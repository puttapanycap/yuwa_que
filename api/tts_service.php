<?php
/**
 * TTS Service API
 *
 * This script handles Text-to-Speech (TTS) requests, converting text to audio
 * using various configured TTS providers.
 *
 * @category API
 * @package  YuwaQueue
 * @author   Puttapong <puttapong.p@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/puttapanycap/yuwa_que
 */
require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $text = $input['text'] ?? '';
    $language = $input['language'] ?? 'th-TH';
    $voice = $input['voice'] ?? 'th-TH-Standard-A';
    $speed = floatval($input['speed'] ?? 1.0);
    $pitch = floatval($input['pitch'] ?? 0);
    
    if (empty($text)) {
        throw new Exception('ไม่มีข้อความที่จะแปลงเป็นเสียง');
    }
    
    // แปลงข้อความให้เหมาะสำหรับการอ่าน
    $processedText = processTextForSpeech($text);
    
    // Get TTS settings
    $ttsProvider = getSetting('tts_provider', 'google');
    $ttsApiUrl = getSetting('tts_api_url', '');
    
    switch ($ttsProvider) {
    case 'google_free':
        $audioData = generateGoogleFreeTTS($processedText, $language, $speed);
        break;
    case 'google':
        $audioData = generateGoogleTTS($processedText, $language, $voice, $speed, $pitch);
        break;
    case 'azure':
        $audioData = generateAzureTTS($processedText, $language, $voice, $speed, $pitch);
        break;
    case 'amazon':
        $audioData = generateAmazonTTS($processedText, $language, $voice, $speed, $pitch);
        break;
    case 'custom':
        if (empty($ttsApiUrl)) {
            throw new Exception('ไม่ได้กำหนด URL สำหรับ TTS API');
        }
        $audioData = generateCustomTTS($processedText, $language, $voice, $speed, $pitch, $ttsApiUrl);
        break;
    default:
        throw new Exception('ผู้ให้บริการ TTS ไม่ถูกต้อง');
    }
    
    if ($audioData === false) {
        throw new Exception('ไม่สามารถสร้างไฟล์เสียงได้');
    }
    
    // Return audio data
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audioData));
    echo $audioData;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Processes text to make it more suitable for speech synthesis.
 *
 * @param string $text The input text.
 *
 * @return string The processed text.
 */
function processTextForSpeech($text)
{
    // แยกหมายเลขคิวออกจากข้อความ
    $processedText = preg_replace_callback(
        '/([A-Z]+)(\d+)/',
        function ($matches) {
            $letters = $matches[1];
            $numbers = $matches[2];
        
            // แยกตัวอักษร
            $letterArray = str_split($letters);
            $letterText = implode(' ', $letterArray);
        
            // แยกตัวเลข
            $numberArray = str_split($numbers);
            $numberText = implode(' ', $numberArray);
        
            return $letterText . ' ' . $numberText;
        },
        $text
    );
    
    // แทนที่คำที่ต้องการให้อ่านแบบพิเศษ
    $replacements = [
        // ตัวเลข
        '0' => 'ศูนย์',
        '1' => 'หนึ่ง',
        '2' => 'สอง',
        '3' => 'สาม',
        '4' => 'สี่',
        '5' => 'ห้า',
        '6' => 'หก',
        '7' => 'เจ็ด',
        '8' => 'แปด',
        '9' => 'เก้า',
        
        // ตัวอักษร (ถ้าต้องการให้อ่านเป็นภาษาไทย)
        'A' => 'เอ',
        'B' => 'บี',
        'C' => 'ซี',
        'D' => 'ดี',
        'E' => 'อี',
        'F' => 'เอฟ',
        'G' => 'จี',
        'H' => 'เอช',
        'I' => 'ไอ',
        'J' => 'เจ',
        'K' => 'เค',
        'L' => 'แอล',
        'M' => 'เอ็ม',
        'N' => 'เอ็น',
        'O' => 'โอ',
        'P' => 'พี',
        'Q' => 'คิว',
        'R' => 'อาร์',
        'S' => 'เอส',
        'T' => 'ที',
        'U' => 'ยู',
        'V' => 'วี',
        'W' => 'ดับเบิลยู',
        'X' => 'เอ็กซ์',
        'Y' => 'วาย',
        'Z' => 'แซด'
    ];
    
    // ตรวจสอบการตั้งค่าว่าจะให้อ่านตัวอักษรเป็นภาษาไทยหรือไม่
    $readLettersInThai = getSetting('read_letters_in_thai', '1') == '1';
    
    if ($readLettersInThai) {
        $processedText = str_replace(array_keys($replacements), array_values($replacements), $processedText);
    }
    
    // เพิ่มช่วงหยุดระหว่างตัวอักษรและตัวเลข
    $processedText = preg_replace('/([A-Za-zก-๙]) ([A-Za-zก-๙])/', '$1, $2', $processedText);
    
    return $processedText;
}

