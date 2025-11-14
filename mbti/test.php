<?php $docRoot = $_SERVER['DOCUMENT_ROOT']; $hasInc = is_file($docRoot.'/includes/header.php') && is_file($docRoot.'/includes/footer.php'); ?>
<?php if(!$hasInc){ ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MBTI 分页测评</title>
<?php } ?>
  <style>
    :root { --brand:#1E40FF; --bg:#f7f9fc; --text:#111827; --muted:#6b7280; --card:#ffffff; }
    html,body { margin:0; padding:0; background:var(--bg); color:var(--text); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"PingFang SC","Microsoft Yahei",sans-serif; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .header { display:flex; align-items:center; justify-content:space-between; padding:12px 0; }
    .brand { font-size:20px; font-weight:700; color:var(--brand); }
    .desc { color:var(--muted); font-size:14px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px; }
    @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    .card { background:var(--card); border-radius:12px; box-shadow: 0 6px 20px rgba(0,0,0,.06); padding:16px; display:flex; flex-direction:column; gap:12px; }
    .qid { color:var(--muted); font-size:12px; }
    .qtext { font-size:16px; line-height:1.6; }
    .opts { display:grid; grid-template-columns: repeat(5, 1fr); gap:8px; }
    .opt { position:relative; }
    .opt input { position:absolute; opacity:0; pointer-events:none; }
    .opt label { display:block; text-align:center; padding:10px; border-radius:10px; background:#eef2ff; color:#1f2937; font-weight:600; cursor:pointer; transition:all .2s ease; }
    .opt input:checked + label { background:var(--brand); color:#fff; box-shadow: 0 8px 24px rgba(30,64,255,.35); }
    .toolbar { position:sticky; top:0; z-index:10; background:rgba(247,249,252,.92); backdrop-filter:saturate(120%) blur(6px); padding:12px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .progressbar { width:100%; height:10px; background:#e5e7eb; border-radius:999px; overflow:hidden; }
    .progressbar .inner { height:100%; background:linear-gradient(90deg,#1E40FF,#6EB5FF); width:0%; }
    .footer { position:sticky; bottom:0; background:rgba(247,249,252,.92); backdrop-filter:saturate(120%) blur(6px); padding:16px; border-top:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
    .status { color:var(--muted); font-size:14px; }
    .encourage { color:#1E40FF; font-size:14px; font-weight:700; }
    .btn { padding:12px 18px; background:var(--brand) !important; color:#fff !important; border:1px solid #1E3A8A; border-radius:10px; font-weight:800; font-size:16px; cursor:pointer; box-shadow:0 10px 24px rgba(30,64,255,.35); transition:transform .12s ease, box-shadow .2s ease, filter .2s ease; position:relative; overflow:hidden; }
    .btn-primary { background:linear-gradient(135deg,#3b5cff,#6b8cff) !important; color:#fff !important; border-color:#1E3A8A; }
    .btn-outline { background:transparent !important; color:var(--brand) !important; border-color:var(--brand); box-shadow:none; }
    .btn-outline:hover { background:#eef2ff !important; }
    .btn-outline:active { transform:translateY(0.5px) scale(0.995); }
    .btn:hover { filter:brightness(1.06); box-shadow:0 12px 28px rgba(30,64,255,.38); }
    .btn:active { transform:translateY(0.5px) scale(0.995); box-shadow:0 8px 20px rgba(30,64,255,.32); }
    .btn:focus-visible { outline:none; box-shadow:0 0 0 3px rgba(30,64,255,.30), 0 10px 24px rgba(30,64,255,.35); }
    .btn::after { content:""; position:absolute; left:var(--x,50%); top:var(--y,50%); transform:translate(-50%,-50%) scale(0); width:10px; height:10px; border-radius:50%; background:rgba(255,255,255,.45); pointer-events:none; opacity:0.9; }
    .btn.pulse::after { animation:hj-btn-ripple .6s ease-out forwards; }
    @keyframes hj-btn-ripple { to { transform:translate(-50%,-50%) scale(26); opacity:0; } }
    .btn[disabled] { opacity:.6; cursor:not-allowed; box-shadow:none; }
    .nav { display:flex; gap:10px; }
    .loading { display:flex; align-items:center; gap:10px; font-size:14px; color:var(--muted); }
    .spinner { width:16px; height:16px; border-radius:50%; border:2px solid #c7d2fe; border-top-color:var(--brand); animation:spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.35); }
    .modal.show { display:flex; }
    .modal .box { width:92%; max-width:480px; background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.25); padding:18px; }
    .modal .title { font-size:18px; font-weight:700; margin-bottom:8px; }
    .modal .msg { color:#374151; font-size:14px; margin-bottom:16px; }
    .modal .ok { display:inline-block; padding:10px 16px; border-radius:8px; background:var(--brand); color:#fff; font-weight:600; border:none; cursor:pointer; }
    @media (max-width: 768px) {
      .header { flex-direction:column; align-items:flex-start; gap:8px; }
      .toolbar { flex-direction:column; align-items:stretch; gap:10px; }
      .toolbar > div:first-child { width:100%; display:grid; grid-template-columns: 1fr; row-gap:8px; }
      .toolbar .btn { width:100%; }
      .progressbar { height:12px; }
      .desc { font-size:13px; }
      .card { padding:14px; }
      .opts { grid-template-columns: 1fr 1fr; }
    }
  </style>
<?php if(!$hasInc){ ?>
</head>
<body>
<?php } ?>
<?php if($hasInc){ include $docRoot.'/includes/header.php'; } ?>
  <div class="wrap">
    <div class="header">
      <div>
        <div class="brand">MBTI 分页测评</div>
        <div class="desc">每页 6 题，共 20 页；支持返回修改；必须全部完成才能提交。</div>
      </div>
      <div id="pageInfo" class="desc"></div>
    </div>
    <div class="toolbar">
      <div style="display:flex; align-items:center; gap:10px;">
        <button class="btn btn-outline" id="prevBtn">上一页</button>
        <button class="btn btn-primary" id="nextBtn">下一页</button>
        <button class="btn btn-primary" id="submitBtn" style="display:none;">提交答案</button>
      </div>
      <div style="flex:1; display:flex; align-items:center; gap:10px;">
        <div class="progressbar"><div id="progressInner" class="inner"></div></div>
        <div id="progressText" class="desc"></div>
        <div id="encourageText" class="encourage"></div>
      </div>
    </div>
    <div id="loading" class="loading"><div class="spinner"></div><span>题库加载中</span></div>
    <div id="questions" class="grid" style="display:none"></div>
  </div>
  <div class="footer"><div class="status" id="status">未提交</div></div>

  <div class="modal" id="errorModal" aria-hidden="true">
    <div class="box">
      <div class="title">提示</div>
      <div class="msg" id="errorMsg"></div>
      <button class="ok" id="errorOk">我知道了</button>
    </div>
  </div>

  <script>
    const questionsEl = document.getElementById('questions');
    const loadingEl = document.getElementById('loading');
    const submitBtn = document.getElementById('submitBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const statusEl = document.getElementById('status');
    const pageInfo = document.getElementById('pageInfo');
    const errorModal = document.getElementById('errorModal');
    const errorMsg = document.getElementById('errorMsg');
    const errorOk = document.getElementById('errorOk');

    function showError(msg){ errorMsg.textContent = msg || '请求失败'; errorModal.classList.add('show'); }
    errorOk.addEventListener('click',()=>{ errorModal.classList.remove('show'); });

    const urlParams = new URLSearchParams(location.search);
    const PER_PAGE = 6;
    const TOTAL_PAGES = 20;
    let currentSlice = [];
    let allQuestions = [];
    let page = parseInt(urlParams.get('page')||'1',10);
    if (isNaN(page) || page<1) page = 1; if (page>TOTAL_PAGES) page = TOTAL_PAGES;

    function loadAnswers(){
      try { const raw = localStorage.getItem('mbti_answers'); return raw ? JSON.parse(raw) : {}; } catch { return {}; }
    }
    function saveAnswers(map){ try { localStorage.setItem('mbti_answers', JSON.stringify(map)); } catch {} }

    async function loadQuestions(){
      loadingEl.style.display = 'flex'; questionsEl.style.display = 'none';
      try {
        const r = await fetch('/api/mbti/questions.php');
        if (!r.ok) throw new Error('题库接口返回异常 ' + r.status);
        const data = await r.json();
        allQuestions = data;
        if (!Array.isArray(data) || data.length !== 120) throw new Error('题库异常');
        const start = (page-1)*PER_PAGE; const end = start+PER_PAGE; const slice = data.slice(start,end);
        renderQuestions(slice);
        currentSlice = slice;
        pageInfo.textContent = `第 ${page}/${TOTAL_PAGES} 页`;
        prevBtn.disabled = page===1; nextBtn.style.display = page<TOTAL_PAGES ? 'inline-block' : 'none'; submitBtn.style.display = page===TOTAL_PAGES ? 'inline-block' : 'none';
        if (page < TOTAL_PAGES) { updateNextDisabled(currentSlice); } else { updateSubmitDisabled(allQuestions); }
        updateProgress();
        loadingEl.style.display = 'none'; questionsEl.style.display = 'grid';
      } catch(e){ loadingEl.style.display='none'; showError(e.message); }
    }

    function renderQuestions(items){
      questionsEl.innerHTML = '';
      const answers = loadAnswers();
      for (const q of items){
        const card = document.createElement('div'); card.className='card';
        const qid = document.createElement('div'); qid.className='qid'; qid.textContent = '#' + q.id + ' · ' + q.dimension;
        const qtext = document.createElement('div'); qtext.className='qtext'; qtext.textContent = q.question;
        const opts = document.createElement('div'); opts.className='opts';
        const values = [2,1,0,-1,-2]; const labels = ['+2 非常同意','+1 同意','0 中立','-1 不同意','-2 非常不同意'];
        values.forEach((v,i)=>{
          const opt = document.createElement('div'); opt.className='opt';
          const input = document.createElement('input'); input.type='radio'; input.name='q'+q.id; input.value=String(v); input.id='q'+q.id+'_'+v;
          const label = document.createElement('label'); label.setAttribute('for', input.id); label.textContent = labels[i];
          if (answers[String(q.id)] !== undefined && parseInt(answers[String(q.id)],10) === v) input.checked = true;
          opt.appendChild(input); opt.appendChild(label); opts.appendChild(opt);
        });
        opts.addEventListener('change', (e)=>{
          const target = e.target; if (target && target.type==='radio'){
            const map = loadAnswers(); map[String(q.id)] = parseInt(target.value,10); saveAnswers(map);
            if (page < TOTAL_PAGES) { updateNextDisabled(currentSlice); } else { updateSubmitDisabled(allQuestions); }
            updateProgress();
          }
        });
        card.appendChild(qid); card.appendChild(qtext); card.appendChild(opts); questionsEl.appendChild(card);
      }
    }

    function goPage(n){ const base = '/mbti/test.php?page=' + n; location.href = base; }

  function updateProgress(){
      const all = Array.isArray(allQuestions) ? allQuestions : [];
      const map = loadAnswers();
      let answered = 0;
      for (const q of all){ const v = map[String(q.id)]; if (!(v===undefined || v===null || isNaN(parseInt(v,10)))) answered++; }
      const pct = all.length ? Math.round((answered / all.length) * 100) : 0;
      const inner = document.getElementById('progressInner'); if (inner) inner.style.width = pct + '%';
      const text = document.getElementById('progressText'); if (text) text.textContent = `已完成 ${answered}/120（${pct}%）`;
      let pageDone = true;
      let remaining = 0;
      for (const q of currentSlice){ const v = map[String(q.id)]; if (v===undefined || v===null || isNaN(parseInt(v,10))) { pageDone = false; remaining++; } }
      let msg = '';
      if (page === TOTAL_PAGES && answered === all.length) { msg = '全部完成，可以提交答案'; }
      else if (pageDone && page < TOTAL_PAGES) { msg = '本页已完成，点击下一页继续'; }
      else { msg = remaining === 1 ? '只剩最后 1 题' : `还剩 ${remaining} 题，加油！`; }
      const en = document.getElementById('encourageText'); if (en) en.textContent = msg;
  }

    function updateNextDisabled(items){
      const map = loadAnswers();
      let done = true;
      for (const q of items){ const v = map[String(q.id)]; if (v===undefined || v===null || isNaN(parseInt(v,10))) { done = false; break; } }
      nextBtn.disabled = !done;
    }

    function updateSubmitDisabled(all){
      const map = loadAnswers();
      let done = true;
      for (const q of all){ const v = map[String(q.id)]; if (v===undefined || v===null || isNaN(parseInt(v,10))) { done = false; break; } }
      submitBtn.disabled = !done;
    }

    prevBtn.addEventListener('click', ()=>{ if (page>1) goPage(page-1); });
    nextBtn.addEventListener('click', ()=>{ if (page<TOTAL_PAGES) goPage(page+1); });
    [prevBtn, nextBtn, submitBtn].forEach(b=>{
      b.addEventListener('pointerdown', e=>{
        const rect = b.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        b.style.setProperty('--x', x + 'px');
        b.style.setProperty('--y', y + 'px');
        b.classList.remove('pulse');
        void b.offsetWidth;
        b.classList.add('pulse');
        setTimeout(()=>b.classList.remove('pulse'), 650);
      }, { passive: true });
    });

    async function submitAll(){
      statusEl.textContent = '校验中';
      try {
        const r = await fetch('/api/mbti/questions.php'); const data = await r.json();
        const ids = Array.isArray(data) ? data.map(x=>String(x.id)) : [];
        const map = loadAnswers();
        let missing = [];
        for (const id of ids){ if (map[id] === undefined || map[id] === null || isNaN(parseInt(map[id],10))) missing.push(id); }
        if (missing.length>0){ showError('你还有题目未完成，请返回继续作答'); statusEl.textContent = '未提交'; return; }
        statusEl.textContent = '提交中';
        const resp = await fetch('/api/mbti/submit.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ answers: map }) });
        if (!resp.ok){ const txt = await resp.text(); throw new Error('提交失败 ' + resp.status + ' ' + txt); }
        const j = await resp.json(); if (!j || !j.type) throw new Error('返回缺少类型');
        localStorage.removeItem('mbti_answers');
        location.href = '/mbti/result.php?type=' + encodeURIComponent(j.type);
      } catch(e){ showError(e.message); statusEl.textContent = '提交失败'; }
    }

    submitBtn.addEventListener('click', submitAll);
    loadQuestions();
  </script>
<?php if($hasInc){ include $docRoot.'/includes/footer.php'; } ?>
<?php if(!$hasInc){ ?>
</body>
</html>
<?php } ?>