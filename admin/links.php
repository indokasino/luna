<?php
/**
 * Admin Quick Links Page
 * 
 * Shows important system links and allows direct access
 */

// Define root path
define('LUNA_ROOT', dirname(__DIR__));

// Include required files
require_once LUNA_ROOT . '/inc/db.php';
require_once LUNA_ROOT . '/inc/auth.php';
require_once LUNA_ROOT . '/inc/functions.php';

// Require login
requireLogin();

// Get base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . "://" . $host;

// If script is in a subdirectory, add it to base URL
$scriptDir = dirname(dirname($_SERVER['PHP_SELF']));
if ($scriptDir != '/' && $scriptDir != '\\') {
    $baseUrl .= $scriptDir;
}

// Get API token from database
$apiToken = getSetting('api_token', 'Not set');

// Define important links
$links = [
    [
        'title' => 'Webhook Handler',
        'url' => $baseUrl . '/api/webhook-handler.php',
        'description' => 'Main webhook endpoint for Chatbot.com integration',
        'note' => 'Use this URL in your Chatbot.com webhook configuration with the API token'
    ],
    [
        'title' => 'Q&A Export',
        'url' => $baseUrl . '/qna.php',
        'description' => 'Export Q&A data for Chatbot.com Knowledge Base',
        'note' => 'View, filter, and copy all Q&A entries'
    ],
    [
        'title' => 'Admin Dashboard',
        'url' => $baseUrl . '/admin/index.php',
        'description' => 'Main admin dashboard',
        'note' => 'Manage Q&A entries'
    ],
    [
        'title' => 'Settings',
        'url' => $baseUrl . '/admin/settings.php',
        'description' => 'System settings and configuration',
        'note' => 'Configure API keys, models, and system settings'
    ],
    [
        'title' => 'Interactive Logs',
        'url' => $baseUrl . '/admin/history.php',
        'description' => 'View interaction logs',
        'note' => 'See all chatbot interactions'
    ]
];

// Get webhook test data if requested
$webhookTestData = '';
$testResult = '';
if (isset($_GET['test_webhook']) && $_GET['test_webhook'] === '1') {
    // Prepare webhook test
    $webhookUrl = $baseUrl . '/api/webhook-handler.php';
    $testQuestion = 'This is a test question from admin panel';
    
    // Create test JSON data
    $webhookTestData = json_encode([
        'question' => $testQuestion
    ], JSON_PRETTY_PRINT);
    
    // Send test request to webhook
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $webhookTestData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        $testResult = "<div class='alert alert-danger'>Error: $error</div>";
    } else {
        $testResult = "<div class='alert alert-success'>
            <strong>HTTP Code:</strong> $httpCode<br>
            <strong>Response:</strong><br>
            <pre>" . htmlspecialchars($response) . "</pre>
        </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - Quick Links</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .links-container {
            margin-bottom: 30px;
        }
        .link-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .link-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .link-description {
            margin-bottom: 10px;
            color: #555;
        }
        .link-note {
            font-style: italic;
            color: #777;
            margin-bottom: 15px;
        }
        .link-url {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            word-break: break-all;
        }
        .link-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .webhook-test {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        .api-token {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Quick Links</h1>
        </div>
        
        <div class="links-container">
            <?php foreach ($links as $link): ?>
            <div class="link-card">
                <div class="link-title"><?php echo $link['title']; ?></div>
                <div class="link-description"><?php echo $link['description']; ?></div>
                <div class="link-note"><?php echo $link['note']; ?></div>
                <div class="link-url"><?php echo $link['url']; ?></div>
                <div class="link-buttons">
                    <a href="<?php echo $link['url']; ?>" target="_blank" class="btn btn-primary">Open in New Tab</a>
                    <button onclick="copyToClipboard('<?php echo $link['url']; ?>')" class="btn btn-secondary">Copy URL</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="webhook-test">
            <h2>Webhook Test</h2>
            <p>Your API Token:</p>
            <div class="api-token"><?php echo $apiToken; ?></div>
            
            <p>Test your webhook with a sample request:</p>
            
            <form method="GET" action="links.php">
                <input type="hidden" name="test_webhook" value="1">
                <button type="submit" class="btn btn-primary">Test Webhook</button>
            </form>
            
            <?php if ($webhookTestData): ?>
            <div style="margin-top: 20px;">
                <h3>Test Request:</h3>
                <pre><?php echo htmlspecialchars($webhookTestData); ?></pre>
                
                <h3>Test Result:</h3>
                <?php echo $testResult; ?>
            </div>
            <?php endif; ?>
            
            <h3>Chatbot.com Webhook Setup Instructions:</h3>
            <ol>
                <li>In your Chatbot.com dashboard, go to Settings > Integrations</li>
                <li>Add a new Webhook integration</li>
                <li>Enter Webhook URL: <code><?php echo $baseUrl; ?>/api/webhook-handler.php</code></li>
                <li>Add Authorization header:
                    <pre>Authorization: Bearer <?php echo $apiToken; ?></pre>
                </li>
                <li>Set Content-Type to <code>application/json</code></li>
                <li>Save and test the configuration</li>
            </ol>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('URL copied to clipboard!');
    }
    </script>
</body>
</html>