<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri === '/' || $uri === '') {
    return false;
}

if (is_file($file)) {
    return false;
}

if (preg_match('#^/api/#', $uri)) {
    include __DIR__ . '/api/index.php';
    return true;
}

if (preg_match('#^/university/(\d+)$#', $uri, $m)) {
    header('Location: /university.html?id=' . $m[1], true, 302);
    exit;
}

if ($uri === '/sitemap.xml') {
    include __DIR__ . '/sitemap.php';
    return true;
}

// MBTI routes without extension
if ($uri === '/mbti/start') {
    header('Location: /mbti/test.php?page=1', true, 302);
    exit;
}
if ($uri === '/mbti/home') {
    header('Location: /mbti/home.php', true, 302);
    exit;
}
if ($uri === '/mbti/select') {
    header('Location: /assessment.html', true, 302);
    exit;
}
if ($uri === '/mbti') {
    header('Location: /mbti/index.html', true, 302);
    exit;
}
if ($uri === '/mbti/result') {
    header('Location: /mbti/result.php', true, 302);
    exit;
}

if ($uri === '/welcome') {
    header('Location: /universities.html', true, 302);
    exit;
}

if ($uri === '/legal/privacy') {
    header('Location: /privacy.html', true, 302);
    exit;
}

if ($uri === '/legal/terms') {
    header('Location: /terms.html', true, 302);
    exit;
}

if ($uri === '/legal/disclaimer') {
    header('Location: /disclaimer.html', true, 302);
    exit;
}

if (preg_match('#^/welcome/.+$#', $uri)) {
    header('Location: /universities.html', true, 302);
    exit;
}

return false;
?>