<?php $docRoot = $_SERVER['DOCUMENT_ROOT']; $hasInc = is_file($docRoot.'/includes/header.php') && is_file($docRoot.'/includes/footer.php'); ?>
<?php if(!$hasInc){ ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MBTI 测试</title>
  <style>
    :root { --brand:#1E40FF; --bg:#f7f9fc; --text:#111827; --muted:#6b7280; --card:#ffffff; }
    html,body { margin:0; padding:0; background:var(--bg); color:var(--text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, "PingFang SC", "Microsoft Yahei", sans-serif; }
    .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
    .header { display:flex; align-items:center; justify-content:space-between; padding:12px 0; }
    .brand { font-size:20px; font-weight:600; color:var(--brand); }
    .desc { color:var(--muted); font-size:14px; }
    .actions { display:flex; gap:12px; }
    .btn { padding:10px 16px; background:var(--brand); color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
    .btn[disabled] { opacity:.6; cursor:not-allowed; }
    .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:16px; margin-top:16px; }
    @media (max-width: 1024px) { .grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } .wrap { padding:16px; } }
    .card { background:var(--card); border-radius:12px; box-shadow: 0 6px 20px rgba(0,0,0,.06); padding:16px; display:flex; flex-direction:column; gap:12px; }
    .qid { color:var(--muted); font-size:12px; }
    .qtext { font-size:16px; line-height:1.6; }
    .opts { display:grid; grid-template-columns: repeat(5, 1fr); gap:8px; }
    .opt { position:relative; }
    .opt input { position:absolute; opacity:0; pointer-events:none; }
    .opt label { display:block; text-align:center; padding:10px; border-radius:10px; background:#eef2ff; color:#1f2937; font-weight:600; cursor:pointer; transition:all .2s ease; }
    .opt input:checked + label { background:var(--brand); color:#fff; box-shadow: 0 8px 24px rgba(30,64,255,.35); }
    .footer { position:sticky; bottom:0; background:rgba(247,249,252,.92); backdrop-filter:saturate(120%) blur(6px); padding:16px; border-top:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
    .status { color:var(--muted); font-size:14px; }
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
  <link rel="preconnect" href="//127.0.0.1:8000">
<?php } ?>
<?php if($hasInc){ include $docRoot.'/includes/header.php'; } ?>
<?php if(!$hasInc){ ?>
</head>
<body>
<?php } ?>
  <div class="wrap">
    <div class="header">
      <div>
        <div class="brand">MBTI 测试</div>
        <div class="desc">请选择每题的同意程度，完成后提交计算类型。</div>
      </div>
      <div class="actions">
        <button class="btn" id="reloadBtn">重新加载题库</button>
      </div>
    </div>

    <div id="loading" class="loading"><div class="spinner"></div><span>题库加载中</span></div>
    <div id="questions" class="grid" style="display:none"></div>
  </div>

  <div class="footer">
    <div class="status" id="status">未提交</div>
    <button class="btn" id="submitBtn" disabled>提交答案</button>
  </div>

  <div class="modal" id="errorModal" aria-hidden="true">
    <div class="box">
      <div class="title">发生错误</div>
      <div class="msg" id="errorMsg"></div>
      <button class="ok" id="errorOk">我知道了</button>
    </div>
  </div>

  <script>
    const questionsEl = document.getElementById('questions');
    const loadingEl = document.getElementById('loading');
    const submitBtn = document.getElementById('submitBtn');
    const statusEl = document.getElementById('status');
    const reloadBtn = document.getElementById('reloadBtn');
    const errorModal = document.getElementById('errorModal');
    const errorMsg = document.getElementById('errorMsg');
    const errorOk = document.getElementById('errorOk');

    function showError(msg){ errorMsg.textContent = msg || '请求失败'; errorModal.classList.add('show'); }
    errorOk.addEventListener('click',()=>{ errorModal.classList.remove('show'); });

    const BASE = window.location.pathname.includes('huilanweb') ? '/huilanweb' : '';

    async function loadQuestions(){
      loadingEl.style.display = 'flex';
      questionsEl.style.display = 'none';
      submitBtn.disabled = true;
      statusEl.textContent = '未提交';
      try {
        const r = await fetch(BASE + '/api/mbti/questions.php', { method:'GET' });
        if (!r.ok) throw new Error('题库接口返回异常 ' + r.status);
        const data = await r.json();
        if (!Array.isArray(data) || data.length === 0) throw new Error('题库为空');
        renderQuestions(data);
        loadingEl.style.display = 'none';
        questionsEl.style.display = 'grid';
        submitBtn.disabled = false;
      } catch(e){
        loadingEl.style.display = 'none';
        showError(e.message);
      }
    }

    function renderQuestions(items){
      questionsEl.innerHTML = '';
      for (const q of items){
        const card = document.createElement('div');
        card.className = 'card';
        const qid = document.createElement('div');
        qid.className = 'qid';
        qid.textContent = '#' + q.id + ' · ' + q.dimension;
        const qtext = document.createElement('div');
        qtext.className = 'qtext';
        qtext.textContent = q.question;
        const opts = document.createElement('div');
        opts.className = 'opts';
        const values = [2,1,0,-1,-2];
        const labels = ['+2 非常同意','+1 同意','0 中立','-1 不同意','-2 非常不同意'];
        values.forEach((v,i)=>{
          const opt = document.createElement('div'); opt.className='opt';
          const input = document.createElement('input');
          input.type='radio'; input.name='q'+q.id; input.value = String(v); input.id = 'q'+q.id+'_'+v;
          const label = document.createElement('label');
          label.setAttribute('for', input.id);
          label.textContent = labels[i];
          opt.appendChild(input); opt.appendChild(label);
          opts.appendChild(opt);
        });
        card.appendChild(qid); card.appendChild(qtext); card.appendChild(opts);
        questionsEl.appendChild(card);
      }
    }

    async function submitAnswers(){
      statusEl.textContent = '提交中';
      submitBtn.disabled = true;
      try {
        const answers = {};
        const cards = questionsEl.querySelectorAll('.card');
        cards.forEach(card=>{
          const qidText = card.querySelector('.qid').textContent;
          const id = qidText.split('·')[0].trim().replace('#','');
          const sel = card.querySelector('input[type=radio]:checked');
          const val = sel ? parseInt(sel.value,10) : 0;
          answers[id] = val;
        });
        const r = await fetch(BASE + '/api/mbti/submit.php', {
          method:'POST',
          headers: { 'Content-Type':'application/json' },
          body: JSON.stringify({ answers })
        });
        if (!r.ok) throw new Error('提交接口返回异常 ' + r.status);
        const data = await r.json();
        if (!data || !data.type) throw new Error('返回数据缺少类型');
        statusEl.textContent = '已计算类型 ' + data.type;
        const BASE = window.location.pathname.includes('huilanweb') ? '/huilanweb' : '';
        window.location.href = BASE + '/mbti/result?type=' + encodeURIComponent(data.type);
      } catch(e){
        showError(e.message);
        statusEl.textContent = '提交失败';
      } finally {
        submitBtn.disabled = false;
      }
    }

    reloadBtn.addEventListener('click', loadQuestions);
    submitBtn.addEventListener('click', submitAnswers);
    loadQuestions();
  </script>
<?php if($hasInc){ include $docRoot.'/includes/footer.php'; } ?>
<?php if(!$hasInc){ ?>
</body>
</html>
<?php } ?>