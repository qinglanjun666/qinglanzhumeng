<?php
$types = [
  'INTJ'=>'策略型', 'INTP'=>'逻辑型', 'INFJ'=>'倡导型', 'INFP'=>'理想型',
  'ISTJ'=>'守序型', 'ISTP'=>'实干型', 'ISFJ'=>'照护型', 'ISFP'=>'艺术型',
  'ENTJ'=>'领导型', 'ENTP'=>'创新型', 'ENFJ'=>'组织型', 'ENFP'=>'灵感型',
  'ESTJ'=>'管理型', 'ESTP'=>'行动型', 'ESFJ'=>'服务型', 'ESFP'=>'表演型'
];
$keys = array_keys($types);
$pick = $keys[random_int(0, count($keys)-1)];
$base = strpos($_SERVER['REQUEST_URI'], '/huilanweb') !== false ? '/huilanweb' : '';
?>
<style>
.mbti-entry { background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,.06); padding:20px; display:grid; grid-template-columns: 1fr; gap:12px; }
.mbti-entry .title { font-weight:800; font-size:20px; color:#0f172a; }
.mbti-entry .sub { color:#4b5563; font-size:14px; line-height:1.8; }
.mbti-entry .row { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.mbti-entry .btn { display:inline-block; padding:10px 16px; border-radius:10px; background:#1E40FF; color:#fff; font-weight:700; text-decoration:none; }
.mbti-entry .hot { color:#374151; font-size:14px; }
@media (max-width:768px){ .mbti-entry { padding:16px; } }
</style>
<section class="mbti-entry" aria-label="MBTI 性格测试入口">
  <div class="title">🧠 MBTI 性格测试（120题）</div>
  <div class="sub">10–15 分钟了解你真实的性格维度；结果含可视化图谱、职业方向与分享二维码。</div>
  <div class="row">
    <a class="btn" href="<?php echo $base; ?>/mbti/home.php">开始测试</a>
    <div class="hot">今日热门人格：<?php echo htmlspecialchars($pick); ?>（<?php echo htmlspecialchars($types[$pick]); ?>）</div>
  </div>
</section>