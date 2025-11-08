<?php
/**
 * 大学投票API端点
 * POST /api/universities/{id}/vote
 */

// 设置CORS头
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "error" => "Method not allowed",
        "message" => "Only POST method is allowed"
    ]);
    exit();
}

// 引入必要的文件
require_once '../config/database.php';
require_once '../models/University.php';
require_once '../models/Analytics.php';

// 获取数据库连接
$database = new Database();
$db = $database->getConnection();

// 创建University对象
$university = new University($db);

// 获取大学ID
$university_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($university_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid university ID",
        "message" => "University ID must be a positive integer"
    ]);
    exit();
}

// 验证大学是否存在
$university_detail = $university->getUniversityDetail($university_id);
if (!$university_detail) {
    http_response_code(404);
    echo json_encode([
        "error" => "University not found",
        "message" => "University with ID {$university_id} does not exist"
    ]);
    exit();
}

// 获取请求体数据
$input = json_decode(file_get_contents("php://input"), true);

// 验证输入数据
if (!$input || !isset($input['mood_slug'])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid input",
        "message" => "mood_slug is required"
    ]);
    exit();
}

$mood_slug = trim($input['mood_slug']);

// 验证mood_slug
if (!$university->isValidMoodSlug($mood_slug)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid mood_slug",
        "message" => "The provided mood_slug is not valid"
    ]);
    exit();
}

// 获取或生成client_id
$client_id = null;

// 1. 从请求体获取client_id
if (isset($input['client_id']) && !empty($input['client_id'])) {
    $client_id = $input['client_id'];
}
// 2. 从Cookie获取client_id
elseif (isset($_COOKIE['hj_client_id']) && !empty($_COOKIE['hj_client_id'])) {
    $client_id = $_COOKIE['hj_client_id'];
}
// 3. 生成新的client_id
else {
    $client_id = $university->generateClientId();
}

// 设置Cookie（30天有效期）
setcookie('hj_client_id', $client_id, time() + (30 * 24 * 60 * 60), '/', '', false, true);

// 获取用户IP地址
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
    $ip_address = $_SERVER['HTTP_X_REAL_IP'];
}

// 执行投票操作
$vote_result = $university->addOrUpdateVote($university_id, $client_id, $mood_slug, $ip_address);

if (!$vote_result['success']) {
    http_response_code(500);
    echo json_encode([
        "error" => "Vote failed",
        "message" => $vote_result['message']
    ]);
    exit();
}

// 获取更新后的投票分布
$updated_university = $university->getUniversityDetail($university_id);
$vote_distribution = $updated_university['vote_distribution'];

// 获取用户当前的投票情况
$user_vote = $university->getUserVote($university_id, $client_id);

// 返回成功响应
$response = [
    "message" => $vote_result['message'],
    "client_id" => $client_id,
    "vote_distribution" => $vote_distribution,
    "user_vote" => [
        "mood_slug" => $user_vote['mood_slug'],
        "mood_name" => $user_vote['mood_name']
    ],
    "updated" => $vote_result['updated']
];

// 如果是重复投票同一个mood_type，添加额外信息
if (!$vote_result['updated'] && $vote_result['message'] === 'Vote already exists for this mood type') {
    $response["already_voted"] = true;
}

http_response_code(200);
echo json_encode($response);

// 基础埋点：记录投票事件 vote（在响应后尝试记录，不影响主流程）
try {
    $analytics = new Analytics($db);
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    $meta = [ 'mood_slug' => $mood_slug, 'updated' => (bool)$vote_result['updated'] ];
    $analytics->logEvent('vote', $university_id, $client_id, $ip_address, $ua, $meta);
} catch (Exception $e) {
    // 忽略埋点异常
}
?>