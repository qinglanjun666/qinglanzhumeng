<?php $docRoot = $_SERVER['DOCUMENT_ROOT']; $hasInc = is_file($docRoot.'/includes/header.php') && is_file($docRoot.'/includes/footer.php'); ?>
<?php if(!$hasInc){ ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MBTI 测评介绍页</title>
  <style>
    :root { --brand:#1E40FF; --bg1:#ECF2FF; --bg2:#F4F0FF; --text:#111827; --muted:#6b7280; }
    html,body { margin:0; padding:0; background:linear-gradient(135deg,var(--bg1),var(--bg2)); color:var(--text); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"PingFang SC","Microsoft Yahei",sans-serif; }
    .mbti-intro-container { max-width: 700px; margin: 0 auto; padding: 36px 24px; }
    .intro-card { background:#fff; border-radius:22px; box-shadow:0 4px 20px rgba(0,0,0,.06); padding:28px; opacity:0; transform:translateY(20px); animation:introFade .5s ease-out forwards; }
    .intro-card h1 { margin:0 0 12px; font-size:32px; font-weight:700; color:#0f172a; }
    .subtitle { margin:0 0 18px; color:#374151; font-size:18px; font-weight:500; line-height:1.8; }
    .feature-list { list-style:none; margin:0; padding:0; display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    .feature-list li { background:#f8fafc; border:1px solid #e5e7eb; border-radius:14px; padding:12px 14px; font-size:16px; color:#444; }
    .tips { margin:14px 0 22px; color:#888; font-size:14px; line-height:1.8; }
    .btn-primary { display:inline-flex; align-items:center; justify-content:center; height:54px; padding:0 22px; border-radius:14px; font-weight:800; font-size:18px; text-decoration:none; color:#fff; background:linear-gradient(135deg,#6C89FF,#8DA8FF); box-shadow:0 12px 30px rgba(108,139,255,.30); transition:transform .12s ease, box-shadow .2s ease, filter .2s ease; }
    .btn-primary:hover { filter:brightness(1.05); box-shadow:0 14px 34px rgba(108,139,255,.34); }
    .btn-primary:active { transform:translateY(1px) scale(0.995); box-shadow:0 10px 26px rgba(108,139,255,.30); }
    .btn-secondary { display:inline-flex; align-items:center; justify-content:center; height:52px; padding:0 20px; border-radius:12px; font-weight:800; font-size:16px; text-decoration:none; color:#6C89FF; background:#fff; border:1.5px solid #6C89FF; margin-top:14px; }
    .btn-secondary:hover { background:#f7f9ff; }
    @keyframes introFade { to { opacity:1; transform:translateY(0); } }
    @media (max-width: 768px) {
      .mbti-intro-container { padding: 26px 16px; }
      .intro-card { padding:22px; border-radius:20px; }
      .intro-card h1 { font-size:28px; }
      .subtitle { font-size:16px; }
      .feature-list { grid-template-columns: 1fr; }
      .btn-primary, .btn-secondary { width:100%; text-align:center; }
    }
  </style>
<?php } ?>
<?php if($hasInc){ include $docRoot.'/includes/header.php'; } ?>
<?php if(!$hasInc){ ?>
</head>
<body>
<?php } ?>
  <div class="mbti-intro-container">
    <div class="intro-card">
      <h1>🧩 探索你的性格密码</h1>
      <p class="subtitle">MBTI 全维度人格测试<br>以更科学、更温柔的方式，帮你看见更清晰的自己。</p>
      <ul class="feature-list">
        <li>120 道权威题库</li>
        <li>职业发展建议</li>
        <li>可视化性格图谱</li>
        <li>多设备适配</li>
      </ul>
      <p class="tips">测试约需 10–15 分钟<br>每道题没有标准答案，只需凭直觉选择即可。</p>
      <a href="/mbti/start" class="btn-primary" aria-label="开始你的性格旅程">🔮 开始你的性格旅程</a>
      <a href="/mbti/select" class="btn-secondary" aria-label="我已知自己的 MBTI">我已知自己的 MBTI · 选择我的 MBTI</a>
    </div>
  </div>
<?php if($hasInc){ include $docRoot.'/includes/footer.php'; } ?>
<?php if(!$hasInc){ ?>
</body>
</html>
<?php } ?>