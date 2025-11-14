<?php $docRoot = $_SERVER['DOCUMENT_ROOT']; $hasInc = is_file($docRoot.'/includes/header.php') && is_file($docRoot.'/includes/footer.php'); ?>
<?php if(!$hasInc){ ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MBTI 海报生成</title>
<?php } ?>
  <style>
    :root { --brand:#1E40FF; --bg:#f7f9fc; --text:#111827; --muted:#6b7280; }
    html,body { margin:0; padding:0; background:var(--bg); color:var(--text); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"PingFang SC","Microsoft Yahei",sans-serif; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .card { background:#fff; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,.06); padding:16px; }
    .actions { display:flex; gap:10px; margin-top:12px; }
    .btn { padding:10px 16px; background:var(--brand); color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
    /* 海报画布：1080x1920，按比例缩放展示 */
    .poster-stage { width: 360px; height: 640px; transform-origin: top left; background: linear-gradient(180deg,#eef2ff,#f7f9fc); border-radius:20px; padding:18px; display:grid; gap:10px; }
    @media(min-width:768px){ .poster-stage { width: 540px; height: 960px; } }
    @media(min-width:1024px){ .poster-stage { width: 675px; height: 1200px; } }
    .poster-title { display:flex; align-items:center; justify-content:space-between; }
    .badge { background:#1E40FF; color:#fff; border-radius:10px; padding:8px 12px; font-weight:800; font-size:18px; }
    .headline { font-size:18px; font-weight:800; color:#0f172a; }
    .keywords { display:flex; gap:8px; flex-wrap:wrap; }
    .kw { background:#fff; border:1px solid #e5e7eb; border-radius:999px; padding:6px 10px; font-weight:700; font-size:12px; }
    .grid { display:grid; grid-template-columns: 1.2fr .8fr; gap:12px; }
    .section { background:#fff; border-radius:14px; padding:12px; box-shadow:0 4px 14px rgba(0,0,0,.06); }
    .section h4 { margin:0 0 6px; font-size:14px; }
    .desc { color:#374151; font-size:12px; line-height:1.7; }
    .career { color:#111827; font-size:12px; }
    .qr { display:flex; gap:10px; align-items:center; }
    .qr img { width:90px; height:90px; border:5px solid #000; border-radius:12px; background:#fff; }
  </style>
<?php if(!$hasInc){ ?>
</head>
<body>
<?php } ?>
<?php if($hasInc){ include $docRoot.'/includes/header.php'; } ?>
  <div class="wrap">
    <div class="card">
      <div style="display:flex; align-items:center; justify-content:space-between;">
        <div style="font-weight:800;">生成朋友圈海报</div>
        <div class="actions">
          <button id="genBtn" class="btn">生成海报 PNG</button>
          <a id="dlBtn" class="btn" download="mbti_poster.png" style="display:none;">下载图片</a>
        </div>
      </div>
      <div id="stage" class="poster-stage">
        <div class="poster-title">
          <div class="headline">你的 MBTI 类型</div>
          <div id="badge" class="badge">TYPE</div>
        </div>
        <div class="keywords" id="keywords"></div>
        <div class="grid">
          <div class="section">
            <h4>四维向量图</h4>
            <canvas id="chart" width="300" height="300"></canvas>
          </div>
          <div class="section">
            <h4>职业分析</h4>
            <div id="careerDesc" class="desc"></div>
            <ul id="careerList" style="margin:6px 0 0; padding-left:18px;"></ul>
          </div>
        </div>
        <div class="section">
          <h4>扫码查看你的详细结果</h4>
          <div class="qr">
            <img id="qr" alt="结果二维码" />
            <div class="desc" id="linkText"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script src="/mbti/presenter_keywords.js"></script>
  <script>
    function getQueryType(){ const p=new URLSearchParams(location.search); const t=(p.get('type')||'').toUpperCase(); return /^[IE][SN][TF][JP]$/.test(t)?t:null; }
    function toVals(type){ const a=type.split(''); return [a[0]==='E'?100:0,a[1]==='S'?100:0,a[2]==='T'?100:0,a[3]==='J'?100:0]; }
    async function loadLib(){ const r=await fetch('/data/mbti_types.json'); if(!r.ok) throw new Error('类型库失败'); return r.json(); }
    async function genQR(type){ const r=await fetch('/mbti/generate_qr.php?type='+encodeURIComponent(type)); const j=await r.json(); if(!j||!j.url) throw new Error('二维码失败'); return j.url; }
    function drawChart(vals){ const ctx=document.getElementById('chart').getContext('2d'); new Chart(ctx,{ type:'radar', data:{ labels:['E/I','S/N','T/F','J/P'], datasets:[{ data:vals, backgroundColor:'rgba(30,64,255,0.22)', borderColor:'#1E40FF', pointBackgroundColor:'#1E40FF', pointBorderColor:'#fff' }] }, options:{ responsive:false, scales:{ r:{ beginAtZero:true, suggestedMax:100 } }, plugins:{ legend:{display:false} } } }); }
    (async function(){
      const type = getQueryType(); if(!type){ alert('缺少类型参数'); return; }
      document.getElementById('badge').textContent = type;
      const vals = toVals(type); drawChart(vals);
      const lib = await loadLib(); const item = lib[type];
      const kw = (window.MBTI_KEYWORDS && window.MBTI_KEYWORDS[type]) ? window.MBTI_KEYWORDS[type] : [];
      document.getElementById('keywords').innerHTML = kw.map(k=>`<span class='kw'>${k}</span>`).join('');
      document.getElementById('careerDesc').textContent = item.description || '';
      document.getElementById('careerList').innerHTML = (item.careers||[]).slice(0,5).map(c=>`<li class='career'>${c}</li>`).join('');
      const link = location.origin + '/mbti/result.php?type=' + type; document.getElementById('linkText').textContent = link;
      const qr = await genQR(type); document.getElementById('qr').src = qr;
      document.getElementById('genBtn').addEventListener('click', async ()=>{
        const node = document.getElementById('stage');
        const canvas = await html2canvas(node, { backgroundColor: null, scale: 2 });
        const url = canvas.toDataURL('image/png');
        const dl = document.getElementById('dlBtn'); dl.href = url; dl.style.display='inline-block';
      });
    })();
  </script>
<?php if($hasInc){ include $docRoot.'/includes/footer.php'; } ?>
<?php if(!$hasInc){ ?>
</body>
</html>
<?php } ?>