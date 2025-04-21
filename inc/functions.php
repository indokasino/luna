<?php
/**
 * Helper Functions
 * 
 * Common utility functions for Luna Chatbot
 */

// Prevent direct access
if (!defined('LUNA_ROOT')) {
    die('Access denied');
}

// Include database
require_once LUNA_ROOT . '/inc/db.php';

/**
 * Sanitize output to prevent XSS
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized output
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Get setting from database
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSetting($key, $default = null) {
    try {
        $db = db()->getConnection();
        $stmt = $db->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['value'] : $default;
    } catch (PDOException $e) {
        error_log("Error getting setting $key: " . $e->getMessage());
        return $default;
    }
}

/**
 * Update setting in database
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool True on success, false on failure
 */
function updateSetting($key, $value) {
    try {
        $db = db()->getConnection();
        
        // Check if setting exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $exists = (int)$stmt->fetchColumn() > 0;
        
        if ($exists) {
            // Update existing setting
            $stmt = $db->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
            $result = $stmt->execute([$value, $key]);
        } else {
            // Insert new setting
            $stmt = $db->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
            $result = $stmt->execute([$key, $value]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating setting $key: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if request has valid API token
 * 
 * @return bool True if valid, false otherwise
 */
function hasValidApiToken() {
    // Check for Bearer token in Authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    return auth()->validateApiToken($token);
}

/**
 * Clean old logs
 * 
 * @param int $days Number of days to keep logs
 * @return int Number of deleted records
 */
function cleanupOldLogs($days = 90) {
    try {
        $db = db()->getConnection();
        $stmt = $db->prepare("DELETE FROM prompt_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error cleaning up logs: " . $e->getMessage());
        return 0;
    }
}

/**
 * Generate pagination HTML
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $urlPattern URL pattern with %d placeholder for page number
 * @return string Pagination HTML
 */
function getPagination($currentPage, $totalPages, $urlPattern) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $output = '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $output .= '<a href="' . sprintf($urlPattern, $currentPage - 1) . '" class="page-link">&laquo; Previous</a>';
    } else {
        $output .= '<span class="page-link disabled">&laquo; Previous</span>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $output .= '<a href="' . sprintf($urlPattern, 1) . '" class="page-link">1</a>';
        if ($startPage > 2) {
            $output .= '<span class="page-link">...</span>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $output .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $output .= '<a href="' . sprintf($urlPattern, $i) . '" class="page-link">' . $i . '</a>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $output .= '<span class="page-link">...</span>';
        }
        $output .= '<a href="' . sprintf($urlPattern, $totalPages) . '" class="page-link">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $output .= '<a href="' . sprintf($urlPattern, $currentPage + 1) . '" class="page-link">Next &raquo;</a>';
    } else {
        $output .= '<span class="page-link disabled">Next &raquo;</span>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @param string $type Log type (info, error, warning)
 */
function logToFile($message, $type = 'info') {
    $logDir = LUNA_ROOT . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/log-' . date('Y-m-d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        $logFile,
        "[{$timestamp}] [{$type}] {$message}" . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Return JSON response
 * 
 * @param bool $success Success status
 * @param string $message Response message
 * @param array $data Additional data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($success, $message, $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    
    exit;
}