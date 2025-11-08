<?php
/**
 * 管理端：导出基础埋点事件为CSV
 * GET /api/admin/analytics/export
 * 可选参数：event_type, from(YYYY-MM-DD), to(YYYY-MM-DD), limit, password 或 Header: X-Admin-Password
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Password');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([ 'error' => 'Method not allowed' ]);
    exit();
}

require_once '../config/database.php';

// 简单密码校验
$envPassword = getenv('HJ_ADMIN_PASSWORD');
$expectedPassword = $envPassword ? $envPassword : 'zxasqw123456';
$providedPassword = null;
if (isset($_SERVER['HTTP_X_ADMIN_PASSWORD'])) {
    $providedPassword = $_SERVER['HTTP_X_ADMIN_PASSWORD'];
} elseif (isset($_GET['password'])) {
    $providedPassword = $_GET['password'];
}
if (!$providedPassword || $providedPassword !== $expectedPassword) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([ 'error' => 'Unauthorized', 'message' => 'Invalid admin password' ]);
    exit();
}

// 连接数据库
$database = new Database();
$pdo = $database->getConnection();

// 读取过滤条件
$eventType = isset($_GET['event_type']) ? trim($_GET['event_type']) : null;
$from = isset($_GET['from']) ? trim($_GET['from']) : null; // YYYY-MM-DD
$to = isset($_GET['to']) ? trim($_GET['to']) : null;       // YYYY-MM-DD
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
if ($limit <= 0 || $limit > 100000) { $limit = 1000; }

$query = "SELECT id, event_type, entity_id, client_id, ip, user_agent, created_at, meta FROM analytics_events WHERE 1=1";
$params = [];
if ($eventType) { $query .= " AND event_type = :event_type"; $params[':event_type'] = $eventType; }
if ($from) { $query .= " AND created_at >= :from"; $params[':from'] = $from . ' 00:00:00'; }
if ($to) { $query .= " AND created_at <= :to"; $params[':to'] = $to . ' 23:59:59'; }
$query .= " ORDER BY created_at DESC, id DESC";
$query .= " LIMIT :limit";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 输出CSV
$filename = 'analytics_events_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
// CSV头
fputcsv($output, ['id','event_type','entity_id','client_id','ip','user_agent','created_at','meta']);
foreach ($rows as $row) {
    fputcsv($output, [
        $row['id'],
        $row['event_type'],
        $row['entity_id'],
        $row['client_id'],
        $row['ip'],
        $row['user_agent'],
        $row['created_at'],
        $row['meta']
    ]);
}
fclose($output);
exit();
?>