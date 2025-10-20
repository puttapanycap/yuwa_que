<?php
declare(strict_types=1);

use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, [
        'success' => false,
        'message' => 'Method not allowed',
    ]);
}

$payload = decodeJsonInput(file_get_contents('php://input'));
$ticket = extractTicket($payload['ticket'] ?? null);
$options = normaliseOptions($payload['options'] ?? [], $payload);
$copies = clampInt($payload['copies'] ?? null, 1, 10, 1);
$timeout = clampInt($payload['timeout'] ?? null, 1000, 60000, 5000);

$printer = null;
$connector = null;
$start = microtime(true);

try {
    $connector = createConnector($payload, $timeout);
    $profile = CapabilityProfile::load($options['profile']);
    $printer = new Printer($connector, $profile);

    for ($i = 0; $i < $copies; $i++) {
        $printer->initialize();
        selectCodeTable($printer, $options['codeTable']);
        renderTicket($printer, $ticket, $options);
    }

    $printer->close();
    $printer = null;

    $duration = (int) round((microtime(true) - $start) * 1000);

    sendJson(200, [
        'success' => true,
        'message' => 'Queue ticket printed successfully',
        'copies' => $copies,
        'durationMs' => $duration,
    ]);
} catch (\Throwable $error) {
    if ($printer instanceof Printer) {
        try {
            $printer->close();
        } catch (\Throwable $closeError) {
            error_log('Failed to close printer connection: ' . $closeError->getMessage());
        }
    }

    if ($connector instanceof PrintConnector) {
        try {
            $connector->finalize();
        } catch (\Throwable $closeError) {
            // Ignore cleanup failures.
        }
    }

    error_log('Queue printer error: ' . $error->getMessage());
    $status = ($error instanceof \InvalidArgumentException || $error instanceof \RuntimeException) ? 400 : 500;

    sendJson($status, [
        'success' => false,
        'message' => $error->getMessage(),
    ]);
}

function decodeJsonInput(?string $raw): array
{
    if ($raw === null || $raw === '') {
        throw new \InvalidArgumentException('No payload provided');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new \InvalidArgumentException('Invalid JSON payload');
    }

    return $decoded;
}

function defaultStandardTicketNote(): string
{
    return 'กรุณารอเรียกคิวจากเจ้าหน้าที่';
}

