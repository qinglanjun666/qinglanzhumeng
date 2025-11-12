<?php
/**
 * MBTI高校推荐API
 * GET /api/mbti_recommend.php?mbti=INTJ
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

// 获取MBTI类型参数（支持GET和POST，优先GET）
$mbti = isset($_GET['mbti']) ? strtoupper(trim($_GET['mbti'])) : (isset($_POST['mbti']) ? strtoupper(trim($_POST['mbti'])) : '');

// 校验MBTI类型
if (!$mbti || !preg_match('/^[EI][NS][TF][JP]$/', $mbti)) {
    echo json_encode([
        'success' => false,
        'message' => '请提供有效的MBTI类型（如INTJ、ENFP）',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 结果占位插画（如无专属插画，统一返回占位图）
$defaultIllustration = '/assets/placeholder.svg';

$responseData = [];
$source = 'db';

// 优先查询数据库
try {
    include_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        $sql = "SELECT university, mbti_type, mbti_desc FROM university_mbti_tags WHERE UPPER(mbti_type) = :mbti";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':mbti', $mbti);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $responseData[] = [
                'university' => $row['university'],
                'mbti_type' => strtoupper($row['mbti_type']),
                'mbti_desc' => $row['mbti_desc'],
                'illustration' => $defaultIllustration,
                'match_type' => 'primary',
                'source' => 'db'
            ];
        }
    } else {
        $source = 'file';
    }
} catch (Exception $e) {
    // 数据库不可用或查询失败时，回退到文件
    $source = 'file';
}

// 文件数据回退
if ($source === 'file' || empty($responseData)) {
    $data_file = __DIR__ . '/../data/universities_mbti.json';
    if (!file_exists($data_file)) {
        echo json_encode([
            'success' => false,
            'message' => '高校性格标签数据源不可用',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $json = file_get_contents($data_file);
    $universities = json_decode($json, true);
    if (!is_array($universities)) {
        echo json_encode([
            'success' => false,
            'message' => '高校性格标签数据解析失败',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $filtered = array_filter($universities, function($item) use ($mbti) {
        return isset($item['mbti_type']) && strtoupper($item['mbti_type']) === $mbti;
    });

    $responseData = array_map(function($item) use ($defaultIllustration) {
        return [
            'university' => $item['university'] ?? null,
            'mbti_type' => strtoupper($item['mbti_type'] ?? ''),
            'mbti_desc' => $item['mbti_desc'] ?? null,
            'illustration' => $defaultIllustration,
            'match_type' => 'primary',
            'source' => 'file'
        ];
    }, array_values($filtered));
}

// 返回统一响应
$response = [
    'success' => true,
    'mbti' => $mbti,
    'count' => count($responseData),
    'data' => $responseData
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);