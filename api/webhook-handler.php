<?php
/**
 * Webhook Handler for Luna Chatbot - Final Optimized Version
 * 
 * Receives and processes webhook requests from Chatbot.com
 * Mengambil semua konfigurasi dari database settings table
 */

// Define root path
define('LUNA_ROOT', dirname(__DIR__));

// Set error reporting
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60);

// Include required files
require_once LUNA_ROOT . '/inc/db.php';
require_once LUNA_ROOT . '/inc/functions.php';

// Configure headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS requests (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Create logs directory if it doesn't exist
$logPath = LUNA_ROOT . '/logs/webhook-' . date('Y-m-d') . '.txt';
$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log function for debugging
function logWebhook($message) {
    global $logPath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logPath, "[{$timestamp}] {$message}" . PHP_EOL, FILE_APPEND);
}

// Log the raw request for debugging
$rawInput = file_get_contents('php://input');
logWebhook("Raw input: " . $rawInput);

// Handle chatbot.com challenge verification
if (isset($_GET['challenge'])) {
    echo $_GET['challenge'];
    logWebhook("Challenge response: " . $_GET['challenge']);
    exit;
}

// Process the request
try {
    // Parse incoming JSON
    $data = json_decode($rawInput, true);
    
    // Extract the question from different possible formats
    $question = null;
    
    // Try to extract from responses array
    if (isset($data['responses']) && is_array($data['responses'])) {
        foreach ($data['responses'] as $response) {
            if (isset($response['type']) && $response['type'] === 'INPUT_MESSAGE' && isset($response['value'])) {
                $question = $response['value'];
                break;
            }
        }
    }
    
    // Try other potential locations
    if (empty($question)) {
        if (isset($data['message'])) {
            $question = $data['message'];
        } elseif (isset($data['text'])) {
            $question = $data['text'];
        } elseif (isset($data['question'])) {
            $question = $data['question'];
        }
    }
    
    logWebhook("Extracted question: " . ($question ?? 'NONE'));
    
    // If no question found, return a default response
    if (empty($question)) {
        // Gunakan format dari response_formatter.php
        echo json_encode([
            'responses' => [
                [
                    'type' => 'text',
                    'message' => 'Maaf bosku, Luna tidak menerima pertanyaan dengan jelas. Bisa ulangi lagi?'
                ]
            ]
        ]);
        logWebhook("No question found in the request");
        exit;
    }
    
    // Try to find answer in database first
    $answer = null;
    try {
        $db = db()->getConnection();
        
        // First try exact match (case-insensitive)
        $stmt = $db->prepare("
            SELECT answer 
            FROM prompt_data 
            WHERE LOWER(question) = LOWER(?) 
            AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([trim($question)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $answer = $result['answer'];
            $source = 'db';
        }
    } catch (Exception $e) {
        logWebhook("Database error: " . $e->getMessage());
    }
    
    // If no database answer, try OpenAI
    if (empty($answer)) {
        // Get API key and models from settings table in database
        $apiKey = getSetting('openai_key', '');
        $model = getSetting('gpt_model', 'gpt-4.1');
        
        logWebhook("Using model from settings: $model");
        
        if (!empty($apiKey)) {
            // Load system prompt
            $promptPath = LUNA_ROOT . '/prompt-luna.txt';
            $systemPrompt = file_exists($promptPath) ? file_get_contents($promptPath) : 'You are a helpful assistant.';
            
            // Call OpenAI API
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            
            // Map model names jika perlu
            $useModel = $model;
            if ($model === 'gpt-4.1') {
                $useModel = 'gpt-4-turbo';
            }
            
            $postData = [
                'model' => $useModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $question]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000
            ];
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            logWebhook("OpenAI response code: " . $httpCode);
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                
                if (isset($responseData['choices'][0]['message']['content'])) {
                    $answer = $responseData['choices'][0]['message']['content'];
                    $source = $model;
                }
            } else {
                logWebhook("API error: " . $response);
                
                // Try fallback model
                $fallbackModel = getSetting('fallback_model', 'gpt-4o');
                
                if ($fallbackModel !== 'none') {
                    logWebhook("Trying fallback model: " . $fallbackModel);
                    
                    // Map fallback model if needed
                    $useFallbackModel = $fallbackModel;
                    
                    $postData['model'] = $useFallbackModel;
                    
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    if ($httpCode === 200) {
                        $responseData = json_decode($response, true);
                        
                        if (isset($responseData['choices'][0]['message']['content'])) {
                            $answer = $responseData['choices'][0]['message']['content'];
                            $source = $fallbackModel;
                        }
                    } else {
                        logWebhook("Fallback API error: " . $response);
                    }
                }
            }
            
            curl_close($ch);
        }
    }
    
    // If still no answer, use fallback response from settings
    if (empty($answer)) {
        $answer = getSetting('fallback_response', 'Maaf bosku, Luna tidak bisa memproses permintaan kamu saat ini. Silakan coba lagi nanti.');
        $source = 'fallback';
    }
    
    // Log the interaction
    try {
        $db = db()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO prompt_log (question, answer, source, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $question,
            $answer,
            $source ?? 'unknown',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        logWebhook("Error logging interaction: " . $e->getMessage());
    }
    
    // FINAL RESPONSE FORMAT - SESUAI DENGAN RESPONSE FORMATTER
    $responseData = [
        'responses' => [
            [
                'type' => 'text',
                'message' => $answer
            ]
        ]
    ];
    
    logWebhook("Sending response from source ($source): " . substr($answer, 0, 100) . "...");
    logWebhook("Response JSON: " . json_encode($responseData));
    
    // Send response in JSON format
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Handle any unexpected errors
    logWebhook("Exception: " . $e->getMessage());
    echo json_encode([
        'responses' => [
            [
                'type' => 'text',
                'message' => 'Maaf bosku, ada gangguan teknis. Coba lagi nanti atau ketik CS untuk dibantu tim support.'
            ]
        ]
    ]);
}