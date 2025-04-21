<?php
/**
 * Q&A Page
 * 
 * Display all questions and answers from the database and logs
 * Suitable for exporting to Chatbot.com Knowledge Base
 */

// Define root path
define('LUNA_ROOT', dirname(__FILE__));

// Include required files
require_once LUNA_ROOT . '/inc/db.php';
require_once LUNA_ROOT . '/inc/functions.php';

// Initialize database
$db = db()->getConnection();

// Get source filter
$source = isset($_GET['source']) ? $_GET['source'] : 'all';

// Build query based on source
$params = [];
$sql = "";

if ($source === 'db') {
    // Get Q&A from prompt_data only
    $sql = "
        SELECT 
            question,
            answer,
            'db' AS source,
            created_at
        FROM 
            prompt_data
        WHERE 
            status = 'active'
    ";
} elseif ($source === 'gpt') {
    // Get Q&A from prompt_log only
    $sql = "
        SELECT 
            question,
            answer,
            source,
            created_at
        FROM 
            prompt_log
        WHERE 
            source IN ('gpt-4.1', 'gpt-4o')
    ";
} else {
    // Get both sources - combine Q&A from prompt_data and prompt_log
    $sql = "
        (SELECT 
            question,
            answer,
            'db' AS source,
            created_at
        FROM 
            prompt_data
        WHERE 
            status = 'active')
        
        UNION ALL
        
        (SELECT 
            question,
            answer,
            source,
            created_at
        FROM 
            prompt_log
        WHERE 
            source IN ('gpt-4.1', 'gpt-4o'))
    ";
}

// Add ordering
$sql .= " ORDER BY created_at DESC";

// Count total records
$totalRecords = db()->count("SELECT COUNT(*) FROM ($sql) as combined", $params);

// Pagination
$recordsPerPage = 30;
$totalPages = ceil($totalRecords / $recordsPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Add limit to SQL
$sql .= " LIMIT $offset, $recordsPerPage";

// Execute query
$records = db()->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Chatbot - Q&A</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .filters {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .filter-options {
            display: flex;
            gap: 10px;
        }
        .filter-link {
            padding: 5px 15px;
            text-decoration: none;
            border-radius: 4px;
            background-color: #f1f1f1;
            color: #333;
        }
        .filter-link.active {
            background-color: #3498db;
            color: white;
        }
        .qna-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .qna-question {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
            color: #2c3e50;
        }
        .qna-answer {
            margin-bottom: 15px;
            white-space: pre-wrap;
        }
        .qna-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .source-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            color: white;
        }
        .source-db {
            background-color: #27ae60;
        }
        .source-gpt-4\.1 {
            background-color: #3498db;
        }
        .source-gpt-4o {
            background-color: #9b59b6;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            margin: 0 5px;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
        }
        .pagination a {
            background-color: #f1f1f1;
            color: #333;
        }
        .pagination a:hover {
            background-color: #ddd;
        }
        .pagination .current {
            background-color: #3498db;
            color: white;
        }
        .copy-section {
            margin: 30px 0;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .copy-btn {
            display: block;
            margin: 10px auto;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .copy-btn:hover {
            background-color: #2980b9;
        }
        .info-text {
            text-align: center;
            margin-top: 10px;
            color: #777;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 3px;
            white-space: pre-wrap;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>Luna Chatbot - Q&A Knowledge Base</h1>
    
    <div class="filters">
        <div class="filter-options">
            <a href="qna.php" class="filter-link <?php echo $source === 'all' ? 'active' : ''; ?>">All Sources</a>
            <a href="qna.php?source=db" class="filter-link <?php echo $source === 'db' ? 'active' : ''; ?>">Database Only</a>
            <a href="qna.php?source=gpt" class="filter-link <?php echo $source === 'gpt' ? 'active' : ''; ?>">GPT Only</a>
        </div>
        <div>
            <span>Total: <?php echo $totalRecords; ?> Q&A entries</span>
        </div>
    </div>
    
    <div class="copy-section">
        <p>Use this page to copy Q&A entries for your Chatbot.com Knowledge Base. You can filter by source and paginate through all entries.</p>
        <button id="copyAll" class="copy-btn">Copy All Q&A on This Page</button>
        <p class="info-text" id="copyStatus"></p>
    </div>
    
    <div class="qna-container">
        <?php foreach ($records as $record): ?>
        <div class="qna-card" data-question="<?php echo htmlspecialchars($record['question']); ?>" data-answer="<?php echo htmlspecialchars($record['answer']); ?>">
            <div class="qna-question">
                Question: <?php echo htmlspecialchars($record['question']); ?>
            </div>
            <div class="qna-answer">
                Answer: <?php echo nl2br(htmlspecialchars($record['answer'])); ?>
            </div>
            <div class="qna-meta">
                <div>
                    <span class="source-badge source-<?php echo $record['source']; ?>"><?php echo strtoupper($record['source']); ?></span>
                </div>
                <div>
                    Date: <?php echo date('Y-m-d', strtotime($record['created_at'])); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (count($records) == 0): ?>
    <div style="text-align: center; margin: 50px 0;">
        <p>No Q&A entries found for the selected filter.</p>
    </div>
    <?php endif; ?>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        // Build URL for pagination links
        $paginationUrl = 'qna.php?';
        if ($source !== 'all') $paginationUrl .= "source=$source&";
        $paginationUrl .= "page=%d";
        
        echo getPagination($currentPage, $totalPages, $paginationUrl);
        ?>
    </div>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyAllBtn = document.getElementById('copyAll');
        const copyStatus = document.getElementById('copyStatus');
        
        copyAllBtn.addEventListener('click', function() {
            const qnaCards = document.querySelectorAll('.qna-card');
            let copyText = '';
            
            qnaCards.forEach(function(card) {
                const question = card.dataset.question;
                const answer = card.dataset.answer;
                
                copyText += 'Question: ' + question + '\n';
                copyText += 'Answer: ' + answer + '\n\n';
            });
            
            // Create temporary textarea to copy from
            const textarea = document.createElement('textarea');
            textarea.value = copyText;
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                copyStatus.textContent = 'All Q&A copied to clipboard!';
                setTimeout(() => {
                    copyStatus.textContent = '';
                }, 3000);
            } catch (err) {
                copyStatus.textContent = 'Failed to copy: ' + err;
            }
            
            document.body.removeChild(textarea);
        });
    });
    </script>
</body>
</html>