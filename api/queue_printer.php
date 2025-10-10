<?php
declare(strict_types=1);

use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

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

function extractTicket($ticket): array
{
    if (!is_array($ticket)) {
        throw new \InvalidArgumentException('Missing or invalid ticket payload');
    }

    return [
        'label' => trim((string) ($ticket['label'] ?? 'บัตรคิว')),
        'hospitalName' => trim((string) ($ticket['hospitalName'] ?? '')),
        'serviceType' => trim((string) ($ticket['serviceType'] ?? '')),
        'queueNumber' => trim((string) ($ticket['queueNumber'] ?? '')),
        'servicePoint' => trim((string) ($ticket['servicePoint'] ?? '')),
        'issuedAt' => trim((string) ($ticket['issuedAt'] ?? '')),
        'waitingCount' => filter_var($ticket['waitingCount'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
        'additionalNote' => normaliseMultiline($ticket['additionalNote'] ?? ''),
        'footer' => normaliseMultiline($ticket['footer'] ?? ''),
        'qrData' => trim((string) ($ticket['qrData'] ?? '')),
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
    $printer->setJustification(Printer::JUSTIFY_CENTER);

    if ($ticket['hospitalName'] !== '') {
        $printer->setEmphasis(true);
        $printer->text(mb_strtoupper($ticket['hospitalName'], 'UTF-8') . "
");
        $printer->setEmphasis(false);
    }

    if ($ticket['label'] !== '') {
        $printer->text($ticket['label'] . "
");
    }

    if ($ticket['serviceType'] !== '') {
        $printer->text($ticket['serviceType'] . "
");
    }

    if ($ticket['queueNumber'] !== '') {
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 2);
        $printer->text($ticket['queueNumber'] . "
");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
    }

    if ($ticket['servicePoint'] !== '') {
        $printer->text($ticket['servicePoint'] . "
");
    }

    if ($ticket['issuedAt'] !== '') {
        $printer->text($ticket['issuedAt'] . "
");
    }

    if ($ticket['waitingCount'] !== null) {
        $printer->text('รอคิวก่อนหน้า ' . $ticket['waitingCount'] . "
");
    }

    if ($ticket['additionalNote'] !== '') {
        foreach (explode("
", $ticket['additionalNote']) as $line) {
            $printer->text($line . "
");
        }
    }

    if ($ticket['qrData'] !== '') {
        $printer->feed(1);
        try {
            $printer->qrCode(
                $ticket['qrData'],
                mapQrErrorLevel($options['qrErrorLevel']),
                $options['qrSize'],
                mapQrModel($options['qrModel'])
            );
            $printer->feed(1);
        } catch (\Throwable $error) {
            error_log('Unable to render QR code: ' . $error->getMessage());
            $printer->text($ticket['qrData'] . "
");
        }
    }

    if ($ticket['footer'] !== '') {
        foreach (explode("
", $ticket['footer']) as $line) {
            $printer->text($line . "
");
        }
    }

    if ($options['trailingFeed'] > 0) {
        $printer->feed($options['trailingFeed']);
    }

    $printer->cut($options['cutType'] === 'full' ? Printer::CUT_FULL : Printer::CUT_PARTIAL);
}

function mapQrErrorLevel(string $level): int
{
    switch ($level) {
        case 'L':
            return Printer::QR_ECLEVEL_L;
        case 'Q':
            return Printer::QR_ECLEVEL_Q;
        case 'H':
            return Printer::QR_ECLEVEL_H;
        case 'M':
        default:
            return Printer::QR_ECLEVEL_M;
    }
}

function mapQrModel(int $model): int
{
    switch ($model) {
        case 1:
            return Printer::QR_MODEL_1;
        case 3:
            return Printer::QR_MICRO;
        case 2:
        default:
            return Printer::QR_MODEL_2;
    }
}

function sendJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
