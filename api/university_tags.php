<?php
/**
 * 大学关联性格标签API
 * GET /api/university/{id}/tags - 获取某大学关联的性格标签
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array(
        'success' => false,
        'message' => 'Method not allowed. Only GET requests are supported.'
    ));
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id || $id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => '无效的大学ID'
    ), JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('数据库连接失败');
    }

    // 检查大学是否存在（允许存在于 universities 或 universities_basic 中任意一个）
    $exists = false;
    $check1 = $db->prepare("SELECT COUNT(*) AS c FROM universities WHERE id = :id");
    $check1->bindValue(':id', $id, PDO::PARAM_INT);
    $check1->execute();
    $exists = ((int)$check1->fetch(PDO::FETCH_ASSOC)['c']) > 0;

    if (!$exists) {
        $check2 = $db->prepare("SELECT COUNT(*) AS c FROM universities_basic WHERE id = :id");
        $check2->bindValue(':id', $id, PDO::PARAM_INT);
        $check2->execute();
        $exists = ((int)$check2->fetch(PDO::FETCH_ASSOC)['c']) > 0;
    }

    if (!$exists) {
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'message' => '大学不存在'
        ), JSON_UNESCAPED_UNICODE);
        exit();
    }

    $tags = [];

    // 优先使用带热度的查询；如表不存在则兜底为不带热度的查询
    try {
        $sql = "SELECT t.id, t.tag_name, t.description, COALESCE(l.like_count, 0) AS like_count
                FROM university_personality_tags ut
                JOIN personality_tags t ON ut.tag_id = t.id
                LEFT JOIN (
                    SELECT tag_id, COUNT(*) AS like_count
                    FROM university_tag_likes
                    WHERE university_id = :id
                    GROUP BY tag_id
                ) l ON l.tag_id = t.id
                WHERE ut.university_id = :id
                ORDER BY COALESCE(l.like_count, 0) DESC, t.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = array(
                'id' => (int)$row['id'],
                'tag_name' => $row['tag_name'],
                'description' => $row['description'],
                'like_count' => (int)$row['like_count']
            );
        }
    } catch (Exception $e) {
        // 兜底：不带热度字段的查询，避免 university_tag_likes 表缺失导致 500
        $sql2 = "SELECT t.id, t.tag_name, t.description
                 FROM university_personality_tags ut
                 JOIN personality_tags t ON ut.tag_id = t.id
                 WHERE ut.university_id = :id
                 ORDER BY t.id ASC";
        $stmt2 = $db->prepare($sql2);
        $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt2->execute();
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = array(
                'id' => (int)$row['id'],
                'tag_name' => $row['tag_name'],
                'description' => $row['description'],
                'like_count' => 0
            );
        }
    }

    http_response_code(200);
    echo json_encode(array(
        'success' => true,
        'message' => '大学关联性格标签获取成功',
        'data' => array(
            'university_id' => $id,
            'tags' => $tags,
            'total_tags' => count($tags)
        ),
        'timestamp' => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => '服务器内部错误: ' . $e->getMessage(),
        'error_code' => 'UNIVERSITY_TAGS_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);
}
?>