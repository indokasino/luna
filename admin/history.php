<?php
/**
 * Admin History Page
 * 
 * View and filter chatbot interaction logs
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

// Handle filters
$source = isset($_GET['source']) ? $_GET['source'] : 'all';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with filters
$sql = "SELECT * FROM prompt_log WHERE 1=1";
$params = [];

if ($source !== 'all') {
    $sql .= " AND source = ?";
    $params[] = $source;
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($search)) {
    $sql .= " AND (question LIKE ? OR answer LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

// Count total records for pagination
$countSql = "SELECT COUNT(*) FROM prompt_log WHERE 1=1";
$countParams = $params;

// Remove the search parameters since they're duplicated
if (!empty($search)) {
    // Remove the last two parameters which are for search
    array_pop($countParams);
    array_pop($countParams);
    // Add them back only once
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

// Pagination
$recordsPerPage = 20;
$totalRecords = db()->count($countSql, $countParams);
$totalPages = ceil($totalRecords / $recordsPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Add limit to SQL
$sql .= " LIMIT $offset, $recordsPerPage";

// Execute query
$logs = db()->fetchAll($sql, $params);

// Get base URL for forms
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$baseUrl = strtok($baseUrl, '?'); // Remove query string
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - Interaction History</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Interaction History</h1>
        </div>
        
        <div class="filter-bar">
            <form action="history.php" method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Search in questions/answers" 
                               value="<?php echo sanitize($search); ?>">
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="source">Source</label>
                        <select id="source" name="source">
                            <option value="all" <?php echo $source === 'all' ? 'selected' : ''; ?>>All Sources</option>
                            <option value="db" <?php echo $source === 'db' ? 'selected' : ''; ?>>Database</option>
                            <option value="gpt-4.1" <?php echo $source === 'gpt-4.1' ? 'selected' : ''; ?>>GPT-4.1</option>
                            <option value="gpt-4o" <?php echo $source === 'gpt-4o' ? 'selected' : ''; ?>>GPT-4o</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo sanitize($dateFrom); ?>">
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo sanitize($dateTo); ?>">
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-secondary btn-block">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="data-summary">
            <p>Showing <?php echo count($logs); ?> of <?php echo $totalRecords; ?> interactions</p>
        </div>
        
        <?php if (count($logs) > 0): ?>
        <div class="logs-container">
            <?php foreach ($logs as $log): ?>
            <div class="log-entry">
                <div class="log-header">
                    <div class="log-meta">
                        <span class="datetime"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></span>
                        <span class="source-badge source-<?php echo $log['source']; ?>">
                            <?php echo strtoupper($log['source']); ?>
                        </span>
                        <?php if (!empty($log['ip_address'])): ?>
                        <span class="ip-address">IP: <?php echo sanitize($log['ip_address']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="log-body">
                    <div class="question">
                        <h4>Question:</h4>
                        <p><?php echo sanitize($log['question']); ?></p>
                    </div>
                    
                    <div class="answer">
                        <h4>Answer:</h4>
                        <p><?php echo nl2br(sanitize($log['answer'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <?php
            // Build URL for pagination links
            $paginationUrl = 'history.php?';
            if ($source !== 'all') $paginationUrl .= "source=$source&";
            if (!empty($dateFrom)) $paginationUrl .= "date_from=" . urlencode($dateFrom) . "&";
            if (!empty($dateTo)) $paginationUrl .= "date_to=" . urlencode($dateTo) . "&";
            if (!empty($search)) $paginationUrl .= "search=" . urlencode($search) . "&";
            $paginationUrl .= "page=%d";
            
            echo getPagination($currentPage, $totalPages, $paginationUrl);
            ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="no-records">
            <p>No interaction logs found matching your criteria.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>