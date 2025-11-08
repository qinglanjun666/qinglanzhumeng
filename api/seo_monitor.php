<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// 配置与常量
$baseUrl = 'http://localhost:5173'; // 指向正在运行的开发服务器
$baseDir = realpath(__DIR__ . '/..'); // 用于文件存在性检查

// 管理密钥校验（可选）
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

function readJsonInput() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (is_array($data)) return $data;
  return $_REQUEST;
}

function fetchHtml($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'Huilan-SEO-Monitor/1.0',
  ]);
  $html = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);
  if ($err) return null; // 错误时返回null
  return $html;
}

function parseMeta($html) {
  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $dom->loadHTML($html);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);

  $getMeta = function($selector, $attr, $value) use ($xpath) {
    $query = "//meta[@$attr='$value']";
    $nodes = $xpath->query($query);
    if ($nodes && $nodes->length) {
      $n = $nodes->item(0);
      return $n->getAttribute('content');
    }
    return null;
  };

  $titleNode = $xpath->query('//title');
  $title = ($titleNode && $titleNode->length) ? trim($titleNode->item(0)->textContent) : null;
  $desc = $getMeta('meta', 'name', 'description');
  $robots = $getMeta('meta', 'name', 'robots');
  $ogTitle = $getMeta('meta', 'property', 'og:title');
  $ogDesc = $getMeta('meta', 'property', 'og:description');
  $ogImage = $getMeta('meta', 'property', 'og:image');
  $ogUrl = $getMeta('meta', 'property', 'og:url');
  $canonicalNode = $xpath->query("//link[@rel='canonical']");
  $canonical = ($canonicalNode && $canonicalNode->length) ? $canonicalNode->item(0)->getAttribute('href') : null;

  // 检查H1/H2
  $h1s = $xpath->query('//h1');
  $h2s = $xpath->query('//h2');

  $preconnects = [];
  foreach ($xpath->query("//link[@rel='preconnect']") as $lnk) {
    $preconnects[] = $lnk->getAttribute('href');
  }

  $images = $xpath->query('//img');
  $imgTotal = $images ? $images->length : 0;
  $imgLazy = 0; $imgMissingAlt = 0;
  if ($images) {
    foreach ($images as $img) {
      if (strtolower($img->getAttribute('loading')) === 'lazy') $imgLazy++;
      if (!$img->getAttribute('alt')) $imgMissingAlt++;
    }
  }

  $score = 100;
  $issues = [];
  if (!$title) { $score -= 10; $issues[] = '缺少<title>'; }
  if (!$desc) { $score -= 10; $issues[] = '缺少meta description'; }
  if (!$canonical) { $score -= 6; $issues[] = '缺少canonical'; }
  if (!$ogTitle || !$ogDesc || !$ogImage) { $score -= 12; $issues[] = 'OG标签不完整'; }
  if (!$h1s || $h1s->length === 0) { $score -= 8; $issues[] = '页面缺少H1标签'; }
  if ($h1s && $h1s->length > 1) { $score -= 5; $issues[] = '页面存在多个H1标签'; }
  if ($imgMissingAlt > 0) { $score -= min(10, $imgMissingAlt * 2); $issues[] = '存在无alt图片'; }

  return [
    'title' => ['exists' => !!$title, 'value' => $title, 'length' => $title ? mb_strlen($title) : 0],
    'meta' => ['description' => ['exists' => !!$desc, 'length' => $desc ? mb_strlen($desc) : 0, 'value' => $desc], 'robots' => $robots],
    'og' => [ 'title' => !!$ogTitle, 'description' => !!$ogDesc, 'image' => !!$ogImage, 'url' => !!$ogUrl ],
    'canonical' => $canonical,
    'structure' => ['h1_count' => $h1s ? $h1s->length : 0, 'h2_count' => $h2s ? $h2s->length : 0],
    'links' => [ 'preconnect' => $preconnects ],
    'images' => [ 'total' => $imgTotal, 'lazy' => $imgLazy, 'missing_alt' => $imgMissingAlt ],
    'score' => max(0, $score),
    'issues' => $issues,
  ];
}

function monitorPages($baseUrl, $paths) {
  $report = [];
  foreach ($paths as $p) {
    $url = rtrim($baseUrl, '/') . '/' . ltrim($p, '/');
    $html = fetchHtml($url);
    $metrics = $html ? parseMeta($html) : ['error' => '无法抓取页面', 'url' => $url];
    $report[] = [ 'path' => '/' . ltrim($p, '/'), 'metrics' => $metrics ];
  }
  return $report;
}

$input = readJsonInput();
$paths = isset($input['paths']) && is_array($input['paths']) ? $input['paths'] : null;
if (!$paths) {
  $paths = [
    'index.html', 'search.html', 'university.html', 'assessment.html', 'mood-map.html',
    'privacy.html', 'terms.html', 'disclaimer.html'
  ];
}

$siteName = 'huilanweb';
$hasSitemap = is_file($baseDir . DIRECTORY_SEPARATOR . 'sitemap.php');

$pages = monitorPages($baseUrl, $paths);

$summaryScore = 0; $countScored = 0; $critical = 0; $warnings = 0;
foreach ($pages as $pg) {
  if (isset($pg['metrics']['score'])) { $summaryScore += $pg['metrics']['score']; $countScored++; }
  if (!empty($pg['metrics']['issues'])) {
    foreach ($pg['metrics']['issues'] as $i) {
      if (strpos($i, '缺少') !== false) $critical++; else $warnings++;
    }
  }
}

$out = [
  'site' => $siteName,
  'generated_at' => date('c'),
  'monitor_target' => $baseUrl,
  'sitemap' => [ 'exists' => $hasSitemap, 'path' => '/sitemap.php' ],
  'pages' => $pages,
  'summary' => [
    'score_avg' => $countScored ? round($summaryScore / $countScored, 2) : null,
    'critical' => $critical,
    'warnings' => $warnings,
  ],
  'template' => [
    'page' => [
      'path' => '/example.html',
      'metrics' => [
        // ... (template structure remains similar but reflects new additions)
        'title' => ['exists' => true, 'value' => '页面标题', 'length' => 15],
        'meta' => ['description' => ['exists' => true, 'length' => 120, 'value' => '描述文本'], 'robots' => 'index, follow'],
        'og' => ['title' => true, 'description' => true, 'image' => true, 'url' => true],
        'canonical' => 'https://example.com/example.html',
        'structure' => ['h1_count' => 1, 'h2_count' => 3],
        'links' => ['preconnect' => ['https://fonts.googleapis.com']],
        'images' => ['total' => 5, 'lazy' => 4, 'missing_alt' => 1],
        'score' => 92,
        'issues' => ['示例问题'],
      ]
    ]
  ]
];

echo json_encode($out, JSON_UNESCAPED_UNICODE);
?>