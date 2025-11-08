<?php
/**
 * 测评提交API
 * 处理用户答案，返回气质类型和匹配大学
 */

// 设置CORS头
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'message' => 'Only POST method is supported'
    ]);
    exit();
}

try {
    // 引入依赖
    require_once '../config/database.php';
    require_once '../models/Assessment.php';
    require_once '../models/Analytics.php';
    require_once '../models/University.php';
    
    // 创建数据库连接
    $database = new Database();
    $pdo = $database->getConnection();
    
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 验证输入数据
    if (!$input || !isset($input['answers'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid input',
            'message' => 'Missing answers array in request body'
        ]);
        exit();
    }
    
    $answers = $input['answers'];
    
    // 验证答案数组
    if (!is_array($answers) || empty($answers)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid answers',
            'message' => 'Answers must be a non-empty array of option IDs'
        ]);
        exit();
    }
    
    // 验证答案数量（新规则：必须为5）
    if (count($answers) !== 5) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid answer count',
            'message' => 'Expected 5 answers, got ' . count($answers)
        ]);
        exit();
    }
    
    // 创建Assessment实例
    $assessment = new Assessment($pdo);
    
    // 处理测评
    $result = $assessment->processAssessment($answers);
    
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Assessment processing failed',
            'message' => $result['error']
        ]);
        exit();
    }
    
    // 构建响应数据
    $response = [
        'success' => true,
        'message' => 'Assessment completed successfully',
        'user_mood' => [
            'id' => (int)$result['user_mood']['id'],
            'slug' => $result['user_mood']['slug'],
            'name' => $result['user_mood']['name'],
            'short_desc' => $result['user_mood']['short_desc'],
            'color' => $result['user_mood']['color']
        ],
        'matched_universities' => array_map(function($university) {
            return [
                'id' => (int)$university['id'],
                'name' => $university['name'],
                'province' => $university['province'],
                'city' => $university['city'],
                'type' => $university['type'],
                'mood_type_id' => (int)$university['mood_type_id'],
                'keywords' => $university['keywords'],
                'one_line' => $university['one_line'],
                'logo_url' => $university['logo_url'],
                'mood' => [
                    'slug' => $university['mood_slug'],
                    'name' => $university['mood_name'],
                    'desc' => $university['mood_desc'],
                    'color' => $university['mood_color']
                ],
                'match_type' => $university['match_type']
            ];
        }, $result['matched_universities']),
        'statistics' => [
            'total_answers' => $result['statistics']['total_answers'],
            'matched_count' => $result['statistics']['matched_count'],
            'primary_matches' => $result['statistics']['primary_matches'],
            'mood_scores' => array_map(function($score) {
                return [
                    'mood_type_id' => (int)$score['mood_type_id'],
                    'total_weight' => (int)$score['total_weight']
                ];
            }, $result['statistics']['mood_scores'])
        ]
    ];
    
    // 基础埋点：记录测评完成事件 quiz_completed
    try {
        $analytics = new Analytics($pdo);
        $universityModel = new University($pdo);

        // 获取或生成client_id
        $client_id = null;
        $inputClientId = isset($input['client_id']) ? $input['client_id'] : null;
        if ($inputClientId) {
            $client_id = $inputClientId;
        } elseif (isset($_COOKIE['hj_client_id']) && !empty($_COOKIE['hj_client_id'])) {
            $client_id = $_COOKIE['hj_client_id'];
        } else {
            $client_id = $universityModel->generateClientId();
        }
        // 设置Cookie（30天有效）
        setcookie('hj_client_id', $client_id, time() + (30 * 24 * 60 * 60), '/', '', false, true);

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        $meta = [
            'mood_type_id' => (int)$result['user_mood']['id'],
            'answers_count' => (int)$result['statistics']['total_answers'],
            'mood_scores' => array_map(function($score) {
                return [
                    'mood_type_id' => (int)$score['mood_type_id'],
                    'total_weight' => (int)$score['total_weight']
                ];
            }, $result['statistics']['mood_scores'])
        ];
        // entity_id使用结果气质类型ID
        $analytics->logEvent('quiz_completed', (int)$result['user_mood']['id'], $client_id, $ip, $ua, $meta);
    } catch (Exception $e) {
        // 埋点失败不影响正常返回
    }

    // 返回成功响应
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Failed to process assessment'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>