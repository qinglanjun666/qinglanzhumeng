<?php
/**
 * 前端页面与核心API可达性检查（命令行）
 */

function checkUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // 使用GET请求，读取响应体
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code;
}

$urls = [
    'http://localhost/huilanweb/',
    'http://localhost/huilanweb/index.html',
    'http://localhost/huilanweb/search.html',
    'http://localhost/huilanweb/assessment.html',
    'http://localhost/huilanweb/admin_ai_seo.html',
    'http://localhost/huilanweb/sitemap.php',
    'http://localhost/huilanweb/api/universities',
    'http://localhost/huilanweb/api/universities/1',
    'http://localhost/huilanweb/api/personality_tags',
    'http://localhost/huilanweb/api/mood_types',
    'http://localhost/huilanweb/api/assessment/questions',
];

echo "=== 页面与API可达性检查 ===\n\n";
foreach ($urls as $u) {
    $code = checkUrl($u);
    echo sprintf("%s -> %s\n", $u, $code ?: 'ERROR');
}

echo "\n=== 完成 ===\n";
?>