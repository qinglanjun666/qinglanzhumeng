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
        // 数据库不可用时，返回后端占位推荐，避免首页中断
        $fallback = [
            [ 'id' => 101, 'name' => '清华大学', 'province' => '北京', 'city' => '北京', 'type' => '综合', 'mood_type_slug' => 'rational_creator', 'one_line' => '工程与计算机强势（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
            [ 'id' => 102, 'name' => '北京大学', 'province' => '北京', 'city' => '北京', 'type' => '综合', 'mood_type_slug' => 'rational_creator', 'one_line' => '综合实力卓越（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
            [ 'id' => 103, 'name' => '浙江大学', 'province' => '浙江', 'city' => '杭州', 'type' => '综合', 'mood_type_slug' => 'practical_achiever', 'one_line' => '创新与研究氛围浓厚（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
            [ 'id' => 104, 'name' => '上海交通大学', 'province' => '上海', 'city' => '上海', 'type' => '理工', 'mood_type_slug' => 'rational_creator', 'one_line' => '理工科优势明显（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
            [ 'id' => 105, 'name' => '中国科学技术大学', 'province' => '安徽', 'city' => '合肥', 'type' => '理工', 'mood_type_slug' => 'rational_creator', 'one_line' => '科研导向（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
            [ 'id' => 106, 'name' => '南京大学', 'province' => '江苏', 'city' => '南京', 'type' => '综合', 'mood_type_slug' => 'artistic_explorer', 'one_line' => '学术基础雄厚（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
        ];
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 6;
        $slice = array_slice($fallback, 0, $limit);
        http_response_code(200);
        echo json_encode([
            'data' => $slice,
            'total' => count($slice),
            'page' => 1,
            'per_page' => count($slice),
            'total_pages' => 1,
            'message' => '返回占位数据（数据库暂不可用）'
        ], JSON_UNESCAPED_UNICODE);
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
    // 错误处理：后端占位数据降级
    error_log('Universities API Error: ' . $e->getMessage());
    $fallback = [
        [ 'id' => 101, 'name' => '清华大学', 'province' => '北京', 'city' => '北京', 'type' => '综合', 'mood_type_slug' => 'rational_creator', 'one_line' => '工程与计算机强势（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
        [ 'id' => 102, 'name' => '北京大学', 'province' => '北京', 'city' => '北京', 'type' => '综合', 'mood_type_slug' => 'rational_creator', 'one_line' => '综合实力卓越（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
        [ 'id' => 103, 'name' => '浙江大学', 'province' => '浙江', 'city' => '杭州', 'type' => '综合', 'mood_type_slug' => 'practical_achiever', 'one_line' => '创新与研究氛围浓厚（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
        [ 'id' => 104, 'name' => '上海交通大学', 'province' => '上海', 'city' => '上海', 'type' => '理工', 'mood_type_slug' => 'rational_creator', 'one_line' => '理工科优势明显（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
        [ 'id' => 105, 'name' => '中国科学技术大学', 'province' => '安徽', 'city' => '合肥', 'type' => '理工', 'mood_type_slug' => 'rational_creator', 'one_line' => '科研导向（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
        [ 'id' => 106, 'name' => '南京大学', 'province' => '江苏', 'city' => '南京', 'type' => '综合', 'mood_type_slug' => 'artistic_explorer', 'one_line' => '学术基础雄厚（占位）', 'logo_url' => null, 'like_count' => 0, 'poll_counts' => 0 ],
    ];
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 6;
    $slice = array_slice($fallback, 0, $limit);
    http_response_code(200);
    echo json_encode([
        'data' => $slice,
        'total' => count($slice),
        'page' => 1,
        'per_page' => count($slice),
        'total_pages' => 1,
        'message' => '返回占位数据（数据库暂不可用）'
    ], JSON_UNESCAPED_UNICODE);
}
?>