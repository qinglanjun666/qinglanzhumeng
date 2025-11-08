<?php
/**
 * 基础埋点与统计模型
 */

class Analytics {
    private $conn;
    private $table_name = "analytics_events";

    public function __construct($db) {
        $this->conn = $db;
        $this->ensureTable();
    }

    /**
     * 记录事件
     * @param string $event_type 事件类型，例如 quiz_completed、university_view、like、vote、share_image
     * @param int|null $entity_id 关联实体ID（如大学ID、或结果气质ID）
     * @param string|null $client_id 客户端ID
     * @param string|null $ip IP地址
     * @param string|null $user_agent UA
     * @param array|null $meta 附加数据（将以JSON字符串存储）
     * @return bool 是否记录成功
     */
    public function logEvent($event_type, $entity_id = null, $client_id = null, $ip = null, $user_agent = null, $meta = null) {
        try {
            $sql = "INSERT INTO " . $this->table_name . " (event_type, entity_id, client_id, ip, user_agent, created_at, meta) VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $this->conn->prepare($sql);
            $meta_json = $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
            $stmt->bindParam(1, $event_type);
            $stmt->bindParam(2, $entity_id);
            $stmt->bindParam(3, $client_id);
            $stmt->bindParam(4, $ip);
            $stmt->bindParam(5, $user_agent);
            $stmt->bindParam(6, $meta_json);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    private function ensureTable() {
        try {
            $stmt = $this->conn->prepare("SHOW TABLES LIKE '" . $this->table_name . "'");
            $stmt->execute();
            $exists = $stmt->fetch();
            if ($exists) {
                return;
            }
            $sql = "CREATE TABLE " . $this->table_name . " (
                id INT PRIMARY KEY AUTO_INCREMENT,
                event_type VARCHAR(50) NOT NULL,
                entity_id INT NULL,
                client_id VARCHAR(128) NULL,
                ip VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                meta TEXT NULL
            )";
            $this->conn->exec($sql);
            $this->conn->exec("CREATE INDEX idx_analytics_event_type ON " . $this->table_name . "(event_type)");
            $this->conn->exec("CREATE INDEX idx_analytics_entity ON " . $this->table_name . "(entity_id)");
            $this->conn->exec("CREATE INDEX idx_analytics_client ON " . $this->table_name . "(client_id)");
            $this->conn->exec("CREATE INDEX idx_analytics_created_at ON " . $this->table_name . "(created_at)");
        } catch (Exception $e) {
            // ignore
        }
    }
}
?>