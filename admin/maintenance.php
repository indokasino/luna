<?php
/**
 * Admin Maintenance Page
 * 
 * Handles system maintenance tasks
 */

// Define root path
define('LUNA_ROOT', dirname(__DIR__));

// Include required files
require_once LUNA_ROOT . '/inc/db.php';
require_once LUNA_ROOT . '/inc/auth.php';
require_once LUNA_ROOT . '/inc/functions.php';

// Require login
requireLogin();

// Generate CSRF token
$csrfToken = auth()->generateCsrfToken();

// Initialize variables
$error = '';
$success = '';

// Check action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Validate CSRF token
if (!empty($action) && !empty($token) && auth()->verifyCsrfToken($token)) {
    // Process actions
    switch ($action) {
        case 'clean_logs':
            // Get retention days from settings
            $retentionDays = (int)getSetting('log_retention_days', 90);
            
            try {
                // Clean old logs
                $count = cleanupOldLogs($retentionDays);
                
                if ($count > 0) {
                    $success = "Successfully deleted $count old log entries.";
                } else {
                    $success = "No logs older than $retentionDays days were found.";
                }
            } catch (Exception $e) {
                $error = "Error cleaning logs: " . $e->getMessage();
            }
            break;
            
        default:
            $error = 'Invalid action specified.';
            break;
    }
} elseif (!empty($action)) {
    $error = 'Invalid security token. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - System Maintenance</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>System Maintenance</h1>
            <a href="settings.php" class="btn btn-secondary">Back to Settings</a>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo sanitize($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo sanitize($success); ?>
        </div>
        <?php endif; ?>
        
        <div class="settings-card">
            <h2>Maintenance Tasks</h2>
            <p>These tasks help maintain system performance and data hygiene.</p>
            
            <div class="maintenance-task">
                <h3>Clean Old Logs</h3>
                <p>Removes log entries older than the configured retention period.</p>
                <p>Current retention period: <strong><?php echo getSetting('log_retention_days', 90); ?> days</strong></p>
                <a href="maintenance.php?action=clean_logs&token=<?php echo $csrfToken; ?>" 
                   class="btn btn-warning" onclick="return confirm('Are you sure you want to clean old logs?');">
                    Run Task
                </a>
            </div>
        </div>
        
        <div class="settings-card">
            <h2>System Information</h2>
            
            <div class="system-info">
                <table class="table">
                    <tr>
                        <th>PHP Version:</th>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>Server Software:</th>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                    </tr>
                    <tr>
                        <th>Database:</th>
                        <td>
                            <?php
                            try {
                                $db = db()->getConnection();
                                echo $db->getAttribute(PDO::ATTR_DRIVER_NAME) . ' ' . $db->getAttribute(PDO::ATTR_SERVER_VERSION);
                            } catch (Exception $e) {
                                echo 'Error connecting to database';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Logs Directory:</th>
                        <td>
                            <?php
                            $logsDir = LUNA_ROOT . '/logs';
                            if (is_dir($logsDir)) {
                                echo 'Exists';
                                if (is_writable($logsDir)) {
                                    echo ' (Writable)';
                                } else {
                                    echo ' (Not writable)';
                                }
                            } else {
                                echo 'Does not exist';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Prompt Template:</th>
                        <td>
                            <?php
                            $promptFile = LUNA_ROOT . '/prompt-luna.txt';
                            if (file_exists($promptFile)) {
                                echo 'Exists';
                                if (is_readable($promptFile)) {
                                    echo ' (Readable)';
                                } else {
                                    echo ' (Not readable)';
                                }
                                if (is_writable($promptFile)) {
                                    echo ' (Writable)';
                                } else {
                                    echo ' (Not writable)';
                                }
                            } else {
                                echo 'Does not exist';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>