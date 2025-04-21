<?php
/**
 * Admin Edit Q&A
 * 
 * Add or edit Q&A entries
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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$question = '';
$answer = '';
$confidence = 1.0;
$status = 'active';
$error = '';
$success = '';

// If ID is provided, load existing record
if ($id > 0) {
    $record = db()->fetchOne("SELECT * FROM prompt_data WHERE id = ?", [$id]);
    
    if ($record) {
        $question = $record['question'];
        $answer = $record['answer'];
        $confidence = $record['confidence_level'];
        $status = $record['status'];
    } else {
        $error = 'Record not found.';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!auth()->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $confidence = floatval($_POST['confidence'] ?? 1.0);
        $status = $_POST['status'] ?? 'active';
        
        // Validate input
        if (empty($question)) {
            $error = 'Question is required.';
        } elseif (empty($answer)) {
            $error = 'Answer is required.';
        } elseif ($confidence < 0 || $confidence > 1) {
            $error = 'Confidence level must be between 0 and 1.';
        } else {
            try {
                // Save the record
                if ($id > 0) {
                    // Update existing record
                    $sql = "
                        UPDATE prompt_data 
                        SET question = ?, answer = ?, confidence_level = ?, status = ? 
                        WHERE id = ?
                    ";
                    $params = [$question, $answer, $confidence, $status, $id];
                    $result = db()->update($sql, $params);
                    
                    if ($result !== false) {
                        $success = 'Q&A updated successfully.';
                    } else {
                        $error = 'Failed to update Q&A.';
                    }
                } else {
                    // Insert new record
                    $sql = "
                        INSERT INTO prompt_data 
                        (question, answer, confidence_level, status) 
                        VALUES (?, ?, ?, ?)
                    ";
                    $params = [$question, $answer, $confidence, $status];
                    $newId = db()->insert($sql, $params);
                    
                    if ($newId) {
                        // Redirect to edit page with new ID
                        header("Location: edit.php?id=$newId&success=created");
                        exit;
                    } else {
                        $error = 'Failed to create Q&A.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Save Q&A error: " . $e->getMessage());
                $error = 'Database error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Check for success message in URL
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = 'Q&A created successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - <?php echo $id > 0 ? 'Edit' : 'Add'; ?> Q&A</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo $id > 0 ? 'Edit' : 'Add New'; ?> Q&A</h1>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
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
        
        <form method="POST" action="edit.php<?php echo $id > 0 ? '?id=' . $id : ''; ?>" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="question">Question <span class="required">*</span></label>
                <textarea id="question" name="question" rows="3" required><?php echo sanitize($question); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="answer">Answer <span class="required">*</span></label>
                <textarea id="answer" name="answer" rows="8" required><?php echo sanitize($answer); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="confidence">Confidence Level</label>
                    <input type="number" id="confidence" name="confidence" step="0.1" min="0" max="1" 
                           value="<?php echo sanitize($confidence); ?>">
                    <small>Value between 0 and 1 indicating how confident we are in this answer</small>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="index.php" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="../assets/js/validation.js"></script>
</body>
</html>