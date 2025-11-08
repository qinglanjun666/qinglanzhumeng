// 统一站点底部（含法律风险与免责说明），适配本地与Apache/huilanweb
(function() {
  const BASE_PATH = window.location.pathname.includes('huilanweb') ? '/huilanweb' : '';

const style = `
.hj-footer { background:#4f79ff; color:#ffffff; border-top:1px solid rgba(255,255,255,.15); }
.hj-footer .wrap { max-width:1200px; margin:0 auto; padding:20px 16px; display:grid; gap:12px; }
.hj-footer .links { display:flex; gap:14px; flex-wrap:wrap; }
.hj-footer a { color:#f5f7ff; text-decoration:none; opacity:.95; }
.hj-footer a:hover { opacity:1; text-decoration:underline; }
.hj-footer .legal { font-size:.9rem; color:#eef2ff; line-height:1.6; }
.hj-footer .meta { font-size:.9rem; color:#e6eaff; }
@media (max-width:768px){ .hj-footer .wrap { padding:16px 12px; } }
`;

  function ensureStyle() {
    if (document.getElementById('hj-footer-style')) return;
    const s = document.createElement('style');
    s.id = 'hj-footer-style'; s.textContent = style;
    document.head.appendChild(s);
  }

  function buildFooter() {
    const f = document.createElement('footer');
    f.className = 'hj-footer';
    f.innerHTML = `
      <div class="wrap">
        <div class="links">
          <a href="${BASE_PATH}/privacy.html">隐私政策</a>
          <a href="${BASE_PATH}/terms.html">使用条款</a>
          <a href="${BASE_PATH}/disclaimer.html">免责声明</a>
        </div>
        <div class="legal">
          使用本网站即表示您同意《使用条款》与《隐私政策》。本网站内容仅供参考，不构成法律、学术或招生建议；因使用产生的风险由您自行承担。更多细则与责任限制，请参见《免责声明》。
        </div>
        <div class="meta">© 2024 绘斓网</div>
      </div>`;
    return f;
  }

  function mountFooter() {
    ensureStyle();
    const container = document.getElementById('site-footer');
    const footer = buildFooter();
    if (container) {
      container.innerHTML = '';
      container.appendChild(footer);
    } else {
      document.body.appendChild(footer);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountFooter);
  } else {
    mountFooter();
  }
})();