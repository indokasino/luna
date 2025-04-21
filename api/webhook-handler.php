<?php
/**
 * Webhook Handler for Luna Chatbot
 * 
 * Receives and processes webhook requests from Chatbot.com
 * Searches database for answers or calls GPT with fallback mechanism
 */

// Define root path
define('LUNA_ROOT', dirname(__DIR__));

// Set error reporting
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

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

// Handle chatbot.com challenge verification
if (isset($_GET['challenge'])) {
    echo $_GET['challenge'];
    exit;
}

// Log the request
$logPath = LUNA_ROOT . '/logs/webhook-' . date('Y-m-d') . '.txt';
$logDir = dirname($logPath);

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode([
        'responses' => [
            [
                'type' => 'TEXT',
                'message' => 'Method not allowed. Only POST is supported.'
            ]
        ]
    ]);
    exit;
}

// Get request body
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

// Log incoming request
logWebhook('Received webhook request: ' . substr($requestBody, 0, 1000));

// Extract question from various possible formats
$question = extractQuestion($requestData);

// If no question found, return error
if (empty($question)) {
    logError('No question found in request');
    http_response_code(400);
    echo json_encode([
        'responses' => [
            [
                'type' => 'TEXT',
                'message' => 'Invalid request. No question found.'
            ]
        ]
    ]);
    exit;
}

// Clean and sanitize the question
$question = trim($question);

// Try to find answer in database
$answer = findInDatabase($question);

if ($answer) {
    // Answer found in database
    logWebhook('Answer found in database for: ' . $question);
    sendResponse($answer, 'db');
    exit;
}

// No answer in database, try GPT-4.1
try {
    $gptResponse = callGpt($question, 'gpt-4.1');
    
    if ($gptResponse['success']) {
        // GPT-4.1 returned an answer
        logWebhook('GPT-4.1 response received for: ' . $question);
        sendResponse($gptResponse['text'], 'gpt-4.1');
        exit;
    }
    
    // GPT-4.1 failed, try GPT-4o fallback
    logWebhook('GPT-4.1 failed, trying GPT-4o fallback for: ' . $question);
    $fallbackResponse = callGpt($question, 'gpt-4o');
    
    if ($fallbackResponse['success']) {
        // GPT-4o returned an answer
        logWebhook('GPT-4o fallback response received for: ' . $question);
        sendResponse($fallbackResponse['text'], 'gpt-4o');
        exit;
    }
    
    // Both GPT calls failed, use fallback response
    logError('All GPT calls failed for: ' . $question);
    $fallbackMessage = getSetting('fallback_response', 'Sorry, I could not process your request at this time.');
    sendResponse($fallbackMessage, 'fallback');
    
} catch (Exception $e) {
    // Handle exceptions
    logError('Exception: ' . $e->getMessage());
    $fallbackMessage = getSetting('fallback_response', 'Sorry, I could not process your request at this time.');
    sendResponse($fallbackMessage, 'error');
}

/**
 * Extract question from various request formats
 * 
 * @param array $data Request data
 * @return string|null Extracted question or null if not found
 */
function extractQuestion($data) {
    // Handle different formats from Chatbot.com
    if (isset($data['text'])) {
        return $data['text'];
    }
    
    if (isset($data['message'])) {
        return $data['message'];
    }
    
    if (isset($data['question'])) {
        return $data['question'];
    }
    
    // Handle responses array format
    if (isset($data['responses']) && is_array($data['responses'])) {
        foreach ($data['responses'] as $response) {
            if (isset($response['type']) && $response['type'] === 'INPUT_MESSAGE' && isset($response['value'])) {
                return $response['value'];
            }
        }
    }
    
    return null;
}

/**
 * Find answer in the database
 * 
 * @param string $question User question
 * @return string|null Answer if found, null otherwise
 */
function findInDatabase($question) {
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
            return $result['answer'];
        }
        
        // No exact match, try semantic search with fulltext
        $stmt = $db->prepare("
            SELECT answer, MATCH(question) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
            FROM prompt_data
            WHERE MATCH(question) AGAINST(? IN NATURAL LANGUAGE MODE) > 0.5
            AND status = 'active'
            ORDER BY relevance DESC
            LIMIT 1
        ");
        $stmt->execute([$question, $question]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['answer'];
        }
        
        // No match found
        return null;
        
    } catch (PDOException $e) {
        logError('Database error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Call GPT API
 * 
 * @param string $question User question
 * @param string $model GPT model to use
 * @return array Response with success status and text
 */
function callGpt($question, $model) {
    $apiKey = getSetting('openai_key', '');
    
    if (empty($apiKey) || $apiKey === 'sk-your-openai-key') {
        logError('API key not set or invalid');
        return [
            'success' => false,
            'text' => 'OpenAI API key not configured properly'
        ];
    }
    
    // Map custom model names to standard OpenAI models if needed
    $standardModels = [
        'gpt-4.1' => 'gpt-4-turbo',
        'gpt-4o' => 'gpt-4o',
    ];
    
    $useModel = isset($standardModels[$model]) ? $standardModels[$model] : $model;
    
    // Load custom system prompt if available
    $systemPrompt = 'You are a helpful assistant.';
    $promptFile = LUNA_ROOT . '/prompt-luna.txt';
    
    if (file_exists($promptFile)) {
        $promptContent = file_get_contents($promptFile);
        if ($promptContent !== false && !empty($promptContent)) {
            $systemPrompt = $promptContent;
        }
    }
    
    // Prepare request for OpenAI API
    $data = [
        'model' => $useModel,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $question
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];
    
    // Initialize cURL
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Handle errors
    if ($error) {
        logError("CURL error: $error");
        return [
            'success' => false,
            'text' => "Connection error: $error"
        ];
    }
    
    if ($httpCode !== 200) {
        logError("HTTP error $httpCode: $response");
        return [
            'success' => false,
            'text' => "API error (HTTP $httpCode)"
        ];
    }
    
    // Parse response
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError("JSON decode error: " . json_last_error_msg());
        return [
            'success' => false,
            'text' => "Invalid API response"
        ];
    }
    
    // Extract the answer
    if (isset($responseData['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'text' => $responseData['choices'][0]['message']['content']
        ];
    }
    
    logError("No content in API response: " . json_encode($responseData));
    return [
        'success' => false,
        'text' => "No content in API response"
    ];
}

/**
 * Send the response
 * 
 * @param string $answer The answer text
 * @param string $source Source of the answer (db, gpt-4.1, gpt-4o, fallback, error)
 */
function sendResponse($answer, $source) {
    global $question;
    
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
            $source,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        logError('Error logging interaction: ' . $e->getMessage());
    }
    
    // Try a different format for Chatbot.com
    // Just return the message directly, which might be what Chatbot.com expects
    $response = $answer;
    
    // Log the response
    logWebhook('Sending simple text response (' . $source . '): ' . substr($answer, 0, 100) . '...');
    
    // Send the response
    echo $response;
    exit;
}

/**
 * Log webhook activity
 * 
 * @param string $message Message to log
 */
function logWebhook($message) {
    global $logPath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        $logPath,
        "[{$timestamp}] {$message}" . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Log errors
 * 
 * @param string $message Error message to log
 */
function logError($message) {
    global $logPath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        $logPath,
        "[{$timestamp}] ERROR: {$message}" . PHP_EOL,
        FILE_APPEND
    );
    
    // Also write to PHP error log
    error_log("LUNA ERROR: {$message}");
}