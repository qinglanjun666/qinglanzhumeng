<?php
/**
 * 大学-标签点赞API
 * POST /api/university/{id}/tags/{tag_id}/like
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('message' => 'Method not allowed'));
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        http_response_code(500);
        echo json_encode(array('message' => 'Database connection failed'));
        exit();
    }

    $university_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $tag_id = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : 0;
    if ($university_id <= 0 || $tag_id <= 0) {
        http_response_code(400);
        echo json_encode(array('message' => 'Invalid university or tag id'));
        exit();
    }

    // 验证大学-标签关联是否存在
    $check = $db->prepare("SELECT COUNT(*) AS c FROM university_personality_tags WHERE university_id = :uid AND tag_id = :tid");
    $check->bindValue(':uid', $university_id, PDO::PARAM_INT);
    $check->bindValue(':tid', $tag_id, PDO::PARAM_INT);
    $check->execute();
    $exists = ((int)$check->fetch(PDO::FETCH_ASSOC)['c']) > 0;
    if (!$exists) {
        http_response_code(404);
        echo json_encode(array('message' => 'Tag not bound to university'));
        exit();
    }

    // 获取客户端ID
    $input = json_decode(file_get_contents('php://input'), true);
    $client_id = null;
    if (isset($input['client_id']) && !empty($input['client_id'])) {
        $client_id = $input['client_id'];
    }
    if (!$client_id && isset($_COOKIE['hj_client_id'])) {
        $client_id = $_COOKIE['hj_client_id'];
    }
    if (!$client_id) {
        // 简易生成：与 University::generateClientId 保持一致长度
        $client_id = 'hj_' . bin2hex(random_bytes(6));
        setcookie('hj_client_id', $client_id, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    // 获取IP
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip_address = $_SERVER['HTTP_X_REAL_IP'];
    }

    // 检查是否已点赞
    $chk = $db->prepare("SELECT COUNT(*) AS c FROM university_tag_likes WHERE university_id = :uid AND tag_id = :tid AND client_id = :cid");
    $chk->bindValue(':uid', $university_id, PDO::PARAM_INT);
    $chk->bindValue(':tid', $tag_id, PDO::PARAM_INT);
    $chk->bindValue(':cid', $client_id, PDO::PARAM_STR);
    $chk->execute();
    $already = ((int)$chk->fetch(PDO::FETCH_ASSOC)['c']) > 0;

    if ($already) {
        // 返回当前热度
        $cntStmt = $db->prepare("SELECT COUNT(*) AS c FROM university_tag_likes WHERE university_id = :uid AND tag_id = :tid");
        $cntStmt->bindValue(':uid', $university_id, PDO::PARAM_INT);
        $cntStmt->bindValue(':tid', $tag_id, PDO::PARAM_INT);
        $cntStmt->execute();
        $like_count = (int)$cntStmt->fetch(PDO::FETCH_ASSOC)['c'];
        http_response_code(200);
        echo json_encode(array(
            'message' => 'already liked',
            'client_id' => $client_id,
            'like_count' => $like_count,
            'already_liked' => true
        ));
        exit();
    }

    // 新增点赞记录
    $ins = $db->prepare("INSERT INTO university_tag_likes (university_id, tag_id, client_id, ip_address) VALUES (:uid, :tid, :cid, :ip)");
    $ins->bindValue(':uid', $university_id, PDO::PARAM_INT);
    $ins->bindValue(':tid', $tag_id, PDO::PARAM_INT);
    $ins->bindValue(':cid', $client_id, PDO::PARAM_STR);
    $ins->bindValue(':ip', $ip_address, PDO::PARAM_STR);
    $ok = $ins->execute();
    if (!$ok) {
        http_response_code(500);
        echo json_encode(array('message' => 'Failed to add tag like'));
        exit();
    }

    // 返回最新热度
    $cntStmt = $db->prepare("SELECT COUNT(*) AS c FROM university_tag_likes WHERE university_id = :uid AND tag_id = :tid");
    $cntStmt->bindValue(':uid', $university_id, PDO::PARAM_INT);
    $cntStmt->bindValue(':tid', $tag_id, PDO::PARAM_INT);
    $cntStmt->execute();
    $like_count = (int)$cntStmt->fetch(PDO::FETCH_ASSOC)['c'];

    http_response_code(200);
    echo json_encode(array(
        'message' => 'Like added successfully',
        'client_id' => $client_id,
        'like_count' => $like_count,
        'already_liked' => false
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('message' => 'Internal server error', 'error' => $e->getMessage()));
}
?>