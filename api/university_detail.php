<?php
/**
 * 大学详情API端点
 * GET /api/universities/{id}
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
        // 数据库不可用时的降级：返回占位详情，避免页面中断
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $fallback_detail = [
            'id' => $id,
            'name' => '示例大学',
            'province' => null,
            'city' => null,
            'type' => '综合',
            'one_line' => '占位数据（数据库暂不可用）',
            'keywords' => null,
            'logo_url' => null,
            'mood_type' => [
                'id' => 0,
                'slug' => 'rational_creator',
                'name' => '理性创造者',
                'short_desc' => '理性、创新、工程气质（占位）',
                'color' => '#4F46E5'
            ],
            'like_count' => 0,
            'vote_distribution' => [
                'rational_creator' => 0,
                'practical_achiever' => 0,
                'artistic_explorer' => 0,
                'empathetic_collaborator' => 0
            ]
        ];
        http_response_code(200);
        echo json_encode($fallback_detail, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 创建大学对象
    $university = new University($db);

    // 获取URL中的ID参数
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // 验证ID参数
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid university ID"));
        exit();
    }

    // 获取大学详情
    $university_detail = $university->getUniversityDetail($id);

    if (!$university_detail) {
        http_response_code(404);
        echo json_encode(array("message" => "University not found"));
        exit();
    }

    // 基础埋点：记录大学详情访问 university_view
    try {
        require_once '../models/Analytics.php';
        $analytics = new Analytics($db);
        // 获取或生成client_id
        $client_id = isset($_COOKIE['hj_client_id']) && !empty($_COOKIE['hj_client_id']) ? $_COOKIE['hj_client_id'] : $university->generateClientId();
        // 设置Cookie（30天有效）
        setcookie('hj_client_id', $client_id, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        // IP与UA
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $meta = [
            'mood_type_id' => isset($university_detail['mood_type']['id']) ? (int)$university_detail['mood_type']['id'] : null
        ];
        $analytics->logEvent('university_view', $id, $client_id, $ip, $ua, $meta);
    } catch (Exception $e) {
        // 忽略埋点异常
    }

    // 返回成功响应
    http_response_code(200);
    echo json_encode($university_detail);

} catch (Exception $e) {
    // 错误处理：返回占位详情，避免页面中断
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $fallback_detail = [
        'id' => $id,
        'name' => '示例大学',
        'province' => null,
        'city' => null,
        'type' => '综合',
        'one_line' => '占位数据（后端异常）',
        'keywords' => null,
        'logo_url' => null,
        'mood_type' => [
            'id' => 0,
            'slug' => 'rational_creator',
            'name' => '理性创造者',
            'short_desc' => '理性、创新、工程气质（占位）',
            'color' => '#4F46E5'
        ],
        'like_count' => 0,
        'vote_distribution' => [
            'rational_creator' => 0,
            'practical_achiever' => 0,
            'artistic_explorer' => 0,
            'empathetic_collaborator' => 0
        ]
    ];
    http_response_code(200);
    echo json_encode($fallback_detail, JSON_UNESCAPED_UNICODE);
}
?>