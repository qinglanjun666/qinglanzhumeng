## 目标与范围
- 将现有大学详情页（`c:\xampp\htdocs\huilanweb\university.html`）改造成“Welcome”文档页式布局：Header、Hero、Main Sections（简介/功能/场景/快速入门）、Footer。
- 不引入外部前端框架，复用站点现有样式与公共头尾（`top-nav.js`、`site-footer.js`、`styles.css`）。
- 数据继续来源于既有 API 与本地 JSON，保持当前交互（想读、在读、标签点赞）与 SEO 结构化数据。

## 现状与约束
- 路由与数据：大学详情通过 `GET /api/universities/{id}`（`api/index.php`:33–56），基础信息 `GET /api/universities_basic`，标签 `GET /api/university/{id}/tags`，在读 `GET/POST /api/universities/{id}/studying`。
- 头尾注入：页面底部以 JS 注入 Header/Footer（`top-nav.js`、`site-footer.js`），没有 `header.php`/`footer.php`。
- 样式：`styles.css` 已提供通用 `.section`/`.card`/`.btn`/`.chips` 等；无 Bootstrap/Tailwind。
- SEO：现有动态 SEO 更新（`updateSEO`，`university.html`:518），JSON-LD（`injectJSONLD`，`university.html`:601）。

## 阶段1：布局骨架搭建
- 在详情页主体中新增 Hero 容器与四大模块区：Intro、Features、Scenarios/Value、Get Started、Admissions、SEO FAQs。
- Hero 显示：院校 LOGO/占位图、`u.name` 为主标题、`u.one_line` 副标题、核心元信息（省/市/类型/关键词/气质）。
- CTA 按钮：映射到现有页面（`/search.html`、`/mood-map.html`），保留分享与互动按钮组。
- 交付：完整静态结构与基础样式，未联动数据的占位内容。

## 阶段2：组件化与样式整合
- 复用现有样式类，补充小范围样式：`hero`、`feature-grid`、`card-list`、`steps` 等，确保响应式与一致风格。
- 头部/页脚继续由 `top-nav.js`、`site-footer.js` 注入，保持品牌一致与导航逻辑统一。
- 交付：所有板块视觉与布局完成，手机端断点（≤768px）正常折叠。

## 阶段3：数据联动与交互
- Intro：绑定基础信息与学校简介（`loadBasicInfoByName`、`loadIntroByName`）。
- Features：将“重点专业”渲染为 `.chips` 列表；MBTI 模块显示类型、气质故事与插画（`loadMbtiByName`）。
- Scenarios/Value：呈现“官网入口”“性格标签”“校内资源”卡片；标签区加载 `GET /api/university/{id}/tags` 并支持点赞（`university_tags.php`）。
- 快速入门：三步操作文案与站内 CTA。
- 保持“想读/在读”交互（`like.php`、`university_studying.php`），并在 Hero/CTA 区同步状态与提示。
- 交付：数据全面打通，交互可用、幂等、错误提示友好。

## 阶段4：SEO与可访问性
- 更新 `title/description/keywords/OG/Twitter/Canonical`（`updateSEO`，`university.html`:518）。
- 保持/增强 JSON-LD：`CollegeOrUniversity`、`BreadcrumbList`、`FAQPage`（`injectJSONLD`、`injectFAQJSONLD`）。
- 可访问性：所有图片补全 `alt`、按钮 `aria-label`，文本对比度与焦点态，移动端可用性。
- 交付：搜索引擎友好，结构化数据正确渲染。

## 阶段5：响应式与性能
- 断点适配：多列到单列、图片缩放、导航可读性；确保 320px–1440px 范围表现稳定。
- 性能：懒加载图片、精简 DOM、避免重复请求；网络失败回退与重试（已复用 `fetchWithRetry`）。
- 交付：在主流设备与网络条件下体验稳定。

## 阶段6：规格驱动（可选，TREA 配置）
- 设计一个轻量的 JSON 配置结构，映射到用户给出的 YAML/JSON 伪规格（header/hero/sections/footer/styles/accessibility）。
- 在 `university.html` 内置解析器，优先使用运行时数据填充，缺失字段采用配置默认值；保留 i18n 可抽取能力。
- 交付：未来可以通过配置调整布局/文案，无需改动模板代码。

## 阶段7：测试与验证
- 功能自测：不同 `id` 的大学详情完整加载；“想读/在读/标签点赞”幂等与提示；MBTI/简介/入学方式回退与占位。
- 兼容性：Chrome/Edge/Firefox；移动端模拟；隐私弹窗与导航共存。
- SEO 验证：查看页面源内的 JSON-LD 与 meta 标签；Canonical 指向 `/university/{id}`。
- 交付：测试记录与问题清单，逐项修复。

## 阶段8：上线与回滚策略
- 以最小改动替换页面结构，保留原有 API 交互与 URL 结构。
- 发布后监控：错误日志与 `seo_monitor.php` 输出；如出现问题，快速回滚到原版模板或禁用新组件。
- 交付：上线说明与回滚指引。

## 验收标准
- 布局与交互严格符合“Welcome 文档页”结构，移动端表现良好。
- 详情页所有关键数据与交互可用，无 5xx/明显前端错误；SEO/JSON-LD 正确。
- 无外部前端依赖新增；与现有站点的头尾、样式、路径前缀策略一致。

## 后续可扩展
- 内容国际化抽取（`_lang` 参数与字典映射）。
- 组件复用到其他页面（搜索、榜单、图谱）。
- 配置化 A/B：不同 Hero/CTA 布局切换，用以观察 SEO/转化表现。