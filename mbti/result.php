<?php $docRoot = $_SERVER['DOCUMENT_ROOT']; $hasInc = is_file($docRoot.'/includes/header.php') && is_file($docRoot.'/includes/footer.php'); ?>
<?php if(!$hasInc){ ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MBTI 测试结果</title>
  <style>
    :root { --brand:#1E40FF; --bg:#f6f8ff; --text:#111827; --muted:#6b7280; --card:#ffffff; }
    html,body { margin:0; padding:0; background:linear-gradient(135deg,#f6f8ff,#f0f5ff); color:var(--text); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"PingFang SC","Microsoft Yahei",sans-serif; }
    .wrap { max-width: 1080px; margin: 0 auto; padding: 28px 24px; }
    .header { display:flex; align-items:center; justify-content:space-between; padding:12px 0; }
    .brand { font-size:22px; font-weight:800; color:var(--brand); }
    .back { padding:10px 16px; background:#eef2ff; color:#1f2937; border:1px solid #c7d2fe; border-radius:10px; font-weight:700; cursor:pointer; }
    .title { font-size:30px; font-weight:900; margin-top:10px; display:flex; align-items:center; gap:12px; }
    .badge { padding:8px 12px; border-radius:10px; background:var(--brand); color:#fff; font-weight:800; }
    .grid { display:grid; grid-template-columns: 1.1fr .9fr; gap:22px; margin-top:22px; }
    @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    .card { background:var(--card); border-radius:16px; box-shadow: 0 6px 22px rgba(0,0,0,.08); padding:20px; }
    .section-title { font-size:16px; font-weight:800; color:#1f2937; margin-bottom:10px; }
    .desc { color:#374151; line-height:1.8; font-size:15px; }
    .careers { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:10px; margin-top:12px; }
    @media (max-width: 640px) { .careers { grid-template-columns: 1fr; } }
    .career { padding:10px 12px; border:1px solid #e5e7eb; border-radius:12px; background:#fafafa; font-size:14px; }
    .chart-wrap { height: 360px; }
    @media (max-width: 640px) { .chart-wrap { height: 300px; } }
    .loading { display:flex; align-items:center; gap:10px; font-size:14px; color:var(--muted); }
    .spinner { width:16px; height:16px; border-radius:50%; border:2px solid #c7d2fe; border-top-color:var(--brand); animation:spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.35); }
    .modal.show { display:flex; }
    .modal .box { width:92%; max-width:480px; background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.25); padding:18px; }
    .modal .title { font-size:18px; font-weight:700; margin-bottom:8px; }
    .modal .msg { color:#374151; font-size:14px; margin-bottom:16px; }
    .modal .ok { display:inline-block; padding:10px 16px; border-radius:8px; background:var(--brand); color:#fff; font-weight:600; border:none; cursor:pointer; }
  </style>
<?php } ?>
<?php if($hasInc){ include $docRoot.'/includes/header.php'; } ?>
<?php if(!$hasInc){ ?>
</head>
<body>
<?php } ?>
  <div class="wrap">
    <div class="header">
      <div class="brand">MBTI 测试结果</div>
      <button class="back" onclick="location.href='/mbti/index.php'">返回测试</button>
    </div>
    <div class="title"><span id="typeBadge" class="badge">TYPE</span><span id="typeTitle"></span></div>
    <div class="grid">
      <div class="card">
        <div class="section-title">性格描述</div>
        <div id="desc" class="desc"></div>
        <div class="section-title" style="margin-top:16px">职业方向</div>
        <div id="careers" class="careers"></div>
      </div>
      <div class="card">
        <div class="section-title">四维图谱</div>
        <div id="chartLoading" class="loading"><div class="spinner"></div><span>图表加载中</span></div>
        <div class="chart-wrap"><canvas id="chart"></canvas></div>
      </div>
      <div class="card">
        <div class="section-title">扫描二维码查看 MBTI 结果</div>
        <div class="loading" id="qrLoading"><div class="spinner"></div><span>二维码生成中</span></div>
        <div id="qrWrap" style="display:none;">
          <img id="qrImg" alt="结果二维码" style="width:220px;height:220px;border:6px solid #000;border-radius:12px;background:#fff;" />
          <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
            <a id="qrDownload" class="back" href="#" download>保存到相册</a>
            <button class="back" id="copyLink">复制链接</button>
            <a id="posterBtn" class="back" href="#">生成朋友圈海报</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" id="errorModal" aria-hidden="true">
    <div class="box">
      <div class="title">发生错误</div>
      <div class="msg" id="errorMsg"></div>
      <button class="ok" id="errorOk">我知道了</button>
    </div>
  </div>

  <script>
    async function ensureChart(){
      if (window.Chart) return;
      await new Promise((resolve, reject)=>{
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        s.onload = ()=>resolve();
        s.onerror = ()=>reject(new Error('Chart 库加载失败'));
        document.head.appendChild(s);
      });
    }
    const errorModal = document.getElementById('errorModal');
    const errorMsg = document.getElementById('errorMsg');
    const errorOk = document.getElementById('errorOk');
    function showError(msg){ errorMsg.textContent = msg || '参数或数据错误'; errorModal.classList.add('show'); }
    errorOk.addEventListener('click',()=>{ errorModal.classList.remove('show'); });

    function getQueryType(){
      const p = new URLSearchParams(location.search);
      const t = (p.get('type') || '').toUpperCase();
      const ok = /^[IE][SN][TF][JP]$/.test(t);
      if (!ok) return null;
      return t;
    }

    function toChartValues(type){
      const letters = type.split('');
      const e = letters[0] === 'E' ? 100 : 0;
      const s = letters[1] === 'S' ? 100 : 0;
      const t = letters[2] === 'T' ? 100 : 0;
      const j = letters[3] === 'J' ? 100 : 0;
      return [e,s,t,j];
    }

    async function loadTypes(){
      const r = await fetch('/data/mbti_types.json');
      if (!r.ok) throw new Error('类型库加载失败 ' + r.status);
      return r.json();
    }

    function renderInfo(type, lib){
      const item = lib[type];
      if (!item) throw new Error('未找到类型信息');
      document.getElementById('typeBadge').textContent = type;
      document.getElementById('typeTitle').textContent = item.title;
      document.getElementById('desc').textContent = item.description;
      const careers = document.getElementById('careers');
      careers.innerHTML = '';
      (item.careers || []).forEach(c=>{
        const li = document.createElement('div');
        li.className='career';
        li.textContent = c;
        careers.appendChild(li);
      });
    }

    function renderChart(values){
      document.getElementById('chartLoading').style.display = 'none';
      const ctx = document.getElementById('chart').getContext('2d');
      new Chart(ctx, {
        type: 'radar',
        data: {
          labels: ['E/I','S/N','T/F','J/P'],
          datasets: [{
            label: 'MBTI',
            data: values,
            backgroundColor: 'rgba(30,64,255,0.20)',
            borderColor: '#1E40FF',
            pointBackgroundColor: '#1E40FF',
            pointBorderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            r: {
              beginAtZero: true,
              suggestedMin: 0,
              suggestedMax: 100,
              ticks: { stepSize: 25 }
            }
          }
        }
      });
    }

    async function generateQR(type){
      try{
        const r = await fetch('/mbti/generate_qr.php?type='+encodeURIComponent(type));
        if(!r.ok) throw new Error('二维码接口异常 '+r.status);
        const j = await r.json();
        if(!j || !j.url) throw new Error('二维码返回缺少URL');
        document.getElementById('qrImg').src = j.url;
        const a = document.getElementById('qrDownload'); a.href = j.url;
        document.getElementById('qrLoading').style.display='none';
        document.getElementById('qrWrap').style.display='block';
      }catch(e){
        document.getElementById('qrLoading').style.display='none';
        showError(e.message);
      }
    }

    (async function(){
      try {
        const type = getQueryType();
        if (!type) throw new Error('URL 缺少或包含无效的 type 参数');
        const lib = await loadTypes();
        renderInfo(type, lib);
        await ensureChart();
        renderChart(toChartValues(type));
        const link = (location.origin + '/mbti/result.php?type=' + type);
        document.getElementById('copyLink').addEventListener('click', async ()=>{
          try{
            await navigator.clipboard.writeText(link);
          }catch{}
        });
        const posterBtn = document.getElementById('posterBtn');
        posterBtn.addEventListener('click', ()=>{
          location.href = '/mbti/poster_template.php?type=' + encodeURIComponent(type);
        });
        generateQR(type);
      } catch(e){
        showError(e.message);
      }
    })();
  </script>
<?php if($hasInc){ include $docRoot.'/includes/footer.php'; } ?>
<?php if(!$hasInc){ ?>
</body>
</html>
<?php } ?>