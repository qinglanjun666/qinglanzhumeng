<?php
/**
 * 大学基础信息API
 * GET /api/universities_basic - 获取 universities_basic 表的基础信息
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array(
        "success" => false,
        "message" => "Method not allowed. Only GET requests are supported."
    ));
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("数据库连接失败");
    }

    // 读取参数（支持简单分页与筛选）
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, min(500, intval($_GET['per_page']))) : 20;
    $region = isset($_GET['region']) ? trim($_GET['region']) : null;
    $nature = isset($_GET['nature']) ? trim($_GET['nature']) : null;
    $level = isset($_GET['level']) ? trim($_GET['level']) : null;
    $q = isset($_GET['q']) ? trim($_GET['q']) : null; // 按名称或重点专业模糊搜索

    $baseQuery = "SELECT id, name, region, nature, level, key_majors FROM universities_basic";
    $conditions = [];
    $params = [];

    if ($region) { $conditions[] = "region = :region"; $params[':region'] = $region; }
    if ($nature) { $conditions[] = "nature = :nature"; $params[':nature'] = $nature; }
    if ($level)  { $conditions[] = "level = :level";   $params[':level'] = $level; }
    if ($q) {
        // 为兼容性，直接在 name 与 key_majors 文本上 LIKE 检索
        $conditions[] = "(name LIKE :q OR CAST(key_majors AS CHAR) LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }

    if (!empty($conditions)) {
        $baseQuery .= " WHERE " . implode(' AND ', $conditions);
    }

    // 统计总数
    $countSql = "SELECT COUNT(*) AS total FROM (" . $baseQuery . ") t";
    $countStmt = $db->prepare($countSql);
    foreach ($params as $k => $v) { $countStmt->bindValue($k, $v); }
    $countStmt->execute();
    $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 分页
    $offset = ($page - 1) * $per_page;
    $query = $baseQuery . " ORDER BY id ASC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // 兼容 key_majors 可能为 JSON 或纯文本
        $majors = null;
        if (!is_null($row['key_majors'])) {
            $decoded = json_decode($row['key_majors'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $majors = $decoded;
            } else {
                // 尝试按分号或逗号拆分
                $parts = preg_split('/[;,\n]+/', $row['key_majors']);
                $majors = array_values(array_filter(array_map('trim', $parts), function($x){ return $x !== ''; }));
            }
        } else {
            $majors = [];
        }

        $items[] = array(
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'region' => $row['region'],
            'nature' => $row['nature'],
            'level' => $row['level'],
            'key_majors' => $majors
        );
    }

    http_response_code(200);
    echo json_encode(array(
        'success' => true,
        'message' => '大学基础信息获取成功',
        'data' => array(
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'universities' => $items
        ),
        'timestamp' => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => '服务器内部错误: ' . $e->getMessage(),
        'error_code' => 'UNIVERSITIES_BASIC_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ), JSON_UNESCAPED_UNICODE);
}
?>