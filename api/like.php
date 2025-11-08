<?php
/**
 * 大学点赞API端点
 * POST /api/universities/{id}/like
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie");
header("Access-Control-Allow-Credentials: true");

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

// 引入必要的文件
include_once '../config/database.php';
include_once '../models/University.php';
include_once '../models/Analytics.php';

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

    // 获取URL中的ID参数
    $university_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // 验证ID参数
    if ($university_id <= 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid university ID"));
        exit();
    }

    // 检查大学是否存在
    $university_exists = $university->getUniversityById($university_id);
    if (!$university_exists) {
        http_response_code(404);
        echo json_encode(array("message" => "University not found"));
        exit();
    }

    // 获取客户端IP地址
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip_address = $_SERVER['HTTP_X_REAL_IP'];
    }

    // 获取或生成客户端ID
    $client_id = null;
    
    // 1. 首先检查请求体中的client_id
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['client_id']) && !empty($input['client_id'])) {
        $client_id = $input['client_id'];
    }
    
    // 2. 检查Cookie中的hj_client_id
    if (!$client_id && isset($_COOKIE['hj_client_id'])) {
        $client_id = $_COOKIE['hj_client_id'];
    }
    
    // 3. 生成新的客户端ID
    if (!$client_id) {
        $client_id = $university->generateClientId();
        
        // 设置Cookie（有效期30天）
        setcookie('hj_client_id', $client_id, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    // 检查是否已经点赞过
    $already_liked = $university->hasUserLiked($university_id, $client_id);
    
    if ($already_liked) {
        // 已经点赞过，返回当前点赞数
        $like_count = $university->getLikeCount($university_id);
        
        http_response_code(200);
        echo json_encode(array(
            "message" => "already liked",
            "like_count" => $like_count,
            "client_id" => $client_id,
            "already_liked" => true
        ));
        exit();
    }

    // 添加点赞记录
    $like_added = $university->addLike($university_id, $client_id, $ip_address);
    
    if (!$like_added) {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to add like"));
        exit();
    }

    // 基础埋点：记录点赞事件 like
    try {
        $analytics = new Analytics($db);
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $meta = [];
        $analytics->logEvent('like', $university_id, $client_id, $ip_address, $ua, $meta);
    } catch (Exception $e) {
        // 忽略埋点异常
    }

    // 获取更新后的点赞数
    $like_count = $university->getLikeCount($university_id);

    // 返回成功响应
    http_response_code(200);
    echo json_encode(array(
        "message" => "Like added successfully",
        "like_count" => $like_count,
        "client_id" => $client_id,
        "already_liked" => false
    ));

} catch (Exception $e) {
    // 错误处理
    http_response_code(500);
    echo json_encode(array(
        "message" => "Internal server error",
        "error" => $e->getMessage()
    ));
}
?>