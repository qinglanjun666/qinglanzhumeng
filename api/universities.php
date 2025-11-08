<?php
/**
 * 大学列表API端点
 * GET /api/universities
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 引入必要的文件
include_once '../config/database.php';
include_once '../models/University.php';

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

try {
    // 获取数据库连接
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed"));
        exit();
    }

    // 创建大学对象
    $university = new University($db);

    // 获取查询参数
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 20;
    $mood_type = isset($_GET['mood_type']) ? trim($_GET['mood_type']) : null;
    $q = isset($_GET['q']) ? trim($_GET['q']) : null;
    $random = isset($_GET['random']) ? intval($_GET['random']) : 0;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : $per_page;

    // 验证mood_type参数（如果提供）
    if ($mood_type && !empty($mood_type)) {
        // 验证mood_type是否存在
        $check_query = "SELECT COUNT(*) as count FROM mood_types WHERE slug = :slug";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':slug', $mood_type);
        $check_stmt->execute();
        $result = $check_stmt->fetch();
        
        if ($result['count'] == 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid mood_type parameter"));
            exit();
        }
    }

    // 随机推荐模式：每日随机选择固定数量大学
    if ($random === 1) {
        $result = $university->getRandomUniversities($limit);
    } else {
        // 获取大学列表
        $result = $university->getUniversities($page, $per_page, $mood_type, $q);
    }

    // 返回成功响应
    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    // 错误处理
    http_response_code(500);
    echo json_encode(array(
        "message" => "Internal server error",
        "error" => $e->getMessage()
    ));
}
?>