function extractTicket($ticket): array
{
    if (!is_array($ticket)) {
        throw new \InvalidArgumentException('Missing or invalid ticket payload');
    }

    $ticketTemplate = trim((string) ($ticket['ticketTemplate'] ?? ''));
    if ($ticketTemplate === '') {
        $ticketTemplate = 'standard';
    }

    $providedAdditionalNote = $ticket['additionalNote'] ?? '';
    $normalisedAdditionalNote = normaliseMultiline($providedAdditionalNote);

    if ($ticketTemplate === 'appointment_list') {
        $additionalNote = $normalisedAdditionalNote !== ''
            ? $normalisedAdditionalNote
            : '';
    } else {
        $additionalNote = $normalisedAdditionalNote !== ''
            ? $normalisedAdditionalNote
            : normaliseMultiline(defaultStandardTicketNote());
    }

    return [
        'label' => trim((string) ($ticket['label'] ?? 'บัตรคิว')),
        'hospitalName' => trim((string) ($ticket['hospitalName'] ?? '')),
        'serviceType' => trim((string) ($ticket['serviceType'] ?? '')),
        'queueNumber' => trim((string) ($ticket['queueNumber'] ?? '')),
        'servicePoint' => trim((string) ($ticket['servicePoint'] ?? '')),
        'issuedAt' => trim((string) ($ticket['issuedAt'] ?? '')),
        'waitingCount' => filter_var($ticket['waitingCount'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
        'additionalNote' => $additionalNote,
        'footer' => normaliseMultiline($ticket['footer'] ?? ''),
        'qrData' => trim((string) ($ticket['qrData'] ?? '')),
        'ticketTemplate' => $ticketTemplate,
        'appointments' => normaliseTicketAppointmentsForPrinter($ticket['appointments'] ?? []),
        'appointmentPatient' => normaliseTicketPatient($ticket['appointmentPatient'] ?? null),
    ];
}

function normaliseMultiline($text): string
{
    $sanitised = trim((string) $text);
    if ($sanitised === '') {
        return '';
    }

    return preg_replace('/\r\n?/', "\n", $sanitised);
}

function normaliseTicketAppointmentsForPrinter($appointments): array
{
    if (!is_array($appointments)) {
        return [];
    }

    $normalised = [];

    foreach ($appointments as $appointment) {
        if (!is_array($appointment)) {
            continue;
        }

        $timeRange = trim((string) ($appointment['time_range'] ?? ''));
        if ($timeRange === '') {
            $start = trim((string) ($appointment['start_time'] ?? ''));
            $end = trim((string) ($appointment['end_time'] ?? ''));
            if ($start !== '' && $end !== '') {
                $timeRange = $start . ' - ' . $end;
            } elseif ($start !== '') {
                $timeRange = $start;
            }
        }

        $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
        $metadataClinic = trim((string) ($metadata['clinic_name'] ?? ''));
        $metadataCause = trim((string) ($metadata['app_cause'] ?? ''));
        $clinic = $metadataClinic !== ''
            ? $metadataClinic
            : trim((string) ($appointment['clinic_name'] ?? ($appointment['department'] ?? '')));
        $cause = $metadataCause !== ''
            ? $metadataCause
            : trim((string) ($appointment['cause'] ?? ''));

        $detail = trim(implode(' ', array_filter([$clinic, $cause], static function ($part) {
            return trim((string) $part) !== '';
        })));

        if ($timeRange === '' && $detail === '') {
            continue;
        }

        $normalised[] = [
            'time' => $timeRange,
            'detail' => $detail,
            'clinic' => $clinic,
            'cause' => $cause,
            'notes' => '',
            'status' => '',
        ];
    }

    return $normalised;
}

function normaliseTicketPatient($patient): array
{
    if (!is_array($patient)) {
        return [];
    }

    $hn = trim((string) ($patient['hn'] ?? ($patient['HN'] ?? ($patient['patient_hn'] ?? ''))));

    if ($hn === '') {
        return [];
    }

    return [
        'hn' => $hn,
    ];
}

function normaliseOptions(array $options, array $payload): array
{
    $qrErrorLevel = strtoupper((string) ($options['qrErrorLevel'] ?? $payload['qrErrorLevel'] ?? 'M'));
    if (!in_array($qrErrorLevel, ['L', 'M', 'Q', 'H'], true)) {
        $qrErrorLevel = 'M';
    }

    $cutType = strtolower((string) ($options['cutType'] ?? $payload['cutType'] ?? 'partial'));
    if ($cutType !== 'full') {
        $cutType = 'partial';
    }

    $profile = trim((string) ($options['profile'] ?? $payload['profile'] ?? 'default'));
    if ($profile === '') {
        $profile = 'default';
    }

    $codeTable = clampInt($options['codeTable'] ?? $payload['codeTable'] ?? null, 0, 255, 21);

    return [
        'qrModel' => clampInt($options['qrModel'] ?? $payload['qrModel'] ?? null, 1, 3, 2),
        'qrSize' => clampInt($options['qrSize'] ?? $payload['qrSize'] ?? null, 1, 16, 4),
        'qrErrorLevel' => $qrErrorLevel,
        'cutType' => $cutType,
        'trailingFeed' => clampInt($options['trailingFeed'] ?? $payload['trailingFeed'] ?? null, 0, 12, 4),
        'codeTable' => $codeTable,
        'profile' => $profile,
    ];
}

function clampInt($value, int $min, int $max, int $fallback): int
{
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false) {
        return $fallback;
    }

    return max($min, min($max, (int) $filtered));
}

function createConnector(array $payload, int $timeoutMs): PrintConnector
{
    $interface = strtolower(trim((string) ($payload['interface'] ?? '')));
    $target = trim((string) ($payload['target'] ?? ''));
    $port = $payload['port'] ?? null;

    $uri = detectInterfaceUri($interface, $target);

    if ($uri !== null) {
        $parts = parse_url($uri);
        if ($parts === false) {
            throw new \InvalidArgumentException('Invalid printer interface URI');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (in_array($scheme, ['tcp', 'socket', 'udp'], true)) {
            $host = (string) ($parts['host'] ?? '');
            $port = $parts['port'] ?? $port ?? 9100;
            if ($host === '') {
                throw new \InvalidArgumentException('Printer host is not specified');
            }

            return new NetworkPrintConnector($host, (int) $port, max(1, (int) ceil($timeoutMs / 1000)));
        }

        if (in_array($scheme, ['printer', 'smb', 'windows'], true)) {
            $share = ltrim((string) ($parts['host'] ?? '') . ($parts['path'] ?? ''), '/');
            if ($share === '') {
                throw new \InvalidArgumentException('Windows printer share is not specified');
            }

            return new WindowsPrintConnector($share);
        }

        if ($scheme === 'file') {
            $path = ($parts['host'] ?? '') !== ''
                ? $parts['host'] . ($parts['path'] ?? '')
                : ($parts['path'] ?? '');
            if ($path === '') {
                throw new \InvalidArgumentException('File path is not specified for printer output');
            }

            return new FilePrintConnector($path);
        }

        throw new \InvalidArgumentException('Unsupported printer interface scheme: ' . $scheme);
    }

    if (in_array($interface, ['network', 'tcp', 'socket'], true)) {
        if ($target === '') {
            throw new \InvalidArgumentException('Printer host is not specified');
        }

        $host = $target;
        $selectedPort = $port;
        if (strpos($target, ':') !== false && filter_var($target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            [$host, $possiblePort] = explode(':', $target, 2);
            if ($selectedPort === null && $possiblePort !== '') {
                $selectedPort = filter_var($possiblePort, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            }
        }

        return new NetworkPrintConnector($host, (int) ($selectedPort ?? 9100), max(1, (int) ceil($timeoutMs / 1000)));
    }

    if (in_array($interface, ['printer', 'windows'], true)) {
        if ($target === '') {
            throw new \InvalidArgumentException('Printer share name is not specified');
        }

        return new WindowsPrintConnector($target);
    }

    if ($interface === 'file') {
        if ($target === '') {
            throw new \InvalidArgumentException('Printer file path is not specified');
        }

        return new FilePrintConnector($target);
    }

    throw new \InvalidArgumentException('Printer interface is not configured.');
}

function detectInterfaceUri(string $interface, string $target): ?string
{
    if (strpos($interface, '://') !== false) {
        return $interface;
    }

    if (strpos($target, '://') !== false) {
        return $target;
    }

    return null;
}

function selectCodeTable(Printer $printer, int $table): void
{
    try {
        $printer->selectCharacterTable($table);
    } catch (\Throwable $error) {
        error_log('Unable to select character table ' . $table . ': ' . $error->getMessage());
    }
}

function renderTicket(Printer $printer, array $ticket, array $options): void
{
    $image = createTicketEscposImage($ticket, $options);

    $printer->setJustification(Printer::JUSTIFY_CENTER);

    try {
        $printer->graphics($image);
    } catch (\Throwable $error) {
        error_log('Falling back to bit image mode: ' . $error->getMessage());
        $printer->bitImage($image);
    }

    if ($options['trailingFeed'] > 0) {
        $printer->feed($options['trailingFeed']);
    }

    $printer->cut($options['cutType'] === 'full' ? Printer::CUT_FULL : Printer::CUT_PARTIAL);
}

function createTicketEscposImage(array $ticket, array $options): GdEscposImage
{
    $resource = buildTicketImageResource($ticket, $options);

    $image = new GdEscposImage();
    $image->readImageFromGdResource($resource);

    imagedestroy($resource);

    return $image;
}

function buildTicketImageResource(array $ticket, array $options)
{
    $width = 550;
    $initialHeight = 1800;
    $canvas = imagecreatetruecolor($width, $initialHeight);
    if ($canvas === false) {
        throw new \RuntimeException('Unable to allocate image canvas for ticket rendering');
    }

    $white = imagecolorallocate($canvas, 255, 255, 255);
    $black = imagecolorallocate($canvas, 0, 0, 0);
    imagefilledrectangle($canvas, 0, 0, $width, $initialHeight, $white);

    $fontRegular = resolveTicketFontPath('regular');
    $fontBold = resolveTicketFontPath('bold');

    $y = 15.0;
    $maxY = $y;

    $hospitalName = trim($ticket['hospitalName']);
    if ($hospitalName !== '') {
        $y = drawCenteredTextBlock($canvas, [mb_strtoupper($hospitalName, 'UTF-8')], $fontBold, 25, $black, $y, 14, 24);
        $maxY = max($maxY, $y);
    }

    if ($ticket['label'] !== '') {
        $y = drawCenteredTextBlock($canvas, [$ticket['label']], $fontRegular, 32, $black, $y, 10, 18);
        $maxY = max($maxY, $y);
    }

    if ($ticket['serviceType'] !== '') {
        $y = drawCenteredTextBlock($canvas, [$ticket['serviceType']], $fontRegular, 30, $black, $y, 10, 18);
        $maxY = max($maxY, $y);
    }

    if ($ticket['queueNumber'] !== '') {
        $y = drawCenteredTextBlock($canvas, [$ticket['queueNumber']], $fontBold, 125, $black, $y, 10, 24);
        $maxY = max($maxY, $y);
    }

    if ($ticket['servicePoint'] !== '') {
        $y = drawCenteredTextBlock($canvas, [$ticket['servicePoint']], $fontRegular, 30, $black, $y, 10, 18);
        $maxY = max($maxY, $y);
    }

    if ($ticket['issuedAt'] !== '') {
        $y = drawCenteredTextBlock($canvas, [$ticket['issuedAt']], $fontRegular, 26, $black, $y, 10, 14);
        $maxY = max($maxY, $y);
    }

    if ($ticket['waitingCount'] !== null) {
        $waitingLine = 'รอคิวก่อนหน้า ' . $ticket['waitingCount'];
        $y = drawCenteredTextBlock($canvas, [$waitingLine], $fontRegular, 26, $black, $y, 10, 18);
        $maxY = max($maxY, $y);
    }

    $appointmentEntries = isset($ticket['appointments']) && is_array($ticket['appointments'])
        ? $ticket['appointments']
        : [];

    if (!empty($appointmentEntries)) {
        $y = drawCenteredTextBlock($canvas, ['รายการนัดวันนี้'], $fontBold, 24, $black, $y, 12, 18);
        $maxY = max($maxY, $y);

        $patientHn = isset($ticket['appointmentPatient']['hn']) ? trim((string) $ticket['appointmentPatient']['hn']) : '';
        if ($patientHn !== '') {
            $y = drawCenteredTextBlock($canvas, ['HN ' . $patientHn], $fontBold, 22, $black, $y, 8, 16);
            $maxY = max($maxY, $y);
        }

        foreach ($appointmentEntries as $entry) {
            $time = isset($entry['time']) ? trim((string) $entry['time']) : '';
            $clinic = isset($entry['clinic']) ? trim((string) $entry['clinic']) : '';
            $cause = isset($entry['cause']) ? trim((string) $entry['cause']) : '';
            $detail = isset($entry['detail']) ? trim((string) $entry['detail']) : '';

            if ($detail === '') {
                $detail = trim(implode(' ', array_filter([$clinic, $cause], static function ($segment) {
                    return trim((string) $segment) !== '';
                })));
            }

            $lineParts = [];
            if ($time !== '') {
                $lineParts[] = $time;
            }
            if ($detail !== '') {
                $lineParts[] = $detail;
            }

            if (empty($lineParts)) {
                continue;
            }

            $line = implode(' ', $lineParts);

            $y = drawCenteredTextBlock($canvas, [$line], $fontRegular, 22, $black, $y, 10, 16);
            $maxY = max($maxY, $y);
        }
    }

    if ($ticket['additionalNote'] !== '') {
        $noteLines = array_filter(array_map('trim', explode("\n", $ticket['additionalNote'])), static function ($line) {
            return $line !== '';
        });
        if (!empty($noteLines)) {
            $noteFontSize = 22;
            $noteLineSpacing = 10;
            $noteBlockSpacing = 16;

            if ($ticket['ticketTemplate'] === 'appointment_list') {
                $noteFontSize = 20;
                $noteLineSpacing = 8;
                $noteBlockSpacing = 14;
            }

            $y = drawCenteredTextBlock($canvas, $noteLines, $fontRegular, $noteFontSize, $black, $y, $noteLineSpacing, $noteBlockSpacing);
            $maxY = max($maxY, $y);
        }
    }

    if ($ticket['qrData'] !== '') {
        $qrResource = createQrImage($ticket['qrData'], $options['qrErrorLevel'], $options['qrSize']);
        if ($qrResource !== null) {
            $qrWidth = imagesx($qrResource);
            $qrHeight = imagesy($qrResource);
            $y += 12;
            $x = (int) round((imagesx($canvas) - $qrWidth) / 2);
            imagecopy($canvas, $qrResource, $x, (int) round($y), 0, 0, $qrWidth, $qrHeight);
            $y += $qrHeight + 18;
            $maxY = max($maxY, $y);
            imagedestroy($qrResource);
        }
    }

    if ($ticket['footer'] !== '') {
        $footerLines = array_filter(array_map('trim', explode("\n", $ticket['footer'])), static function ($line) {
            return $line !== '';
        });
        if (!empty($footerLines)) {
            $y = drawCenteredTextBlock($canvas, $footerLines, $fontRegular, 22, $black, $y, 12, 16);
            $maxY = max($maxY, $y);
        }
    }

    $paddingBottom = 40;
    $targetHeight = (int) min($initialHeight, max($maxY + $paddingBottom, 120));
    if ($targetHeight < $initialHeight) {
        $cropped = imagecrop($canvas, [
            'x' => 0,
            'y' => 0,
            'width' => $width,
            'height' => $targetHeight,
        ]);
        if ($cropped !== false) {
            imagedestroy($canvas);
            $canvas = $cropped;
        }
    }

    return $canvas;
}

function drawCenteredTextBlock($image, array $lines, string $font, float $fontSize, int $color, float $currentY, float $lineSpacing, float $blockSpacing): float
{
    $width = imagesx($image);
    $y = $currentY;
    $drawn = false;

    foreach ($lines as $line) {
        $text = trim((string) $line);
        if ($text === '') {
            continue;
        }

        [$textWidth, $textHeight] = measureText($font, $fontSize, $text);
        $baseline = $y + $textHeight;
        $x = (int) round(($width - $textWidth) / 2);
        imagettftext($image, $fontSize, 0, $x, (int) round($baseline), $color, $font, $text);
        $y = $baseline + $lineSpacing;
        $drawn = true;
    }

    if ($drawn) {
        $y += $blockSpacing;
    }

    return $y;
}

function measureText(string $font, float $fontSize, string $text): array
{
    $box = imagettfbbox($fontSize, 0, $font, $text);
    if ($box === false) {
        throw new \RuntimeException('Unable to measure text bounds for ticket rendering');
    }

    $width = abs($box[2] - $box[0]);
    $height = abs($box[7] - $box[1]);

    return [$width, $height];
}

function resolveTicketFontPath(string $variant): string
{
    static $cache = [];

    $key = $variant === 'bold' ? 'bold' : 'regular';

    if (!isset($cache[$key])) {
        $fileName = $key === 'bold' ? 'LINESeedSansTH_Bd.ttf' : 'LINESeedSansTH_Rg.ttf';
        $candidate = __DIR__ . '/../assets/fonts/LineSeed/' . $fileName;
        $resolved = realpath($candidate);

        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            throw new \RuntimeException('Ticket font file is missing or unreadable: ' . $fileName);
        }

        $cache[$key] = $resolved;
    }

    return $cache[$key];
}

function createQrImage(string $data, string $errorLevel, int $moduleSize)
{
    try {
        $scale = max(4, min(12, $moduleSize + 2));
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => mapQrErrorLevelToEcc($errorLevel),
            'scale' => $scale,
            'imageTransparent' => false,
            'imageBase64' => false,
            'moduleValues' => [
                0 => [255, 255, 255],
                1 => [0, 0, 0],
            ],
        ]);

        $pngData = (new QRCode($options))->render($data);
        $image = imagecreatefromstring($pngData);
        if ($image === false) {
            throw new \RuntimeException('Unable to decode QR code image data');
        }

        $width = imagesx($image);
        $target = (int) max(180, min(320, $width));
        if ($width !== $target) {
            $scaled = imagescale($image, $target, $target, IMG_NEAREST_NEIGHBOUR);
            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        return $image;
    } catch (\Throwable $error) {
        error_log('Unable to generate QR code image: ' . $error->getMessage());
        return null;
    }
}

function mapQrErrorLevelToEcc(string $level): int
{
    switch ($level) {
        case 'L':
            return QRCode::ECC_L;
        case 'Q':
            return QRCode::ECC_Q;
        case 'H':
            return QRCode::ECC_H;
        case 'M':
        default:
            return QRCode::ECC_M;
    }
}
function sendJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}