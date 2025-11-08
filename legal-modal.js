// 轻量法律弹窗脚本：在任意页面以弹窗展示 用户协议/隐私政策/免责声明
// 使用：在页面中引入 <script src="legal-modal.js"></script>
// 然后调用 openLegalModal('privacy'|'terms'|'disclaimer')
(function(){
  const base = window.location.pathname.includes('huilanweb') ? '/huilanweb' : '';
  const routes = {
    privacy: base + '/privacy.html',
    terms: base + '/terms.html',
    disclaimer: base + '/disclaimer.html'
  };

  function ensureModal(){
    let modal = document.getElementById('legalModal');
    if (modal) return modal;
    modal = document.createElement('div');
    modal.id = 'legalModal';
    modal.style.position = 'fixed';
    modal.style.inset = '0';
    modal.style.background = 'rgba(0,0,0,.4)';
    modal.style.display = 'none';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '9999';
    modal.innerHTML = '<div id="legalBox" style="background:#fff;border-radius:12px;border:1px solid #eee;padding:1rem;width:min(860px,92vw);max-height:86vh;overflow:auto;"></div>';
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    return modal;
  }
  function showModal(html){
    const modal = ensureModal();
    const box = document.getElementById('legalBox');
    box.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;margin-bottom:.6rem;"><h3 style="margin:0">法律与隐私</h3><button id="legalClose" style="padding:.4rem .7rem;border-radius:8px;border:1px solid #ddd;background:#fff;cursor:pointer;">关闭</button></div>' + html;
    document.getElementById('legalClose').addEventListener('click', closeModal);
    modal.style.display = 'flex';
  }
  function closeModal(){ const modal = document.getElementById('legalModal'); if (modal) modal.style.display = 'none'; }

  async function fetchMain(url){
    const res = await fetch(url);
    const text = await res.text();
    // 提取 main 内容
    const m = text.match(/<main[^>]*>([\s\S]*?)<\/main>/i);
    return m ? m[1] : text;
  }

  async function openLegalModal(type){
    const url = routes[type];
    if (!url) return;
    try { const html = await fetchMain(url); showModal(html); } catch { showModal('<p>加载失败，请稍后重试。</p>'); }
  }

  // 可选：自动绑定 data-legal
  function bindFooterLegalLinks(){
    document.querySelectorAll('[data-legal]').forEach(el => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        const type = el.getAttribute('data-legal');
        openLegalModal(type);
      });
    });
  }

  window.openLegalModal = openLegalModal;
  window.bindFooterLegalLinks = bindFooterLegalLinks;
})();