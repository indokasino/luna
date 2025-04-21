<?php
/**
 * Admin Header Include
 * 
 * Common header for all admin pages
 */

// Prevent direct access
if (!defined('LUNA_ROOT')) {
    die('Access denied');
}

// Get current username
$currentUsername = getCurrentUsername();
?>
<header class="admin-header">
    <div class="container">
        <div class="header-left">
            <div class="logo">
                <a href="index.php">Luna Chatbot</a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Q&A Management</a></li>
                    <li><a href="review.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'review.php' ? 'active' : ''; ?>">GPT Reviews</a></li>
                    <li><a href="history.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'history.php' ? 'active' : ''; ?>">Interaction Logs</a></li>
                    <li><a href="links.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'links.php' ? 'active' : ''; ?>">Quick Links</a></li>
                    <li><a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="header-right">
            <div class="user-menu">
                <span class="username"><?php echo sanitize($currentUsername); ?></span>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </div>
    </div>
</header>