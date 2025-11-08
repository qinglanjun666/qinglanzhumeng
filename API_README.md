# 绘斓网站 API 文档

## T002任务完成交付

### 📁 交付文件
- `config/database.php` - 数据库连接配置
- `models/University.php` - 大学数据模型
- `api/universities.php` - 大学列表API端点
- `api/index.php` - API路由入口
- `.htaccess` - URL重写配置
- `test_api.php` - Web界面测试工具
- `test_api_cli.php` - 命令行测试工具

### 🚀 API端点

#### GET /api/universities
获取大学列表，支持分页和筛选

**请求参数:**
- `page` (int, 可选) - 页码，默认1
- `per_page` (int, 可选) - 每页数量，默认20，最大100
- `mood_type` (string, 可选) - 气质类型slug筛选
- `q` (string, 可选) - 搜索关键字，匹配大学名称和关键词

**响应格式:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "清华大学",
      "province": "北京",
      "city": "北京",
      "type": "综合",
      "mood_type_slug": "rational_creator",
      "one_line": "自强不息，厚德载物的理工强校",
      "logo_url": null,
      "like_count": 0,
      "poll_counts": 0
    }
  ],
  "total": 10,
  "page": 1,
  "per_page": 20,
  "total_pages": 1
}
```

### 📋 验收标准确认

#### ✅ 验收标准1: 基础分页功能
**请求:** `/api/universities?page=1&per_page=20`
**预期:** 返回JSON格式数据，包含total字段

#### ✅ 验收标准2: 气质类型筛选
**请求:** `/api/universities?mood_type=rational_creator`
**预期:** 只返回属于"理性创造型"气质的大学

#### ✅ 验收标准3: 数据结构完整性
每个大学记录包含所有必需字段：
- id, name, province, city, type
- mood_type_slug, one_line, logo_url
- like_count, poll_counts

### 🧪 测试方法

#### 方法1: Web界面测试
1. 启动XAMPP的Apache和MySQL服务
2. 访问 `http://localhost/huilanweb/test_api.php`
3. 查看自动化测试结果

#### 方法2: 命令行测试
```bash
php test_api_cli.php
```

#### 方法3: cURL测试
```bash
# 基础分页测试
curl "http://localhost/huilanweb/api/universities?page=1&per_page=20"

# 气质类型筛选测试
curl "http://localhost/huilanweb/api/universities?mood_type=rational_creator"

# 搜索功能测试
curl "http://localhost/huilanweb/api/universities?q=清华"
```

#### 方法4: Postman测试
导入以下请求进行测试：
- GET `http://localhost/huilanweb/api/universities?page=1&per_page=20`
- GET `http://localhost/huilanweb/api/universities?mood_type=rational_creator`

### 🔧 技术实现要点

#### 数据库查询优化
- 使用JOIN联表查询获取气质类型信息
- 使用子查询计算like_count和poll_counts
- 支持LIMIT/OFFSET分页
- 添加索引提高查询性能

#### 参数验证
- 页码和每页数量的范围验证
- mood_type参数的有效性验证
- SQL注入防护（使用PDO预处理语句）

#### 错误处理
- 数据库连接错误处理
- 参数验证错误返回
- 统一的JSON错误响应格式

#### CORS支持
- 设置跨域访问头部
- 支持前端JavaScript调用

### 📊 性能特性
- 支持分页减少数据传输量
- 使用索引优化查询速度
- PDO连接池复用数据库连接
- 参数化查询防止SQL注入

### 🔄 扩展性设计
- 模块化的MVC架构
- 可配置的数据库连接
- 统一的API响应格式
- 易于添加新的筛选条件

T002任务已完成，API功能已实现并可通过多种方式进行测试验证。