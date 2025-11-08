<?php
/**
 * 测评题目管理API
 * 获取测评题目和选项数据
 */

// 设置CORS头
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'message' => 'Only GET method is supported'
    ]);
    exit();
}

try {
    // 引入数据库配置
    require_once '../config/database.php';
    
    // 创建数据库连接
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // 解析查询参数：默认随机返回5题；all=true 时返回全部
    $all = isset($_GET['all']) ? (strtolower(trim($_GET['all'])) === 'true') : false;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 5;
    
    if ($all) {
        $sql = "
            SELECT 
                q.id as question_id,
                q.question_text,
                q.question_order,
                o.id as option_id,
                o.option_text,
                o.option_order
            FROM assessment_questions q
            LEFT JOIN assessment_options o ON q.id = o.question_id
            WHERE q.is_active = TRUE
            ORDER BY q.question_order ASC, o.option_order ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 先随机抽取题目ID，再查询这些题目的选项
        $pickSql = "SELECT id FROM assessment_questions WHERE is_active = TRUE ORDER BY RAND() LIMIT ?";
        $pickStmt = $pdo->prepare($pickSql);
        $pickStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $pickStmt->execute();
        $pickedIds = array_map(function($r){ return (int)$r['id']; }, $pickStmt->fetchAll(PDO::FETCH_ASSOC));
        
        if (empty($pickedIds)) {
            $results = [];
        } else {
            // 构造占位符
            $placeholders = implode(',', array_fill(0, count($pickedIds), '?'));
            $sql = "
                SELECT 
                    q.id as question_id,
                    q.question_text,
                    q.question_order,
                    o.id as option_id,
                    o.option_text,
                    o.option_order
                FROM assessment_questions q
                LEFT JOIN assessment_options o ON q.id = o.question_id
                WHERE q.id IN ($placeholders)
                ORDER BY q.question_order ASC, o.option_order ASC
            ";
            $stmt = $pdo->prepare($sql);
            foreach ($pickedIds as $i => $qid) {
                $stmt->bindValue($i + 1, $qid, PDO::PARAM_INT);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // 组织数据结构
    $questions = [];
    $current_question = null;
    
    foreach ($results as $row) {
        $question_id = $row['question_id'];
        
        // 如果是新题目，创建题目结构
        if ($current_question === null || $current_question['id'] !== $question_id) {
            // 保存上一个题目
            if ($current_question !== null) {
                $questions[] = $current_question;
            }
            
            // 创建新题目
            $current_question = [
                'id' => $question_id,
                'question_text' => $row['question_text'],
                'question_order' => $row['question_order'],
                'options' => []
            ];
        }
        
        // 添加选项（如果存在）
        if ($row['option_id'] !== null) {
            $current_question['options'][] = [
                'id' => $row['option_id'],
                'option_text' => $row['option_text'],
                'option_order' => $row['option_order']
            ];
        }
    }
    
    // 添加最后一个题目
    if ($current_question !== null) {
        $questions[] = $current_question;
    }
    
    // 获取统计信息
    $stats_sql = "
        SELECT 
            (SELECT COUNT(*) FROM assessment_questions WHERE is_active = TRUE) as total_questions,
            (SELECT COUNT(*) FROM assessment_options o 
             JOIN assessment_questions q ON o.question_id = q.id 
             WHERE q.is_active = TRUE) as total_options
    ";
    
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // 返回结果
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Assessment questions retrieved successfully',
        'data' => [
            'questions' => $questions,
            'statistics' => [
                'total_questions' => (int)$stats['total_questions'],
                'total_options' => (int)$stats['total_options'],
                'returned_questions' => count($questions)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Failed to retrieve assessment questions'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>