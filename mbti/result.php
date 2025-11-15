<?php $docRoot = $_SERVER['DOCUMENT_ROOT']; $hasInc = is_file($docRoot.'/includes/header.php') && is_file($docRoot.'/includes/footer.php'); ?>
<?php if(!$hasInc){ ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MBTI 测试结果</title>
  <style>
    :root{ --brand:#1E40FF; --text:#111827; --muted:#6b7280; --bg:#f7f8fb; }
    html,body{ margin:0; padding:0; background:var(--bg); color:var(--text); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"PingFang SC","Microsoft Yahei",sans-serif; }
    .wrap { max-width: 1080px; margin: 0 auto; padding: 24px; }
    .hero{ max-width:960px; margin:24px auto 0; padding:28px 24px; background:linear-gradient(135deg,#eef2ff,#f7f8ff); border:1px solid #e6eaff; border-radius:16px; }
    .hero .title{ display:flex; align-items:flex-end; gap:12px; }
    .hero .badge{ display:inline-block; padding:8px 12px; border-radius:10px; background:var(--brand); color:#fff; font-weight:800; }
    .hero .subtitle{ margin:8px 0 0; color:#3b4a8b; }
    .grid2{ max-width:1080px; margin:18px auto 0; display:grid; grid-template-columns:1.2fr .8fr; gap:18px; }
    @media (max-width: 960px){ .grid2{ grid-template-columns:1fr; } }
    .section{ background:#fff; border:1px solid #e9ecef; border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,.05); padding:18px; }
    .section h2{ margin:0 0 8px; font-size:20px; }
    .section h3{ margin:16px 0 6px; font-size:16px; color:#374151; }
    .section p{ color:#334e68; }
    .section ul{ margin:0; padding-left:20px; }
    .sidebar-card{ background:#fff; border:1px solid #e9ecef; border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,.05); padding:18px; }
    .chips{ margin:8px 0 0; }
    .chip{ display:inline-block; padding:6px 10px; margin:0 8px 8px 0; background:#f5f7ff; border:1px solid #e3e8ff; border-radius:999px; font-size:13px; color:#3b4a8b; }
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
  <div id="top-nav"></div>
  <div class="wrap">
    <div class="hero">
      <div class="title"><span id="typeBadge" class="badge">TYPE</span><h1 id="typeTitle" style="font-size:40px;margin:0"></h1></div>
      <div id="typeAlias" class="subtitle"></div>
    </div>
    <div class="grid2">
      <div>
        <section class="section">
          <h2>类型解读</h2>
          <div id="overview"></div>
          <h3>优势</h3>
          <ul id="strengths"></ul>
          <h3>可能的盲点</h3>
          <ul id="pitfalls"></ul>
          <h3>工作风格</h3>
          <ul id="work"></ul>
          <h3>沟通偏好</h3>
          <ul id="communication"></ul>
          <h3>学习建议</h3>
          <ul id="learning"></ul>
          <h3>适合领域</h3>
          <ul id="careers"></ul>
          <div style="font-size:12px;color:#6b7280;margin-top:14px;">说明：以上描述基于人格偏好维度进行概括，用于自我了解与沟通参考，不用于任何诊断或选拔性结论。</div>
        </section>
      </div>
      <aside>
        <div class="sidebar-card">
          <div style="font-weight:700;">核心特质</div>
          <div id="traits" class="chips"></div>
        </div>
        <div class="sidebar-card" style="margin-top:12px;">
          <div class="section-title" style="margin:0 0 8px">四维图谱</div>
          <div id="chartLoading" class="loading"><div class="spinner"></div><span>图表加载中</span></div>
          <div class="chart-wrap"><canvas id="chart"></canvas></div>
        </div>
        <div class="sidebar-card" style="margin-top:12px;">
          <div class="section-title" style="margin:0 0 8px">扫描二维码查看 MBTI 结果</div>
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
      </aside>
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
      const urls = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
        'https://unpkg.com/chart.js@4.4.4/dist/chart.umd.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js'
      ];
      for (const url of urls){
        try {
          await new Promise((resolve, reject)=>{
            const s = document.createElement('script');
            s.src = url;
            s.crossOrigin = 'anonymous';
            s.referrerPolicy = 'no-referrer';
            s.onload = ()=>resolve();
            s.onerror = ()=>reject(new Error('load fail'));
            document.head.appendChild(s);
          });
          if (window.Chart) return;
        } catch {}
      }
      throw new Error('Chart 库加载失败');
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

    async function loadDetails(BASE){
      const r = await fetch(BASE + '/data/mbti_details.json');
      if (!r.ok) throw new Error('类型详情加载失败 ' + r.status);
      return r.json();
    }

    function renderInfo(type, details){
      const item = details[type];
      if (!item) throw new Error('未找到类型信息');
      document.getElementById('typeBadge').textContent = type;
      document.getElementById('typeTitle').textContent = (item.alias || type);
      document.getElementById('typeAlias').textContent = '';
      const setList = (id, arr)=>{ const ul=document.getElementById(id); ul.innerHTML=''; (arr||[]).forEach(t=>{ const li=document.createElement('li'); li.textContent=t; ul.appendChild(li); }); };
      const ov = document.getElementById('overview');
      ov.innerHTML = '';
      if(item.overview){ const p=document.createElement('p'); p.textContent=item.overview; ov.appendChild(p); }
      setList('strengths', item.strengths||[]);
      setList('pitfalls', item.pitfalls||[]);
      setList('work', item.work||[]);
      setList('communication', item.communication||[]);
      setList('learning', item.learning||[]);
      setList('careers', item.careers||[]);
      const traits = document.getElementById('traits'); traits.innerHTML=''; (item.traits||[]).forEach(t=>{ const s=document.createElement('span'); s.className='chip'; s.textContent=t; traits.appendChild(s); });
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

    function renderSimpleRadar(values){
      document.getElementById('chartLoading').style.display = 'none';
      const wrap = document.querySelector('.chart-wrap');
      const canvas = document.getElementById('chart');
      const dpr = window.devicePixelRatio || 1;
      const w = Math.max(220, wrap.clientWidth);
      const h = Math.max(220, wrap.clientHeight);
      canvas.width = Math.floor(w * dpr);
      canvas.height = Math.floor(h * dpr);
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      const ctx = canvas.getContext('2d');
      ctx.setTransform(dpr,0,0,dpr,0,0);
      const cx = w/2, cy = h/2;
      const r = Math.min(w,h)/2 - 22;
      const labels = ['E/I','S/N','T/F','J/P'];
      const angs = [-Math.PI/2, 0, Math.PI/2, Math.PI];
      ctx.clearRect(0,0,w,h);
      ctx.strokeStyle = '#e5e7eb';
      for(let i=1;i<=4;i++){ const rr = (r*i)/4; ctx.beginPath(); ctx.arc(cx,cy,rr,0,Math.PI*2); ctx.stroke(); }
      ctx.strokeStyle = '#cbd5e1';
      angs.forEach(a=>{ ctx.beginPath(); ctx.moveTo(cx,cy); ctx.lineTo(cx + r*Math.cos(a), cy + r*Math.sin(a)); ctx.stroke(); });
      ctx.fillStyle = '#1E40FF';
      ctx.font = '12px system-ui, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      labels.forEach((lab,i)=>{ const a = angs[i]; const tx = cx + (r+12)*Math.cos(a); const ty = cy + (r+12)*Math.sin(a); ctx.fillStyle = '#3b4a8b'; ctx.fillText(lab, tx, ty); });
      const pts = values.map((v,i)=>{ const a = angs[i]; const rr = (v/100)*r; return [cx + rr*Math.cos(a), cy + rr*Math.sin(a)]; });
      ctx.beginPath(); ctx.moveTo(pts[0][0], pts[0][1]); for(let i=1;i<pts.length;i++){ ctx.lineTo(pts[i][0], pts[i][1]); }
      ctx.closePath();
      ctx.fillStyle = 'rgba(30,64,255,0.20)';
      ctx.strokeStyle = '#1E40FF';
      ctx.lineWidth = 2;
      ctx.fill();
      ctx.stroke();
    }

    async function generateQR(type, BASE){
      try{
        const r = await fetch(BASE + '/mbti/generate_qr.php?type='+encodeURIComponent(type));
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
        const BASE = location.pathname.includes('huilanweb') ? '/huilanweb' : '';
        const type = getQueryType();
        if (!type) throw new Error('URL 缺少或包含无效的 type 参数');
        const details = await loadDetails(BASE);
        renderInfo(type, details);
        try { await ensureChart(); renderChart(toChartValues(type)); }
        catch { renderSimpleRadar(toChartValues(type)); }
        const link = (location.origin + BASE + '/mbti/result?type=' + type);
        document.getElementById('copyLink').addEventListener('click', async ()=>{
          try{ await navigator.clipboard.writeText(link); }catch{}
        });
        const posterBtn = document.getElementById('posterBtn');
        posterBtn.addEventListener('click', ()=>{
          location.href = BASE + '/mbti/poster_template.php?type=' + encodeURIComponent(type);
        });
        generateQR(type, BASE);
      } catch(e){
        showError(e.message);
      }
    })();
  </script>
  <div id="site-footer"></div>
  <script src="../top-nav.js"></script>
  <script src="../site-footer.js"></script>
<?php if($hasInc){ include $docRoot.'/includes/footer.php'; } ?>
<?php if(!$hasInc){ ?>
</body>
</html>
<?php } ?>