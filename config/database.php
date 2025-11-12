<?php
/**
 * 数据库连接配置
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'huilanweb';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(Throwable $exception) {
            // 覆盖所有连接异常（包含未安装PDO扩展、PDOException等），避免接口抛 500
            error_log("DB connection error: " . $exception->getMessage());
            $this->conn = null;
        }

        return $this->conn;
    }
}
?>