// 顶部统一导航（动态基础路径，适配本地8080与Apache/huilanweb）
(function() {
  const BASE_PATH = window.location.pathname.includes('huilanweb') ? '/huilanweb' : '';
  const current = window.location.pathname.toLowerCase();

  const links = [
    { href: `${BASE_PATH}/index.html`, text: '首页', key: 'index.html' },
    { href: `${BASE_PATH}/assessment.html`, text: '测评', key: 'assessment.html' },
    { href: `${BASE_PATH}/mood-map.html`, text: '大学图谱', key: 'mood-map.html' },
  ];

  const style = `
    .hj-top-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: #fff; border-bottom: 1px solid #eee; }
    .hj-top-nav .wrap { max-width: 1200px; margin: 0 auto; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; }
    .hj-top-nav .brand { font-weight: 700; color: #333; text-decoration: none; font-size: 1.1rem; }
    .hj-top-nav .links { display: flex; gap: 16px; }
    .hj-top-nav a { color: #333; text-decoration: none; font-weight: 500; }
    .hj-top-nav a.active { color: #4f46e5; }
    @media (max-width: 768px) { .hj-top-nav .links { gap: 12px; } }
    body.hj-has-top-nav { padding-top: 56px; }
  `;

  function ensureStyle() {
    // 注入统一样式文件（styles.css），避免逐页修改
    if (!document.querySelector('link[href$="/styles.css"], link[href="styles.css"]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = `${BASE_PATH}/styles.css`;
      document.head.appendChild(link);
    }
    if (!document.getElementById('hj-top-nav-style')) {
      const s = document.createElement('style');
      s.id = 'hj-top-nav-style';
      s.textContent = style;
      document.head.appendChild(s);
    }
  }

  function buildNav() {
    const nav = document.createElement('header');
    nav.className = 'hj-top-nav';
    const brandHref = `${BASE_PATH}/index.html`;
    const linksHtml = links.map(l => {
      const isActive = current.endsWith('/' + l.key) || current === ('/' + l.key) || current.endsWith(l.key);
      return `<a href="${l.href}" ${isActive ? 'class="active"' : ''}>${l.text}</a>`;
    }).join('');
    nav.innerHTML = `<div class="wrap"><a class="brand" href="${brandHref}">🎨 绘斓网</a><div class="links">${linksHtml}</div></div>`;
    return nav;
  }

  function mountNav() {
    ensureStyle();
    const container = document.getElementById('top-nav');
    const nav = buildNav();
    document.body.classList.add('hj-has-top-nav');
    if (container) {
      container.innerHTML = '';
      container.appendChild(nav);
    } else {
      document.body.insertBefore(nav, document.body.firstChild);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountNav);
  } else {
    mountNav();
  }
})();