<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false], JSON_UNESCAPED_UNICODE); exit(); }
$type = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
if (!$type || !preg_match('/^[EI][SN][TF][JP]$/', $type)) { echo json_encode(['success'=>false,'message'=>'invalid type'], JSON_UNESCAPED_UNICODE); exit(); }
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = strpos($_SERVER['REQUEST_URI'], '/huilanweb') !== false ? '/huilanweb' : '';
$target = $scheme.'://' . $host . $base . '/mbti/result.php?type=' . $type;
$dir = __DIR__ . '/qrcodes';
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
$file = $dir . '/' . strtolower($type) . '.png';
if (!is_file($file) || filesize($file) < 100) {
  $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode($target) . '&format=png';
  $img = @file_get_contents($qrUrl);
  if ($img !== false) { @file_put_contents($file, $img); }
}
$public = $base . '/mbti/qrcodes/' . strtolower($type) . '.png';
echo json_encode(['success'=>true,'url'=>$public], JSON_UNESCAPED_UNICODE);