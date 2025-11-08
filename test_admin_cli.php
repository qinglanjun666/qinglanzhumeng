<?php
/**
 * 管理接口基本验证
 */

function get($url, $headers = []) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HEADER => false,
    CURLOPT_HTTPHEADER => $headers,
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $body];
}

function post($url, $fields, $headers = []) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $fields,
    CURLOPT_HTTPHEADER => $headers,
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $body];
}

echo "=== 管理接口测试 ===\n\n";

// 1. 导出埋点（携带管理密码）
$headers = ['X-Admin-Password: zxasqw123456'];
list($code1, $body1) = get('http://localhost/huilanweb/api/admin/analytics/export', $headers);
echo "GET /api/admin/analytics/export -> $code1\n";
if ($code1 === 200) {
  $head = substr($body1, 0, 32);
  echo "CSV头部: " . $head . "\n";
}

// 2. 导入大学（提供示例CSV文本）
$csv = "name,province,city,type,mood_slug,keywords,one_line,logo_url,external_id\n" .
       "示例大学导入,北京,北京,综合,rational_creator,测试,命令行导入测试,,sample_ext_001\n";
$fields = [
  'password' => 'zxasqw123456',
  'match_by' => 'name',
  'csv_text' => $csv,
];
list($code2, $body2) = post('http://localhost/huilanweb/api/admin/import/universities', $fields, $headers);
echo "POST /api/admin/import/universities -> $code2\n";
if ($body2) echo "响应: " . substr($body2, 0, 160) . "...\n";

echo "\n=== 完成 ===\n";
?>