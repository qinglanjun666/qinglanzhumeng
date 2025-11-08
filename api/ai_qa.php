<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// 读取配置与模型
$aiConfig = require __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/University.php';

// 管理密钥校验（可选）：若环境变量 HJ_ADMIN_API_KEY 存在，则要求请求携带匹配密钥
function requireAdminKeyOptional() {
  $expected = getenv('HJ_ADMIN_API_KEY');
  if ($expected === false || $expected === '') { return; }
  $provided = null;
  if (isset($_SERVER['HTTP_X_ADMIN_KEY'])) { $provided = $_SERVER['HTTP_X_ADMIN_KEY']; }
  elseif (isset($_GET['admin_key'])) { $provided = $_GET['admin_key']; }
  else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data) && isset($data['admin_key'])) { $provided = $data['admin_key']; }
  }
  if (!$provided || $provided !== $expected) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid admin key'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}
requireAdminKeyOptional();

// 简易文件缓存：data/ai_cache/{hash}.json，TTL可通过环境变量 HJ_AI_CACHE_TTL 配置（默认7天）
function cacheDir() {
  $dir = __DIR__ . '/../data/ai_cache';
  if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
  return $dir;
}
function cachePath($key) { return cacheDir() . '/' . $key . '.json'; }
function cacheGet($key, $ttlSeconds) {
  $path = cachePath($key);
  if (!is_file($path)) return null;
  $raw = @file_get_contents($path);
  if (!$raw) return null;
  $data = json_decode($raw, true);
  if (!is_array($data)) return null;
  $ts = isset($data['created_at']) ? intval($data['created_at']) : 0;
  if ($ts <= 0) return null;
  if ((time() - $ts) > $ttlSeconds) return null;
  return $data;
}
function cacheSet($key, $payload) {
  $path = cachePath($key);
  $payload['created_at'] = time();
  @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE));
}

function jsonInput() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (is_array($data)) return $data;
  // 同时支持表单
  return $_REQUEST;
}

function fetchUniversity($id = null, $name = null) {
  $db = new Database();
  $conn = $db->getConnection();
  if (!$conn) return null;
  $u = new University($conn);
  if ($id) return $u->getUniversityDetail((int)$id);
  // 根据名称查询（简化实现：先查列表，再匹配）
  $list = $u->getUniversities(1, 200, null, $name);
  foreach ($list['data'] as $row) {
    if (mb_stripos($row['name'], $name) !== false) {
      return $u->getUniversityDetail((int)$row['id']);
    }
  }
  return null;
}

function buildPrompt($univ, $question = null) {
  $base = [
    'name' => $univ['name'] ?? '',
    'province' => $univ['province'] ?? '',
    'city' => $univ['city'] ?? '',
    'type' => $univ['type'] ?? '',
    'one_line' => $univ['one_line'] ?? '',
    'keywords' => $univ['keywords'] ?? '',
    'mood_type' => $univ['mood_type']['name'] ?? ($univ['mood_type_name'] ?? ''),
    'like_count' => $univ['like_count'] ?? 0,
    'vote_distribution' => $univ['vote_distribution'] ?? [],
  ];
  $q = $question ?: '请基于大学特色生成3-5个「用户常见提问与回答」，突出优势与适合人群，输出JSON。';
  $sys = '你是教育领域的咨询助手，请以真实、审慎的语气，输出结构化JSON问答，避免夸大或失实。';
  $user = '大学信息：' . json_encode($base, JSON_UNESCAPED_UNICODE) . "\n" . '问题：' . $q . "\n" . '输出格式：{"qa":[{"q":"...","a":"...","tags":["特色","环境"],"confidence":0.0}],"summary":"一句话总结"}';
  return [$sys, $user];
}