/**
 * Generates audio using the free Google Translate TTS service.
 *
 * @param string $text     The text to synthesize.
 * @param string $language The language code (e.g., 'th-TH').
 * @param float  $speed    The speech rate.
 *
 * @return string|false The audio data or false on failure.
 */
function generateGoogleFreeTTS($text, $language, $speed)
{
    $url = "https://translate.google.com/translate_tts?ie=UTF-8&q=" . urlencode($text) . "&tl=" . $language . "&client=tw-ob&ttsspeed=" . $speed;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
    
    $audioData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $audioData !== false) {
        return $audioData;
    }
    
    return false;
}

/**
 * Placeholder for Google Cloud TTS integration.
 *
 * @param string $text     The text to synthesize.
 * @param string $language The language code.
 * @param string $voice    The voice name.
 * @param float  $speed    The speech rate.
 * @param float  $pitch    The speech pitch.
 *
 * @return void
 */
function generateGoogleTTS($text, $language, $voice, $speed, $pitch)
{
    // This is a placeholder for Google Cloud Text-to-Speech integration
    // You would need to implement actual Google Cloud TTS API calls here
    // For now, return false to indicate TTS is not available
    http_response_code(501);
    echo json_encode(['error' => 'Google TTS not implemented']);
    exit;
}

/**
 * Placeholder for Azure Speech Service integration.
 *
 * @param string $text     The text to synthesize.
 * @param string $language The language code.
 * @param string $voice    The voice name.
 * @param float  $speed    The speech rate.
 * @param float  $pitch    The speech pitch.
 *
 * @return void
 */
function generateAzureTTS($text, $language, $voice, $speed, $pitch)
{
    // This is a placeholder for Azure Speech Service integration
    // You would need to implement actual Azure Speech API calls here
    http_response_code(501);
    echo json_encode(['error' => 'Azure TTS not implemented']);
    exit;
}

/**
 * Placeholder for Amazon Polly integration.
 *
 * @param string $text     The text to synthesize.
 * @param string $language The language code.
 * @param string $voice    The voice name.
 * @param float  $speed    The speech rate.
 * @param float  $pitch    The speech pitch.
 *
 * @return void
 */
function generateAmazonTTS($text, $language, $voice, $speed, $pitch)
{
    // This is a placeholder for Amazon Polly integration
    // You would need to implement actual Amazon Polly API calls here
    http_response_code(501);
    echo json_encode(['error' => 'Amazon TTS not implemented']);
    exit;
}

/**
 * Generates audio using a custom TTS API.
 *
 * @param string $text     The text to synthesize.
 * @param string $language The language code.
 * @param string $voice    The voice name.
 * @param float  $speed    The speech rate.
 * @param float  $pitch    The speech pitch.
 * @param string $apiUrl   The URL of the custom TTS API.
 *
 * @return string|false The audio data or false on failure.
 */
function generateCustomTTS($text, $language, $voice, $speed, $pitch, $apiUrl)
{
    // Call custom TTS API
    $postData = [
        'text' => $text,
        'language' => $language,
        'voice' => $voice,
        'speed' => $speed,
        'pitch' => $pitch
    ];
    
    $ch = curl_init();
    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: audio/mpeg'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]
    );
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response !== false) {
        return $response;
    }
    
    return false;
}
?>
