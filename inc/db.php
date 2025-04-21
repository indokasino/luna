<?php
/**
 * Database Connection Module
 * 
 * Provides PDO database connection and query helper functions
 */

// Prevent direct access
if (!defined('LUNA_ROOT')) {
    die('Access denied');
}

class Database {
    private static $instance = null;
    private $connection = null;
    
    // Database configuration
    private $host = 'localhost';
    private $dbname = 'admin_luna_gpt';
    private $username = 'admin_luna_gpt';
    private $password = 'MioSmile5566@@';
    private $charset = 'utf8mb4';
    
    /**
     * Constructor - Connect to database
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check the configuration.');
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     * 
     * @return Database Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO PDO connection object
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return PDOStatement Query result
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }
    
    /**
     * Fetch a single row
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|false Row data or false if not found
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError('FetchOne error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Rows data
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('FetchAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Insert data and return last insert ID
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int|false Last insert ID or false on failure
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->logError('Insert error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update data and return affected rows
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int|false Affected rows or false on failure
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Update error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete data and return affected rows
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int|false Affected rows or false on failure
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count rows
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int|false Row count or false on failure
     */
    public function count($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError('Count error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log error to file
     * 
     * @param string $message Error message
     */
    private function logError($message) {
        $logDir = LUNA_ROOT . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/db-error-' . date('Y-m-d') . '.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(
            $logFile,
            "[{$timestamp}] {$message}" . PHP_EOL,
            FILE_APPEND
        );
    }
}

/**
 * Get database instance
 * 
 * @return Database Database instance
 */
function db() {
    return Database::getInstance();
}