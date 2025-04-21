<?php
/**
 * Luna Chatbot Installer
 * 
 * Automatically creates database tables and initial settings
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define root path
define('LUNA_ROOT', __DIR__);

// Simple styling for the installer page
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot Installer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #2c3e50;
        }
        .step {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 10px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 10px;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 10px;
        }
        code {
            background-color: #f1f1f1;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
        }
        pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-family: Consolas, Monaco, monospace;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Luna Chatbot Installer</h1>
';

// Create logs directory if it doesn't exist
$logsDir = LUNA_ROOT . '/logs';
if (!is_dir($logsDir)) {
    if (mkdir($logsDir, 0755, true)) {
        echo '<div class="success">Created logs directory successfully</div>';
    } else {
        echo '<div class="error">Failed to create logs directory. Please create it manually and set proper permissions.</div>';
    }
}

// Check if we need to process the form
if (isset($_POST['install']) && $_POST['install'] === 'yes') {
    // Get database connection details from form
    $host = $_POST['db_host'] ?? 'localhost';
    $dbname = $_POST['db_name'] ?? '';
    $username = $_POST['db_user'] ?? '';
    $password = $_POST['db_pass'] ?? '';
    
    if (empty($dbname) || empty($username)) {
        echo '<div class="error">Database name and username are required.</div>';
    } else {
        echo '<div class="step">';
        echo '<h2>Step 1: Database Connection</h2>';
        
        // Try to connect to the database
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            
            echo '<div class="success">Successfully connected to database!</div>';
            
            // Update database configuration in db.php
            $dbConfigFile = LUNA_ROOT . '/inc/db.php';
            if (file_exists($dbConfigFile)) {
                $dbConfig = file_get_contents($dbConfigFile);
                $dbConfig = preg_replace("/private \\\$host = '.*?';/", "private \$host = '$host';", $dbConfig);
                $dbConfig = preg_replace("/private \\\$dbname = '.*?';/", "private \$dbname = '$dbname';", $dbConfig);
                $dbConfig = preg_replace("/private \\\$username = '.*?';/", "private \$username = '$username';", $dbConfig);
                $dbConfig = preg_replace("/private \\\$password = '.*?';/", "private \$password = '$password';", $dbConfig);
                
                if (file_put_contents($dbConfigFile, $dbConfig)) {
                    echo '<div class="success">Database configuration updated successfully!</div>';
                } else {
                    echo '<div class="error">Failed to update database configuration. Please update inc/db.php manually.</div>';
                }
            } else {
                echo '<div class="error">Database configuration file not found. Please check if inc/db.php exists.</div>';
            }
            
            echo '<h2>Step 2: Creating Database Tables</h2>';
            
            // Read SQL file
            $sqlFile = LUNA_ROOT . '/migrations/create-tables.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if ($sql === false) {
                    echo '<div class="error">Failed to read SQL file. Please check if migrations/create-tables.sql exists and is readable.</div>';
                } else {
                    // Split SQL into separate queries
                    $queries = explode(';', $sql);
                    $queryCount = 0;
                    $errorCount = 0;
                    
                    echo '<div style="max-height:300px; overflow-y:auto; margin-bottom:15px;">';
                    echo '<table>';
                    echo '<tr><th>Query</th><th>Status</th></tr>';
                    
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (empty($query)) continue;
                        
                        try {
                            $pdo->exec($query);
                            echo '<tr><td>' . htmlspecialchars(substr($query, 0, 50)) . '...</td><td style="color:green;">Success</td></tr>';
                            $queryCount++;
                        } catch (PDOException $e) {
                            echo '<tr><td>' . htmlspecialchars(substr($query, 0, 50)) . '...</td><td style="color:red;">Error: ' . $e->getMessage() . '</td></tr>';
                            $errorCount++;
                        }
                    }
                    
                    echo '</table>';
                    echo '</div>';
                    
                    if ($errorCount === 0) {
                        echo '<div class="success">All database tables created successfully! Executed ' . $queryCount . ' queries.</div>';
                    } else {
                        echo '<div class="warning">Database tables creation completed with ' . $errorCount . ' errors. Some tables may already exist.</div>';
                    }
                }
            } else {
                echo '<div class="error">SQL file not found. Please check if migrations/create-tables.sql exists.</div>';
            }
            
            // Generate API Token if needed
            $apiToken = bin2hex(random_bytes(16)); // Generate a random token
            
            // Check if we need to set the OpenAI API key
            $openaiKey = '';
            if (!empty($_POST['openai_key'])) {
                $openaiKey = trim($_POST['openai_key']);
                
                // Update OpenAI API key in settings
                try {
                    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'openai_key'");
                    $stmt->execute([$openaiKey]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo '<div class="success">OpenAI API key updated successfully!</div>';
                    } else {
                        // Try to insert if update failed (key might not exist yet)
                        $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('openai_key', ?) ON DUPLICATE KEY UPDATE value = ?");
                        $stmt->execute([$openaiKey, $openaiKey]);
                        echo '<div class="success">OpenAI API key added successfully!</div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="error">Failed to update OpenAI API key: ' . $e->getMessage() . '</div>';
                }
            }
            
            // Update API token
            try {
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'api_token'");
                $stmt->execute([$apiToken]);
                
                if ($stmt->rowCount() > 0) {
                    echo '<div class="success">API token updated successfully!</div>';
                } else {
                    // Try to insert if update failed
                    $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('api_token', ?) ON DUPLICATE KEY UPDATE value = ?");
                    $stmt->execute([$apiToken, $apiToken]);
                    echo '<div class="success">API token added successfully!</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">Failed to update API token: ' . $e->getMessage() . '</div>';
            }
            
            echo '<h2>Installation Complete!</h2>';
            echo '<div class="success">';
            echo '<p>Luna Chatbot has been successfully installed!</p>';
            echo '<p><strong>API Token:</strong> <code>' . $apiToken . '</code> <br>(You\'ll need this for webhook configuration)</p>';
            echo '<p>You can now login to the admin panel with:</p>';
            echo '<ul>';
            echo '<li><strong>Username:</strong> admin</li>';
            echo '<li><strong>Password:</strong> admin123</li>';
            echo '</ul>';
            echo '<p><strong>IMPORTANT:</strong> Please change the default password immediately after login!</p>';
            echo '<p><a href="admin/login.php" class="btn">Go to Admin Login</a></p>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="error">Database connection failed: ' . $e->getMessage() . '</div>';
            echo '<p>Please check your database credentials and try again.</p>';
            showInstallForm($host, $dbname, $username, $openaiKey ?? '');
        }
        
        echo '</div>'; // end step
    }
} else {
    // Show the installation form
    showInstallForm();
}

/**
 * Show the installation form
 */
