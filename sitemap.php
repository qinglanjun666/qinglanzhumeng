<?php
// 动态生成网站地图（sitemap.xml）
header('Content-Type: application/xml; charset=UTF-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // 期望为 /huilanweb
$baseUrl = $scheme . '://' . $host . $basePath;

$urls = [];
// 静态页面
$staticPages = [
    '/index.html',
    '/search.html',
    '/mood-map.html',
    '/assessment.html',
];
foreach ($staticPages as $p) {
    $urls[] = [ 'loc' => $baseUrl . $p, 'changefreq' => 'weekly', 'priority' => '0.8' ];
}

// 读取大学详情页URL：/university/{id}
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        $stmt = $db->query('SELECT id FROM universities ORDER BY id ASC');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int)$row['id'];
            $urls[] = [ 'loc' => $baseUrl . '/university/' . $id, 'changefreq' => 'weekly', 'priority' => '0.9' ];
        }
    }
} catch (Exception $e) {
    // 忽略数据库异常，照常输出静态页面条目
}

// 输出XML
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($urls as $u) {
    $loc = htmlspecialchars($u['loc'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $changefreq = $u['changefreq'] ?? 'weekly';
    $priority = $u['priority'] ?? '0.8';
    echo "\n  <url>\n";
    echo "    <loc>{$loc}</loc>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>";
}
echo "\n</urlset>\n";
?>