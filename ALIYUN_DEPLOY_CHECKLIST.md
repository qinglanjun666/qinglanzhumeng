# 阿里云部署与上线自检清单（Huilanweb）

## 环境准备
- 操作系统与网络：确认实例安全组开放 `80`/`443`（HTTP/HTTPS）。
- Web服务器：安装并启用 `Apache`（或 Nginx），支持 `.htaccess` 重写规则。
- PHP运行环境：PHP 8.x（或 ≥7.4），启用扩展 `pdo_mysql`、`curl`、`json`、`mbstring`。
- 数据库：MySQL 5.7+/8.0+，创建数据库用户并授予 `SELECT/INSERT/UPDATE/DELETE` 权限。
- 域名与证书：配置站点域名与 HTTPS 证书（Let’s Encrypt 或企业证书）。

## 代码与配置
- 代码部署：将项目目录同步至服务器站点根，如 `/var/www/html/huilanweb`。
- 虚拟主机根：站点根需包含 `huilanweb/` 子路径或调整虚拟主机以将 `huilanweb` 设为根。
- URL重写：启用 `AllowOverride All` 并确认 `.htaccess` 生效（API通过 `/api/...` 路由工作）。
- 数据库配置：更新 `config/database.php` 中主机、库名、用户、密码为生产环境。
- 环境变量：
  - `HJ_ADMIN_PASSWORD`：管理端密码（默认 `admin123`，请修改）。
  - 如需SEO监控鉴权：`HJ_ADMIN_API_KEY`（可选，若设置需在请求头 `X-Admin-Key` 携带）。
  - 如需外部服务（AI等）请在 `config/ai.php` 填写对应密钥与域名。

## 数据初始化
- 首次部署执行 `database_init.sql`：创建表结构与示例数据。
- 验证关键表：`universities`、`mood_types`、`university_likes`、`university_votes`、`analytics_events`。
- 若已有历史数据：执行差异迁移，避免覆盖生产数据。

## 应用验证（后端）
- 列表API：`GET /api/universities` 返回 200，分页与筛选可用。
- 详情API：`GET /api/universities/{id}` 返回 200，结构完整（含 `vote_distribution`）。
- 点赞API：`POST /api/universities/{id}/like` 返回 200；重复点赞拒绝计数增加。
- 投票API：`POST /api/universities/{id}/vote` 返回 200；重复同选项不增计数，更改投票成功。
- 测评API：`GET /api/assessment/questions` 返回 200；`POST /api/assessment/submit` 正常。
- 管理导出：`GET /api/admin/analytics/export` 携带 `X-Admin-Password` 返回 CSV。
- 管理导入：`POST /api/admin/import/universities` 携带 `X-Admin-Password` 与 CSV 成功导入。
- SEO监控：`POST /api/seo_monitor.php` 返回 JSON；如需监控生产站，更新 `api/seo_monitor.php` 内 `baseUrl` 指向生产域名。

## 应用验证（前端）
- 页面可达：主页 `index.html`、搜索页 `search.html`、大学页 `university.html`、测评页 `assessment.html`、站点地图 `sitemap.php` 返回 200。
- 导航与交互：顶部导航跳转正常；投票与点赞前端调用成功且响应提示正确。
- SEO要素：页面 `<title>`、`meta description`、OG 标签、`canonical` 存在且适配生产域名。
- 隐私与法律：`privacy.html`、`terms.html`、`disclaimer.html` 可达且文案最新。

## 安全与运维
- 管理密码：更改默认 `admin123`，并通过安全渠道保存。
- 访问日志与错误日志：启用并定位到指定目录，验证写入权限。
- 文件权限：确保 `www-data`（或运行用户）可读项目文件；不授予写权限除非必要。
- 备份策略：数据库与代码定期备份（快照或逻辑备份）。
- 监控与报警：站点健康检查（HTTP 200）、错误率、数据库连接异常告警。

## 上线前自检清单（勾选）
- [ ] API与页面核心入口均返回 200。
- [ ] 点赞与投票端到端验证通过（含重复与更改投票）。
- [ ] 管理导出/导入验证通过且数据落库正确。
- [ ] SEO监控接口返回正常，产出基本报告。
- [ ] `.htaccess` 重写工作正常，路由 `/api/...` 无 404/405 异常。
- [ ] HTTPS 有效，证书链完整，强制跳转到 https。
- [ ] 环境变量与密钥已配置，默认密码已变更。
- [ ] 错误日志无致命错误，访问日志无大量 4xx/5xx。
- [ ] 备份与监控配置完成。

## 备注
- 若使用 Nginx，需将 `.htaccess` 规则转换为 `try_files` 与 `location` 配置以支持 `/api` 路由。
- 生产环境请避免保留开发基准数据；如需保留，建议标记并在 UI 中隐藏示例项。