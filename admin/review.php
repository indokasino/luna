<?php
/**
 * Admin GPT Review Page
 * 
 * Reviews GPT-generated responses and adds them to the database
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

// Process form submission for adding to database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_db') {
    // Validate CSRF token
    if (!auth()->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $logId = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $confidence = floatval($_POST['confidence'] ?? 0.8);
        $status = $_POST['status'] ?? 'active';
        
        if ($logId <= 0 || empty($question) || empty($answer)) {
            $error = 'Invalid data provided.';
        } else {
            try {
                // Begin transaction
                $db = db()->getConnection();
                $db->beginTransaction();
                
                // Check if question already exists
                $existingId = db()->fetchOne(
                    "SELECT id FROM prompt_data WHERE LOWER(question) = LOWER(?) LIMIT 1", 
                    [strtolower($question)]
                );
                
                if ($existingId) {
                    // Update existing record
                    $sql = "
                        UPDATE prompt_data 
                        SET answer = ?, confidence_level = ?, status = ? 
                        WHERE id = ?
                    ";
                    $params = [$answer, $confidence, $status, $existingId['id']];
                    db()->update($sql, $params);
                    
                    $success = 'Existing Q&A updated successfully.';
                } else {
                    // Insert new record
                    $sql = "
                        INSERT INTO prompt_data 
                        (question, answer, confidence_level, status) 
                        VALUES (?, ?, ?, ?)
                    ";
                    $params = [$question, $answer, $confidence, $status];
                    $newId = db()->insert($sql, $params);
                    
                    if (!$newId) {
                        throw new Exception('Failed to create new Q&A entry.');
                    }
                    
                    $success = 'New Q&A created successfully.';
                }
                
                // Commit transaction
                $db->commit();
                
                // Redirect to avoid form resubmission
                header("Location: review.php?success=" . urlencode($success));
                exit;
            } catch (Exception $e) {
                // Rollback transaction on error
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("Add to DB error: " . $e->getMessage());
                $error = 'Database error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get success/error message from URL
if (empty($error) && isset($_GET['error'])) {
    $error = $_GET['error'];
}

if (empty($success) && isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get specific log entry if ID is provided
$log = null;
if (isset($_GET['log_id'])) {
    $logId = (int)$_GET['log_id'];
    $log = db()->fetchOne("SELECT * FROM prompt_log WHERE id = ?", [$logId]);
}

// Get all GPT responses
$sql = "
    SELECT * 
    FROM prompt_log 
    WHERE source IN ('gpt-4.1', 'gpt-4o')
    ORDER BY created_at DESC 
    LIMIT 20
";

$logs = db()->fetchAll($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - GPT Review</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .review-container {
            display: flex;
            gap: 20px;
        }
        .review-sidebar {
            width: 30%;
        }
        .review-main {
            width: 70%;
        }
        .log-list {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .log-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        .log-item:hover {
            background-color: #f5f5f5;
        }
        .log-item.active {
            background-color: #e1f0fa;
        }
        .log-question {
            font-weight: bold;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .log-date {
            font-size: 12px;
            color: #777;
        }
        .review-card {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .edit-form {
            background-color: #f9f9f9;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        .message-box {
            background-color: #f5f5f5;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .message-box h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>GPT Response Review</h1>
            <a href="history.php" class="btn btn-secondary">View All Logs</a>
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
        
        <div class="review-container">
            <div class="review-sidebar">
                <h3>Recent GPT Responses</h3>
                
                <div class="log-list">
                    <?php foreach ($logs as $item): ?>
                    <div class="log-item <?php echo ($log && $log['id'] == $item['id']) ? 'active' : ''; ?>" 
                         onclick="window.location.href='review.php?log_id=<?php echo $item['id']; ?>'">
                        <div class="log-question"><?php echo sanitize(substr($item['question'], 0, 50)) . (strlen($item['question']) > 50 ? '...' : ''); ?></div>
                        <div class="log-date">
                            <span class="source-badge source-<?php echo $item['source']; ?>"><?php echo strtoupper($item['source']); ?></span>
                            <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($logs) === 0): ?>
                    <div class="log-item">
                        <p>No GPT responses found.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="review-main">
                <?php if ($log): ?>
                <div class="review-card">
                    <h3>Review GPT Response</h3>
                    
                    <div class="message-box">
                        <h4>Question:</h4>
                        <p><?php echo sanitize($log['question']); ?></p>
                    </div>
                    
                    <div class="message-box">
                        <h4>GPT Response:</h4>
                        <p><?php echo nl2br(sanitize($log['answer'])); ?></p>
                    </div>
                    
                    <div class="source-info">
                        <p>
                            <strong>Source:</strong> <?php echo strtoupper($log['source']); ?><br>
                            <strong>Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div class="edit-form">
                        <h4>Add to Database</h4>
                        <form method="POST" action="review.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="add_to_db">
                            <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                            
                            <div class="form-group">
                                <label for="question">Question</label>
                                <textarea id="question" name="question" rows="2" required><?php echo sanitize($log['question']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="answer">Answer</label>
                                <textarea id="answer" name="answer" rows="5" required><?php echo sanitize($log['answer']); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="confidence">Confidence Level</label>
                                    <input type="number" id="confidence" name="confidence" step="0.1" min="0" max="1" value="0.8">
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="status">Status</label>
                                    <select id="status" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save to Database</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="review-card">
                    <h3>GPT Response Review</h3>
                    <p>Select a GPT response from the list on the left to review and add it to the database.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>