<?php
/**
 * API路由入口文件
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie");
header("Access-Control-Allow-Credentials: true");

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 获取请求路径
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
// 更健壮的前缀处理：若包含 /api/ 则截取其后路径；否则尝试移除固定前缀
$pos = strpos($path, '/api/');
if ($pos !== false) {
    $path = substr($path, $pos + strlen('/api/'));
} else {
    $path = str_replace('/huilanweb/api/', '', $path);
}

// 解析路径参数
$path_parts = explode('/', trim($path, '/'));

// 路由处理
switch ($path_parts[0]) {
    case 'universities':
        if (isset($path_parts[1]) && is_numeric($path_parts[1])) {
            if (isset($path_parts[2]) && $path_parts[2] === 'like') {
                // 点赞 API: /api/universities/{id}/like
                $_GET['id'] = $path_parts[1];
                include_once 'like.php';
            } elseif (isset($path_parts[2]) && $path_parts[2] === 'studying') {
                // 在读标记与统计 API: /api/universities/{id}/studying
                $_GET['id'] = $path_parts[1];
                include_once 'university_studying.php';
            } elseif (isset($path_parts[2]) && $path_parts[2] === 'vote') {
                // 投票 API: /api/universities/{id}/vote
                $_GET['id'] = $path_parts[1];
                include_once 'vote.php';
            } else {
                // 大学详情 API: /api/universities/{id}
                $_GET['id'] = $path_parts[1];
                include_once 'university_detail.php';
            }
        } else {
            // 大学列表 API: /api/universities
            include_once 'universities.php';
        }
        break;
    
    case 'universities_basic':
        // 大学基础信息 API: /api/universities_basic
        include_once 'universities_basic.php';
        break;
    
    case 'personality_tags':
        // 性格标签 API: /api/personality_tags
        include_once 'personality_tags.php';
        break;
    
    case 'university':
        // 大学关联标签与标签点赞
        if (isset($path_parts[1]) && is_numeric($path_parts[1]) && isset($path_parts[2]) && $path_parts[2] === 'tags') {
            // /api/university/{id}/tags 或 /api/university/{id}/tags/{tag_id}/like
            $_GET['id'] = $path_parts[1];
            if (isset($path_parts[3]) && is_numeric($path_parts[3]) && isset($path_parts[4]) && $path_parts[4] === 'like') {
                $_GET['tag_id'] = $path_parts[3];
                include_once 'university_tag_like.php';
            } else {
                include_once 'university_tags.php';
            }
        } else {
            http_response_code(404);
            echo json_encode(array(
                "message" => "University endpoint not found",
                "available_endpoints" => array(
                    "GET /api/university/{id}/tags" => "获取指定大学的性格标签",
                    "POST /api/university/{id}/tags/{tag_id}/like" => "为大学的某个性格标签点赞"
                )
            ));
        }
        break;
    
    case 'admin':
        // 管理端导入: /api/admin/import/universities
        if (isset($path_parts[1]) && $path_parts[1] === 'import' && isset($path_parts[2]) && $path_parts[2] === 'universities') {
            include_once 'admin_import_universities.php';
        }
        // 管理端导出埋点：/api/admin/analytics/export
        elseif (isset($path_parts[1]) && $path_parts[1] === 'analytics' && isset($path_parts[2]) && $path_parts[2] === 'export') {
            include_once 'admin_analytics_export.php';
        }
        else {
            http_response_code(404);
            echo json_encode(array(
                "message" => "Admin endpoint not found",
                "available_endpoints" => array(
                    "POST /api/admin/import/universities" => "导入或更新大学数据（CSV）",
                    "GET /api/admin/analytics/export" => "导出基础埋点事件（CSV）"
                )
            ));
        }
        break;
    
    case 'mood_types':
        // 气质类型 API: /api/mood_types
        include_once 'mood_types.php';
        break;
    
    case 'assessment':
        if (isset($path_parts[1])) {
            switch ($path_parts[1]) {
                case 'questions':
                    // 获取测评题目 API: /api/assessment/questions
                    include_once 'assessment_questions.php';
                    break;
                case 'submit':
                    // 提交测评答案 API: /api/assessment/submit
                    include_once 'assessment_submit.php';
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(array(
                        "message" => "Assessment endpoint not found",
                        "available_endpoints" => array(
                            "GET /api/assessment/questions" => "获取测评题目",
                            "POST /api/assessment/submit" => "提交测评答案"
                        )
                    ));
                    break;
            }
        } else {
            http_response_code(404);
            echo json_encode(array(
                "message" => "Assessment endpoint not specified",
                "available_endpoints" => array(
                    "GET /api/assessment/questions" => "获取测评题目",
                    "POST /api/assessment/submit" => "提交测评答案"
                )
            ));
        }
        break;
    
    default:
        http_response_code(404);
        echo json_encode(array(
            "message" => "Endpoint not found",
            "available_endpoints" => array(
                "GET /api/universities" => "获取大学列表",
                "GET /api/universities/{id}" => "获取大学详情",
                "POST /api/universities/{id}/like" => "点赞大学",
                "POST /api/universities/{id}/vote" => "投票选择大学气质",
                "GET /api/universities_basic" => "获取大学基础信息列表",
                "GET /api/personality_tags" => "获取性格标签列表",
                "GET /api/university/{id}/tags" => "获取指定大学的性格标签",
                "GET /api/mood_types" => "获取气质类型列表",
                "GET /api/assessment/questions" => "获取测评题目",
                "POST /api/assessment/submit" => "提交测评答案",
                "POST /api/admin/import/universities" => "导入或更新大学数据（CSV）",
                "POST /api/admin/import/questions" => "导入或更新测评题库（JSON）",
                "GET /api/admin/analytics/export" => "导出基础埋点事件（CSV）"
            )
        ));
        break;
}
?>