<?php
/**
 * SEO监控端点测试：POST /api/seo_monitor.php
 */

$url = 'http://localhost/huilanweb/api/seo_monitor.php';
$payload = json_encode([ 'paths' => ['index.html','search.html'] ], JSON_UNESCAPED_UNICODE);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_TIMEOUT => 10,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== SEO监控测试 ===\n\n";
echo "POST /api/seo_monitor.php -> $code\n";
if ($body) {
  // 打印报告摘要
  $data = json_decode($body, true);
  if (is_array($data)) {
    echo "目标: " . ($data['monitor_target'] ?? 'N/A') . "\n";
    $summary = $data['summary'] ?? [];
    echo "平均分: " . ($summary['score_avg'] ?? 'N/A') . ", 关键问题: " . ($summary['critical'] ?? '0') . ", 警告: " . ($summary['warnings'] ?? '0') . "\n";
  } else {
    echo substr($body, 0, 200) . "...\n";
  }
}
echo "\n=== 完成 ===\n";
?>