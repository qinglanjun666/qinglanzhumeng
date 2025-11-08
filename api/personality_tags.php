<?php
/**
 * 性格标签API
 * GET /api/personality_tags - 获取 personality_tags 表中的所有标签
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

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('数据库连接失败');
    }

    $query = "SELECT id, tag_name, description FROM personality_tags ORDER BY id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $tags = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tags[] = array(
            'id' => (int)$row['id'],
            'tag_name' => $row['tag_name'],
            'description' => $row['description']
        );
    }

    http_response_code(200);
    echo json_encode(array(
        'success' => true,
        'message' => '性格标签获取成功',
        'data' => array(
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
        'error_code' => 'PERSONALITY_TAGS_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);
}
?>