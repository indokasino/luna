<?php
// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Reset Admin Password</h1>";

// Connect directly to the database
// Replace these with your actual database credentials
$host = 'localhost'; 
$dbname = 'admin_luna_gpt';
$username = 'admin_luna_gpt';
$password = 'MioSmile5566@@';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // New password
    $newPassword = 'admin123';
    
    // Hash password - simple and reliable method
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // First try to update existing admin
    $stmt = $conn->prepare("UPDATE admin SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hashedPassword]);
    
    $rowCount = $stmt->rowCount();
    
    if ($rowCount > 0) {
        echo "<p style='color: green;'>Success! Admin password has been reset.</p>";
    } else {
        // If no rows were updated, try to insert a new admin
        $stmt = $conn->prepare("INSERT INTO admin (username, password_hash) VALUES ('admin', ?)");
        $stmt->execute([$hashedPassword]);
        
        echo "<p style='color: green;'>New admin user created successfully!</p>";
    }
    
    echo "<p>Username: <strong>admin</strong></p>";
    echo "<p>Password: <strong>$newPassword</strong></p>";
    echo "<p><a href='admin/login.php'>Go to Admin Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>