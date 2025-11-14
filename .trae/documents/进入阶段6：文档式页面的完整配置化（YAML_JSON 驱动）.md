## 阶段选择
- 依据你的追加需求（导航菜单与品牌样式配置化已完成），建议进入“阶段6：规格驱动（可选，TREA 配置）”。
- 目标：不仅让 Header/品牌可配置，还让 Hero、Sections、Footer 与样式全部通过 YAML/JSON 规范生成，从而做到“改配置不改代码”。

## 阶段6目标
- 定义并实现一份页面配置（JSON/YAML）映射到你给出的规范：`layout.header`、`layout.hero`、`sections[]`（`textImage`、`featureGrid`、`cardList`、`steps`）、`footer`、`styles`、`accessibility`。
- 页面实例（大学详情）在运行时解析配置并渲染对应组件，缺省时回退到现有数据（API 与本地 JSON）。

## 技术实施
- 配置文件：扩展 `data/site_config.json`（或支持 `site_config.yml`）加入：
  - `layout.hero`: `background`、`headline`、`subheadline`、`ctaButtons[]`
  - `sections[]`: 支持 `intro`/`features`/`value`/`getStarted`/`admissions` 的 type 与字段
  - `footer.links[]` 与 `footer.copyright`
  - `styles.brandColor`、`styles.font.*`、`styles.breakpoints`、`accessibility.*`
- 解析器与渲染：在 `university.html` 增加配置解析函数：
  - 读取配置 → 合并运行时数据（大学详情/基础信息/MBTI/入学方式）→ 生成 Hero/各 Section DOM
  - `featureGrid` 映射 chips/图标 + 标题 + 描述；`cardList` 渲染图片/标题/说明；`steps` 渲染多步与 CTA
  - 按 `styles` 更新 `--brand-color` 与字体；按 `breakpoints` 调整响应式栅格
  - 按 `accessibility` 强制图片 `alt` 与按钮 `aria-label`
- 头尾：
  - Header/Brand/导航已配置化；Footer 读取 `footer.links` 与 `copyright`
- i18n：预留国际化（如 `_lang=en`），在解析时兼容不同语言文案字段（如 `title_en`/`title_zh` 或 `i18n` 映射）。

## 验证与交付
- 验证：切换不同配置（品牌色、导航项、Hero 文案、Section 组合）页面正确渲染；无 404/5xx；移动端正常。
- 交付：
  - 扩展后的 `data/site_config.json`
  - `university.html` 的配置解析与组件渲染逻辑
  - 现有 `top-nav.js`、`site-footer.js` 与样式对齐更新（已接入品牌色与字体）。

## 验收标准
- 不改代码即可通过配置切换布局与文案；Header/Hero/Sections/Footer 全部受控。
- 可访问性达标（alt/aria/对比度），响应式在手机/平板/桌面表现稳定。
- 与站点现有 API/本地数据融合，无用户可感知的交互回退问题。

如确认进入阶段6，我将扩展配置并在 `university.html` 完成解析与渲染实现。