function showInstallForm($host = 'localhost', $dbname = '', $username = '', $openaiKey = '') {
    echo '<div class="step">';
    echo '<h2>Database Configuration</h2>';
    echo '<p>Please enter your database details below:</p>';
    
    echo '<form method="POST" action="">';
    echo '<table>';
    echo '<tr><td><label for="db_host">Database Host:</label></td>';
    echo '<td><input type="text" id="db_host" name="db_host" value="' . htmlspecialchars($host) . '" required></td></tr>';
    
    echo '<tr><td><label for="db_name">Database Name:</label></td>';
    echo '<td><input type="text" id="db_name" name="db_name" value="' . htmlspecialchars($dbname) . '" required></td></tr>';
    
    echo '<tr><td><label for="db_user">Database Username:</label></td>';
    echo '<td><input type="text" id="db_user" name="db_user" value="' . htmlspecialchars($username) . '" required></td></tr>';
    
    echo '<tr><td><label for="db_pass">Database Password:</label></td>';
    echo '<td><input type="password" id="db_pass" name="db_pass"></td></tr>';
    
    echo '<tr><td><label for="openai_key">OpenAI API Key (Optional):</label></td>';
    echo '<td><input type="text" id="openai_key" name="openai_key" value="' . htmlspecialchars($openaiKey) . '" placeholder="sk-..."></td></tr>';
    echo '</table>';
    
    echo '<input type="hidden" name="install" value="yes">';
    echo '<button type="submit" class="btn">Install Luna Chatbot</button>';
    echo '</form>';
    
    echo '</div>';
}

// Close HTML
echo '</body></html>';