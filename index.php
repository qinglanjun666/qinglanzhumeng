<?php
// 将默认首页从 index.php 跳转到现有的 index.html
// 便于在 Apache/XAMPP 环境下通过目录根路径直接访问首页
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: index.html');
exit;
?>