function callClaude($config, $sys, $user) {
  $apiKey = $config['anthropic_api_key'];
  if (empty($apiKey)) return null;
  $payload = [
    'model' => $config['anthropic_model'],
    'max_tokens' => 800,
    'temperature' => 0.7,
    'system' => $sys,
    'messages' => [ ['role' => 'user', 'content' => $user] ],
  ];
  $ch = curl_init($config['anthropic_endpoint']);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'X-API-Key: ' . $apiKey,
      'anthropic-version: ' . $config['anthropic_version'],
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => $config['timeout'],
  ]);
  $resp = curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($err) return ['error' => $err, 'http' => $code];
  $data = json_decode($resp, true);
  return $data;
}

function dryRunAnswer($univ, $question = null) {
  $name = $univ['name'] ?? '目标大学';
  $one = $univ['one_line'] ?? '特色介绍待完善';
  $mood = $univ['mood_type']['name'] ?? ($univ['mood_type_name'] ?? '');
  $province = $univ['province'] ?? '';
  $city = $univ['city'] ?? '';
  $tags = array_filter([$mood, $province, $city]);
  $qa = [
    ['q' => $question ?: "为什么选择$name？", 'a' => "$name：$one。适合倾向于$mood 的同学，校园位于$province $city，学习与生活资源较为丰富。", 'tags' => $tags, 'confidence' => 0.65],
    ['q' => "$name 的优势学科是什么？", 'a' => "建议关注学校公布的优势学科与近年招生情况，结合个人兴趣与职业规划做选择。", 'tags' => ['学科'], 'confidence' => 0.6],
    ['q' => "$name 的校园氛围如何？", 'a' => "整体氛围偏向 $mood，同学参与度较高，建议亲自咨询在读学生或参加开放日体验。", 'tags' => ['氛围'], 'confidence' => 0.55],
  ];
  return ['qa' => $qa, 'summary' => "$name：$one" ];
}

$input = jsonInput();
$id = isset($input['id']) ? (int)$input['id'] : null;
$name = isset($input['name']) ? trim($input['name']) : null;
$question = isset($input['question']) ? trim($input['question']) : null;

if (!$id && !$name) {
  http_response_code(400);
  echo json_encode(['error' => '缺少参数：id 或 name'], JSON_UNESCAPED_UNICODE);
  exit;
}

$univ = fetchUniversity($id, $name);
if (!$univ) {
  http_response_code(404);
  echo json_encode(['error' => '未找到大学'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 缓存命中检查
$keyBase = $id ? ('id:' . intval($id)) : ('name:' . ($name ?: $univ['name']));
$qBase = $question ?: '';
$cacheKey = substr(sha1($keyBase . '|' . $qBase), 0, 16);
$ttl = getenv('HJ_AI_CACHE_TTL');
$ttl = ($ttl && intval($ttl) > 0) ? intval($ttl) : (7 * 24 * 60 * 60);
$cached = cacheGet($cacheKey, $ttl);
if ($cached && isset($cached['answer'])) {
  echo json_encode([
    'ok' => true,
    'cached' => true,
    'university' => [ 'id' => $univ['id'], 'name' => $univ['name'] ],
    'answer' => $cached['answer'],
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

list($sys, $user) = buildPrompt($univ, $question);
$result = null;
if ($aiConfig['dry_run']) {
  $result = [ 'dry_run' => true, 'output' => dryRunAnswer($univ, $question) ];
} else {
  $claude = callClaude($aiConfig, $sys, $user);
  $parsed = null;
  if (isset($claude['content'][0]['type']) && $claude['content'][0]['type'] === 'text') {
    $txt = $claude['content'][0]['text'];
    $try = json_decode($txt, true);
    if (is_array($try)) $parsed = $try;
  }
  $result = [ 'dry_run' => false, 'raw' => $claude, 'output' => $parsed ];
}

// 写入缓存
cacheSet($cacheKey, [
  'university' => [ 'id' => $univ['id'], 'name' => $univ['name'] ],
  'question' => $question,
  'answer' => $result,
]);

echo json_encode([
  'ok' => true,
  'university' => [ 'id' => $univ['id'], 'name' => $univ['name'] ],
  'answer' => $result,
], JSON_UNESCAPED_UNICODE);
?>