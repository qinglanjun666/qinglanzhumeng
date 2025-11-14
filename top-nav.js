// 顶部统一导航（动态基础路径，适配本地8080与Apache/huilanweb）
(function() {
  const BASE_PATH = window.location.pathname.includes('huilanweb') ? '/huilanweb' : '';
  const current = window.location.pathname.toLowerCase();

  function fetchConfig() {
    const url = `${BASE_PATH}/data/site_config.json`;
    return fetch(url).then(r => r.ok ? r.json() : {}).catch(() => ({}));
  }

  const links = [
    { href: `${BASE_PATH}/index.html`, text: '首页', key: 'index.html' },
    { href: `${BASE_PATH}/mbti/home.php`, text: 'MBTI测试', key: 'mbti/home.php' },
    { href: `${BASE_PATH}/mood-map.html`, text: '大学图谱', key: 'mood-map.html' },
    { href: `${BASE_PATH}/leaderboard.html`, text: '人格榜单', key: 'leaderboard.html' },
  ];

  const style = `
    .hj-top-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: #fff; border-bottom: 1px solid #eee; }
    .hj-top-nav .wrap { max-width: 1200px; margin: 0 auto; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; }
    .hj-top-nav .brand { display:flex; align-items:center; gap:10px; font-weight: 800; text-decoration: none; font-size: 2.2rem; letter-spacing:.02em; }
    /* 新LOGO：简洁Q字徽记（环形 + 尾巴） */
    .hj-top-nav .brand .logo-mark { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #1f2a44 0%, #33446b 100%); box-shadow: 0 8px 20px rgba(31, 42, 68, .25); position: relative; overflow: hidden; }
    .hj-top-nav .brand .logo-mark::before { content:""; position:absolute; inset:8px; border-radius:50%; background:radial-gradient(circle at 50% 50%, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 60%), linear-gradient(135deg, #409EFF 0%, #6EB5FF 100%); box-shadow: inset 0 0 0 2px rgba(255,255,255,.18); }
    .hj-top-nav .brand .logo-mark::after { content:""; position:absolute; right:8px; bottom:9px; width:12px; height:3px; border-radius:2px; background: linear-gradient(90deg, #6EB5FF, #409EFF); transform: rotate(35deg); box-shadow: 0 2px 8px rgba(64,158,255,.35); }
    .hj-top-nav .brand .brand-text { color: var(--brand-color, #4f79ff); }
    .hj-top-nav .links { display: flex; gap: 10px; }
    .hj-top-nav .links a { position: relative; display: inline-flex; align-items: center; gap: 6px; padding: 12px 14px; border-radius: 10px; color: #334; text-decoration: none; font-weight: 600; border: 1px solid transparent; transition: all .2s ease; overflow: hidden; }
    .hj-top-nav .links a:hover { background: #f6f8ff; border-color: #e5e9f5; box-shadow: 0 3px 10px rgba(102,126,234,.16); transform: translateY(-1px); }
    .hj-top-nav .links a:active { transform: translateY(0); background: #eef2ff; border-color: #c7d2fe; box-shadow: 0 2px 8px rgba(102,126,234,.18); }
    .hj-top-nav .links a.active { color: #fff; background: var(--brand-color, #4f79ff); border-color: transparent; box-shadow: 0 6px 18px rgba(102,126,234,.35); }
    .hj-top-nav .links a:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,.25); }
    /* 点击脉冲效果 */
    .hj-top-nav .links a::after { content: ""; position: absolute; left: var(--x, 50%); top: var(--y, 50%); transform: translate(-50%, -50%) scale(0); width: 8px; height: 8px; border-radius: 50%; background: rgba(102,126,234,.30); pointer-events: none; opacity: 0.9; }
    .hj-top-nav .links a.pulse::after { animation: hj-ripple .6s ease-out forwards; }
    @keyframes hj-ripple { to { transform: translate(-50%, -50%) scale(26); opacity: 0; } }
    @media (max-width: 768px) { .hj-top-nav .wrap { padding: 16px 16px; } .hj-top-nav .brand { font-size: 1.8rem; } .hj-top-nav .links { gap: 8px; } .hj-top-nav .links a { padding: 10px 12px; } }
    body.hj-has-top-nav { padding-top: 96px; }
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

  function buildNav(cfg) {
    const nav = document.createElement('header');
    nav.className = 'hj-top-nav';
    const brandHref = `${BASE_PATH}/index.html`;
    let items = (cfg && cfg.layout && cfg.layout.header && Array.isArray(cfg.layout.header.navItems) && cfg.layout.header.navItems.length)
      ? cfg.layout.header.navItems
      : [
          { href: `${BASE_PATH}/index.html`, text: '首页', key: 'index.html' },
          { href: '/mbti/home.php', text: 'MBTI测试', key: 'mbti/home.php' },
          { href: `${BASE_PATH}/mood-map.html`, text: '大学图谱', key: 'mood-map.html' },
          { href: `${BASE_PATH}/leaderboard.html`, text: '人格榜单', key: 'leaderboard.html' }
        ];
    items = items.filter(l => {
      const h = l.href || '';
      const t = l.text || '';
      return !(t.includes('测评') || h.endsWith('/assessment.html') || h.includes('/assessment.html'));
    });
    const hasMbti = items.some(l => {
      const h = (l.href || '').toLowerCase();
      const t = (l.text || '').toLowerCase();
      return h.includes('/mbti') || /mbti/.test(t);
    });
    if (!hasMbti) {
      items.unshift({ href: '/mbti/home.php', text: 'MBTI测试', key: 'mbti/home.php' });
    }
    items = items.map(l => {
      const h = l.href || '';
      const t = l.text || '';
      if (/mbti/i.test(t) || h.endsWith('/mbti.html') || h.includes('/mbti.html')) {
        return { href: '/mbti/home.php', text: 'MBTI测试', key: 'mbti/home.php' };
      }
      return l;
    });
    const normalizeKey = l => (l.key || ((l.href || '').split('/').pop() || '')).toLowerCase();
    const wanted = ['index.html','mbti/home.php','mood-map.html','leaderboard.html'];
    const bucket = {};
    items.forEach(l => { const k = normalizeKey(l); if (!bucket[k]) bucket[k] = l; });
    if (!bucket['index.html']) bucket['index.html'] = { href: `${BASE_PATH}/index.html`, text: '首页', key: 'index.html' };
    if (!bucket['mbti/home.php']) bucket['mbti/home.php'] = { href: '/mbti/home.php', text: 'MBTI测试', key: 'mbti/home.php' };
    if (!bucket['mood-map.html']) bucket['mood-map.html'] = { href: `${BASE_PATH}/mood-map.html`, text: '大学图谱', key: 'mood-map.html' };
    if (!bucket['leaderboard.html']) bucket['leaderboard.html'] = { href: `${BASE_PATH}/leaderboard.html`, text: '人格榜单', key: 'leaderboard.html' };
    items = wanted.map(k => bucket[k]).filter(Boolean);

    const linksHtml = items.map(l => {
      const key = l.key || (l.href || '').split('/').pop();
      const isActive = current.endsWith('/' + key) || current === ('/' + key) || current.endsWith(key);
      const href = (l.href || '').startsWith('/') ? `${BASE_PATH}${(l.href || '').replace('/huilanweb','')}` : l.href;
      return `<a href="${href}" ${isActive ? 'class="active"' : ''}>${l.text}</a>`;
    }).join('');
    const brandName = (cfg && cfg.layout && cfg.layout.header && cfg.layout.header.brandName) ? cfg.layout.header.brandName : '青蓝君';
    nav.innerHTML = `<div class="wrap"><a class="brand" href="${brandHref}"><span class="logo-mark" aria-hidden="true"></span><span class="brand-text">${brandName}</span></a><div class="links">${linksHtml}</div></div>`;
    const headingFont = cfg && cfg.styles && cfg.styles.font && cfg.styles.font.heading;
    if (headingFont) {
      const brandEl = nav.querySelector('.brand');
      if (brandEl) brandEl.style.fontFamily = headingFont;
    }
    return nav;
  }

  async function mountNav() {
    const cfg = await fetchConfig();
    ensureStyle();
    const container = document.getElementById('top-nav');
    const nav = buildNav(cfg);
    document.body.classList.add('hj-has-top-nav');
    if (container) {
      container.innerHTML = '';
      container.appendChild(nav);
    } else {
      document.body.insertBefore(nav, document.body.firstChild);
    }
    const brandColor = cfg && cfg.styles && cfg.styles.brandColor;
    if (brandColor) document.documentElement.style.setProperty('--brand-color', brandColor);
    const bodyFont = cfg && cfg.styles && cfg.styles.font && cfg.styles.font.body;
    if (bodyFont) document.body.style.fontFamily = bodyFont;
    nav.querySelectorAll('.links a').forEach(a => {
      a.addEventListener('pointerdown', e => {
        const rect = a.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        a.style.setProperty('--x', x + 'px');
        a.style.setProperty('--y', y + 'px');
        a.classList.remove('pulse');
        void a.offsetWidth;
        a.classList.add('pulse');
        setTimeout(() => a.classList.remove('pulse'), 650);
      }, { passive: true });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountNav);
  } else {
    mountNav();
  }
})();