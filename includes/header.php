<?php
$base = strpos($_SERVER['REQUEST_URI'], '/huilanweb') !== false ? '/huilanweb' : '';
?>
<div id="top-nav"></div>
<script src="<?php echo $base; ?>/top-nav.js"></script>