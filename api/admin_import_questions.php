<?php
/**
 * 管理端：导入/更新测评题库（JSON）
 * POST /api/admin/import/questions
 *
 * 认证：Header `X-Admin-Password` 或 form 字段 `password`
 * 负载：JSON（或表单字段 `json_text`）
 * 格式：
 * {
 *   "questions": [
 *     {
 *       "question_text": "你更偏好哪种学习方式？",
 *       "options": [
 *         {
 *           "option_text": "动手实践，项目驱动",
 *           "weights": { "rational_creator": 2, "innovative_pioneer": 1 }
 *         },
 *         {
 *           "option_text": "理论推导，深入研究",
 *           "weights": { "scholarly_thinker": 2, "rational_creator": 1 }
 *         }
 *       ]
 *     }
 *   ]
 * }
 *
 * 说明：`weights` 支持按气质类型 slug 设置权重，未提供的类型视为0。
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Admin-Password");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

require_once '../config/database.php';

// 简单认证：环境变量 HJ_ADMIN_PASSWORD 或默认值
$env_password = getenv('HJ_ADMIN_PASSWORD');
if ($env_password === false || $env_password === '') {
    $env_password = 'zxasqw123456';
}

$provided_password = null;
if (isset($_SERVER['HTTP_X_ADMIN_PASSWORD'])) {
    $provided_password = $_SERVER['HTTP_X_ADMIN_PASSWORD'];
}
if (!$provided_password && isset($_POST['password'])) {
    $provided_password = $_POST['password'];
}

if (!$provided_password || $provided_password !== $env_password) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized", "error" => "Invalid admin password"]);
    exit();
}

// 读取JSON负载
$raw = file_get_contents('php://input');
if (!$raw && isset($_POST['json_text'])) {
    $raw = $_POST['json_text'];
}

$payload = json_decode($raw, true);
if (!$payload || !isset($payload['questions']) || !is_array($payload['questions'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid payload: expect { questions: [] } JSON"]);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(["message" => "Database connection failed"]);
        exit();
    }

    // 查询 mood_types 映射
    $mtStmt = $pdo->prepare("SELECT id, slug FROM mood_types");
    $mtStmt->execute();
    $moodTypes = [];
    while ($row = $mtStmt->fetch(PDO::FETCH_ASSOC)) {
        $moodTypes[$row['slug']] = (int)$row['id'];
    }

    // 获取当前最大题目序号
    $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(question_order), 0) AS max_order FROM assessment_questions");
    $orderStmt->execute();
    $maxOrder = (int)$orderStmt->fetch(PDO::FETCH_ASSOC)['max_order'];

    $insertedQuestions = 0;
    $insertedOptions = 0;
    $insertedWeights = 0;
    $errors = [];

    $pdo->beginTransaction();

    foreach ($payload['questions'] as $qIndex => $q) {
        $qText = isset($q['question_text']) ? trim($q['question_text']) : '';
        $options = isset($q['options']) && is_array($q['options']) ? $q['options'] : [];
        if ($qText === '' || empty($options)) {
            $errors[] = [ 'index' => $qIndex, 'error' => 'Missing question_text or options' ];
            continue;
        }

        $maxOrder += 1;
        $qIns = $pdo->prepare("INSERT INTO assessment_questions (question_text, question_order, is_active) VALUES (?, ?, TRUE)");
        $qIns->bindValue(1, $qText);
        $qIns->bindValue(2, $maxOrder, PDO::PARAM_INT);
        $qIns->execute();
        $questionId = (int)$pdo->lastInsertId();
        $insertedQuestions++;

        // 插入选项及权重
        $optOrder = 0;
        foreach ($q['options'] as $opt) {
            $optText = isset($opt['option_text']) ? trim($opt['option_text']) : '';
            $weights = isset($opt['weights']) && is_array($opt['weights']) ? $opt['weights'] : [];
            if ($optText === '') { continue; }
            $optOrder += 1;
            $oIns = $pdo->prepare("INSERT INTO assessment_options (question_id, option_text, option_order) VALUES (?, ?, ?)");
            $oIns->bindValue(1, $questionId, PDO::PARAM_INT);
            $oIns->bindValue(2, $optText);
            $oIns->bindValue(3, $optOrder, PDO::PARAM_INT);
            $oIns->execute();
            $optionId = (int)$pdo->lastInsertId();
            $insertedOptions++;

            // 权重插入
            foreach ($weights as $slug => $w) {
                if (!isset($moodTypes[$slug])) { continue; }
                $weightVal = is_numeric($w) ? (int)$w : 0;
                $wIns = $pdo->prepare("INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES (?, ?, ?)");
                $wIns->bindValue(1, $optionId, PDO::PARAM_INT);
                $wIns->bindValue(2, $moodTypes[$slug], PDO::PARAM_INT);
                $wIns->bindValue(3, $weightVal, PDO::PARAM_INT);
                $wIns->execute();
                $insertedWeights++;
            }
        }
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Questions imported successfully',
        'inserted_questions' => $insertedQuestions,
        'inserted_options' => $insertedOptions,
        'inserted_weights' => $insertedWeights,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo)) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>