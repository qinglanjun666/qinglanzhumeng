<?php
/**
 * 气质类型API
 * GET /api/mood_types - 获取所有气质类型信息
 */

// 引入数据库连接和模型
require_once '../config/database.php';

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array(
        "success" => false,
        "message" => "Method not allowed. Only GET requests are supported."
    ));
    exit();
}

try {
    // 创建数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("数据库连接失败");
    }
    
    // 查询气质类型基本信息
    $query = "SELECT 
                mt.id,
                mt.slug,
                mt.name,
                mt.short_desc,
                mt.color,
                mt.created_at
              FROM mood_types mt 
              ORDER BY mt.id ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $mood_types = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // 统计每个气质类型的大学数量
        $uni_count_query = "SELECT COUNT(*) as count 
                           FROM universities u 
                           WHERE u.mood_type_id = :mood_id";
        $uni_stmt = $db->prepare($uni_count_query);
        $uni_stmt->bindParam(':mood_id', $row['id']);
        $uni_stmt->execute();
        $uni_count = $uni_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // 统计选择该气质类型的用户数量（从测评结果中统计）
        // 注意：assessment_results表可能不存在，先检查表是否存在
        $user_count = 0;
        try {
            $user_count_query = "SELECT COUNT(DISTINCT ar.user_id) as count 
                                FROM assessment_results ar 
                                WHERE ar.mood_type_id = :mood_id";
            $user_stmt = $db->prepare($user_count_query);
            $user_stmt->bindParam(':mood_id', $row['id']);
            $user_stmt->execute();
            $user_count = $user_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            // 如果assessment_results表不存在，使用默认值0
            $user_count = 0;
        }
        
        $mood_types[] = array(
            "id" => (int)$row['id'],
            "slug" => $row['slug'],
            "name" => $row['name'],
            "short_desc" => $row['short_desc'],
            "color" => $row['color'],
            "university_count" => (int)$uni_count,
            "user_count" => (int)$user_count,
            "created_at" => $row['created_at']
        );
    }
    
    // 获取总体统计信息
    $total_users = 0;
    try {
        $total_users_query = "SELECT COUNT(DISTINCT user_id) as count FROM assessment_results";
        $total_stmt = $db->prepare($total_users_query);
        $total_stmt->execute();
        $total_users = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // 如果assessment_results表不存在，使用默认值0
        $total_users = 0;
    }
    
    $total_universities_query = "SELECT COUNT(*) as count FROM universities";
    $total_uni_stmt = $db->prepare($total_universities_query);
    $total_uni_stmt->execute();
    $total_universities = $total_uni_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 返回成功响应
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "气质类型数据获取成功",
        "data" => array(
            "mood_types" => $mood_types,
            "statistics" => array(
                "total_mood_types" => count($mood_types),
                "total_users" => (int)$total_users,
                "total_universities" => (int)$total_universities
            )
        ),
        "timestamp" => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 错误处理：提供降级数据，避免前端不可用
    error_log("Mood Types API Error: " . $e->getMessage());

    // 尝试读取本地降级数据 data/mood_types.json
    $fallback_list = null;
    $json_path = __DIR__ . '/../data/mood_types.json';
    if (file_exists($json_path)) {
        $raw = file_get_contents($json_path);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            // 兼容两种结构：直接数组或 { mood_types: [] }
            if (isset($decoded['mood_types']) && is_array($decoded['mood_types'])) {
                $fallback_list = $decoded['mood_types'];
            } else {
                $fallback_list = $decoded;
            }
        }
    }

    if (!$fallback_list) {
        // 内置四种气质类型的示例数据（与文档一致的slug）
        $fallback_list = array(
            array(
                "id" => 1,
                "slug" => "rational_creator",
                "name" => "理性创造型",
                "short_desc" => "逻辑思维强，善于创新",
                "color" => "#3B82F6",
                "university_count" => 0,
                "user_count" => 0,
                "created_at" => date('Y-m-d H:i:s')
            ),
            array(
                "id" => 2,
                "slug" => "artistic_explorer",
                "name" => "文艺探索型",
                "short_desc" => "审美与表达并重，善于探索",
                "color" => "#EF4444",
                "university_count" => 0,
                "user_count" => 0,
                "created_at" => date('Y-m-d H:i:s')
            ),
            array(
                "id" => 3,
                "slug" => "practical_achiever",
                "name" => "务实应用型",
                "short_desc" => "动手能力强，面向应用",
                "color" => "#10B981",
                "university_count" => 0,
                "user_count" => 0,
                "created_at" => date('Y-m-d H:i:s')
            ),
            array(
                "id" => 4,
                "slug" => "social_connector",
                "name" => "社交领导型",
                "short_desc" => "善于沟通组织，凝聚团队",
                "color" => "#F59E0B",
                "university_count" => 0,
                "user_count" => 0,
                "created_at" => date('Y-m-d H:i:s')
            )
        );
    }

    // 返回降级成功响应
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "返回示例数据（数据库暂不可用）",
        "data" => array(
            "mood_types" => $fallback_list,
            "statistics" => array(
                "total_mood_types" => count($fallback_list),
                "total_users" => 0,
                "total_universities" => 0
            )
        ),
        "timestamp" => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);
}
?>