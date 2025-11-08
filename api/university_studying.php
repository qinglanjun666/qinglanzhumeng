<?php
/**
 * 大学在读标记与统计 API
 * GET /api/universities/{id}/studying  -> 返回在读总数与当前客户端在读状态
 * POST /api/universities/{id}/studying -> 设置当前客户端在读状态（幂等），返回最新统计
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        http_response_code(500);
        echo json_encode(array('message' => 'Database connection failed'));
        exit();
    }

    $university_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($university_id <= 0) {
        http_response_code(400);
        echo json_encode(array('message' => 'Invalid university ID'));
        exit();
    }

    // 尝试查询统计，若表不存在或查询失败则兜底为0
    $getStats = function($db, $uid) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) AS c FROM university_studying WHERE university_id = :uid AND studying = 1");
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();
            $cnt = (int)$stmt->fetch(PDO::FETCH_ASSOC)['c'];
            return $cnt;
        } catch (Exception $e) {
            return 0;
        }
    };

    // 获取客户端ID（GET不生成，POST若无则生成并写Cookie）
    $resolveClientId = function($allowGenerate) {
        $client_id = null;
        $inputRaw = file_get_contents('php://input');
        $input = null;
        if (!empty($inputRaw)) {
            $tmp = json_decode($inputRaw, true);
            if (is_array($tmp)) $input = $tmp;
        }
        if ($input && isset($input['client_id']) && !empty($input['client_id'])) {
            $client_id = $input['client_id'];
        }
        if (!$client_id && isset($_COOKIE['hj_client_id'])) {
            $client_id = $_COOKIE['hj_client_id'];
        }
        if (!$client_id && $allowGenerate) {
            $client_id = 'hj_' . bin2hex(random_bytes(6));
            setcookie('hj_client_id', $client_id, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        return $client_id;
    };

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $client_id = $resolveClientId(false);
        $studying_count = $getStats($db, $university_id);
        $is_studying = false;
        if ($client_id) {
            try {
                $stmt = $db->prepare("SELECT studying FROM university_studying WHERE university_id = :uid AND client_id = :cid LIMIT 1");
                $stmt->bindValue(':uid', $university_id, PDO::PARAM_INT);
                $stmt->bindValue(':cid', $client_id, PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $is_studying = $row ? ((int)$row['studying'] === 1) : false;
            } catch (Exception $e) {
                $is_studying = false;
            }
        }
        http_response_code(200);
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'university_id' => $university_id,
                'studying_count' => $studying_count,
                'is_studying' => $is_studying
            )
        ));
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $client_id = $resolveClientId(true);
        if (!$client_id) {
            http_response_code(500);
            echo json_encode(array('message' => 'Failed to resolve client id'));
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $studying = 1;
        if (is_array($input)) {
            if (isset($input['studying'])) {
                $studying = ($input['studying'] ? 1 : 0);
            }
        }

        // 尝试创建或更新记录（表不存在时返回兜底响应而非500）
        try {
            // 确保表存在（若不存在，抛异常被捕获并走兜底）
            $db->query("CREATE TABLE IF NOT EXISTS university_studying (
                id INT AUTO_INCREMENT PRIMARY KEY,
                university_id INT NOT NULL,
                client_id VARCHAR(64) NOT NULL,
                studying TINYINT(1) NOT NULL DEFAULT 1,
                ip_address VARCHAR(64) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_uc (university_id, client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 获取IP（用于记录）
            $ip_address = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $ip_address = $_SERVER['HTTP_X_REAL_IP'];
            }

            // UPSERT 当前客户端在读状态
            $stmt = $db->prepare("INSERT INTO university_studying (university_id, client_id, studying, ip_address)
                VALUES (:uid, :cid, :studying, :ip)
                ON DUPLICATE KEY UPDATE studying = VALUES(studying), ip_address = VALUES(ip_address)");
            $stmt->bindValue(':uid', $university_id, PDO::PARAM_INT);
            $stmt->bindValue(':cid', $client_id, PDO::PARAM_STR);
            $stmt->bindValue(':studying', $studying, PDO::PARAM_INT);
            $stmt->bindValue(':ip', $ip_address, PDO::PARAM_STR);
            $ok = $stmt->execute();
            if (!$ok) {
                throw new Exception('Failed to set studying status');
            }

            $studying_count = $getStats($db, $university_id);
            http_response_code(200);
            echo json_encode(array(
                'message' => 'Studying status updated',
                'client_id' => $client_id,
                'studying_count' => $studying_count,
                'is_studying' => ($studying === 1)
            ));
            exit();
        } catch (Exception $e) {
            // 兜底：表不存在或SQL异常，不致命，返回统计为0
            http_response_code(200);
            echo json_encode(array(
                'message' => 'Fallback: studying table not available',
                'client_id' => $client_id,
                'studying_count' => 0,
                'is_studying' => ($studying === 1),
                'error' => $e->getMessage()
            ));
            exit();
        }
    }

    // 其他方法不允许
    http_response_code(405);
    echo json_encode(array('message' => 'Method not allowed'));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('message' => 'Internal server error', 'error' => $e->getMessage()));
}
?>