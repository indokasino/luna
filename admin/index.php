<?php
/**
 * Admin Dashboard - Q&A Management
 * 
 * Lists all Q&A entries with filtering and pagination
 */

// Define root path
define('LUNA_ROOT', dirname(__DIR__));

// Include required files
require_once LUNA_ROOT . '/inc/db.php';
require_once LUNA_ROOT . '/inc/auth.php';
require_once LUNA_ROOT . '/inc/functions.php';

// Require login
requireLogin();

// Initialize database
$db = db()->getConnection();

// Generate CSRF token
$csrfToken = auth()->generateCsrfToken();

// Handle filters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with filters
$sql = "SELECT * FROM prompt_data WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $sql .= " AND (question LIKE ? OR answer LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id DESC";

// Count total records for pagination
$countSql = "SELECT COUNT(*) FROM prompt_data WHERE 1=1";
$countParams = [];

if ($status !== 'all') {
    $countSql .= " AND status = ?";
    $countParams[] = $status;
}

if (!empty($search)) {
    $countSql .= " AND (question LIKE ? OR answer LIKE ?)";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

$totalRecords = db()->count($countSql, $countParams);

// Pagination
$recordsPerPage = 20;
$totalPages = ceil($totalRecords / $recordsPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Add limit to SQL
$sql .= " LIMIT $offset, $recordsPerPage";

// Execute query
$records = db()->fetchAll($sql, $params);

// Process success/error messages
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// Map error codes to messages
$errorMessages = [
    'invalid_token' => 'Invalid security token. Please try again.',
    'invalid_id' => 'Invalid record ID.',
    'delete_failed' => 'Failed to delete record.',
    'database_error' => 'Database error occurred.'
];

// Map success codes to messages
$successMessages = [
    'deleted' => 'Record deleted successfully.',
    'updated' => 'Record updated successfully.',
    'created' => 'New record created successfully.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Q&A Management</h1>
            <a href="edit.php" class="btn btn-primary">Add New Q&A</a>
        </div>
        
        <?php if (!empty($error) && isset($errorMessages[$error])): ?>
        <div class="alert alert-danger">
            <?php echo sanitize($errorMessages[$error]); ?>
            <?php if ($error === 'database_error' && isset($_GET['message'])): ?>
                <br><?php echo sanitize($_GET['message']); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success) && isset($successMessages[$success])): ?>
        <div class="alert alert-success">
            <?php echo sanitize($successMessages[$success]); ?>
        </div>
        <?php endif; ?>
        
        <div class="filter-bar">
            <form action="index.php" method="GET" class="filter-form">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search questions/answers" 
                           value="<?php echo sanitize($search); ?>">
                </div>
                
                <div class="form-group">
                    <select name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="index.php" class="btn btn-link">Reset</a>
                </div>
            </form>
        </div>
        
        <div class="data-summary">
            <p>Showing <?php echo count($records); ?> of <?php echo $totalRecords; ?> entries</p>
        </div>
        
        <?php if (count($records) > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Status</th>
                        <th>Confidence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo $record['id']; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $record['id']; ?>" class="question-link">
                                <?php echo strlen($record['question']) > 80 ? 
                                    sanitize(substr($record['question'], 0, 80)) . '...' : 
                                    sanitize($record['question']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $record['status']; ?>">
                                <?php echo ucfirst($record['status']); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($record['confidence_level'], 2); ?></td>
                        <td class="actions">
                            <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            
                            <form method="POST" action="delete.php" class="inline-form" 
                                  onsubmit="return confirm('Are you sure you want to delete this Q&A?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <?php
            // Build URL for pagination links
            $paginationUrl = 'index.php?';
            if ($status !== 'all') $paginationUrl .= "status=$status&";
            if (!empty($search)) $paginationUrl .= "search=" . urlencode($search) . "&";
            $paginationUrl .= "page=%d";
            
            echo getPagination($currentPage, $totalPages, $paginationUrl);
            ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="no-records">
            <p>No records found matching your criteria.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="../assets/js/validation.js"></script>
</body>
</html>