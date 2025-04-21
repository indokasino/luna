<?php
/**
 * Index file - Redirects to admin panel
 */

// Define root path
define('LUNA_ROOT', __DIR__);

// Redirect to admin login
header('Location: admin/login.php');
exit;