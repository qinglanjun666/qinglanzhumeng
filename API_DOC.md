# API_DOC.md

---

# 绘斓网站 API 文档（T001-T015 全覆盖）

## 目录
1. API端点总览
2. 请求参数与示例
3. 错误码表
4. 数据库表结构
5. 示例数据
6. 前端路由与页面映射

---

## 1. API端点总览

| 端点 | 方法 | 功能描述 |
|------|------|----------|
| /api/universities | GET | 获取大学列表，支持分页/筛选 |
| /api/universities/{id} | GET | 获取大学详情 |
| /api/universities/{id}/like | POST | 点赞大学 |
| /api/universities/{id}/vote | POST | 投票选择大学气质 |
| /api/universities_basic | GET | 获取大学基础信息（name/region/nature/level/key_majors） |
| /api/personality_tags | GET | 获取性格标签列表（tag_name/description） |
| /api/university/{id}/tags | GET | 获取指定大学关联的性格标签 |
| /api/mood_types | GET | 获取气质类型列表 |
| /api/assessment/questions | GET | 获取测评题目与选项 |
| /api/assessment/submit | POST | 提交测评答案，返回气质类型与推荐大学 |
| /api/admin/import/universities | POST | 管理端导入/更新大学数据（CSV，支持 tags 列） |
| /api/admin/import/questions | POST | 管理端导入/更新测评题库（JSON） |
| /api/admin/analytics/export | GET | 管理端导出埋点事件（CSV） |

---

## 2. 请求参数与示例

### 2.1 获取大学列表
- GET /api/universities
- 参数：page, per_page, mood_type, q
- 示例：
  - /api/universities?page=1&per_page=20
  - /api/universities?mood_type=rational_creator
- 响应：
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

### 2.2 获取大学详情
- GET /api/universities/{id}
- 参数：id（路径参数）
- 示例：/api/universities/1
- 响应：
```json
{
  "id": 1,
  "name": "清华大学",
  "province": "北京市",
  "city": "北京市",
  "type": "综合类",
  "one_line": "自强不息，厚德载物",
  "keywords": "理工科,研究型,985,211",
  "logo_url": "https://example.com/tsinghua_logo.png",
  "mood_type": {
    "id": 1,
    "slug": "rational_creator",
    "name": "理性创造者",
    "short_desc": "逻辑思维强，善于创新",
    "color": "#3B82F6"
  },
  "like_count": 156,
  "vote_distribution": {
    "rational_creator": 45,
    "artistic_explorer": 10,
    "social_connector": 8,
    "nature_lover": 3,
    "adventure_seeker": 2,
    "peaceful_thinker": 7
  }
}
```

### 2.3 点赞大学
- POST /api/universities/{id}/like
- 参数：id（路径参数）
- 响应：
```json
{ "success": true, "message": "Liked successfully" }
```

### 2.4 投票大学气质
- POST /api/universities/{id}/vote
- 参数：id（路径参数），mood_slug（body参数）
- 示例：
```json
{ "mood_slug": "rational_creator" }
```
- 响应：
```json
{ "success": true, "message": "Vote recorded" }
```

### 2.5 获取气质类型列表
- GET /api/mood_types
- 响应：
```json
{
  "success": true,
  "message": "气质类型数据获取成功",
  "data": {
    "mood_types": [
      { "id": 1, "slug": "rational_creator", "name": "理性创造型", "short_desc": "逻辑思维强，善于创新", "color": "#3B82F6" }
      // ...
    ]
  },
  "timestamp": "2024-12-01 12:00:00"
}
```

### 2.6 获取测评题目与选项
- GET /api/assessment/questions
- 响应：
```json
{
  "success": true,
  "message": "Assessment questions retrieved successfully",
  "data": {
    "questions": [
      { "question_id": 1, "question_text": "你更喜欢的学习方式是？", "options": [ { "option_id": 1, "option_text": "独立钻研" }, ... ] }
      // ...
    ]
  }
}
```

### 2.7 提交测评答案
- POST /api/assessment/submit
- 参数：answers（数组，选项ID列表）
- 示例：
```json
{ "answers": [1,5,9,13,17,21,25] }
```
- 响应：
```json
{
  "success": true,
  "message": "Assessment completed successfully",
  "user_mood": { "id": 1, "slug": "rational_creator", "name": "理性创造型", "short_desc": "逻辑思维强，善于创新", "color": "#3B82F6" },
  "matched_universities": [ { "id": 1, "name": "清华大学", ... } ],
  "statistics": { "total_answers": 7, "matched_count": 3, "primary_matches": 2, "mood_scores": [ { "mood_type_id": 1, "total_weight": 15 } ] }
}
```

### 2.8 管理端导入大学数据
- POST /api/admin/import/universities
- 参数：CSV文件，password（表单或Header）
 - CSV列：`name,province,city,type,mood_slug,keywords,one_line,logo_url,external_id,tags`
 - 行为：当存在 `tags` 列时，系统会自动：
   - 将标签写入 `personality_tags`（不存在则创建）
   - 依据大学名称补全/创建 `universities_basic` 基础记录（region/nature/level/key_majors）
   - 建立 `university_personality_tags` 多对多映射（避免重复插入）
- 响应：
```json
{
  "success": true,
  "inserted_count": 8,
  "updated_count": 2,
  "failure_count": 0,
  "errors": [],
  "warnings": ["external_id column present but not used (fallback to name)"]
}
```

### 2.9 管理端导出埋点事件
- GET /api/admin/analytics/export
- 参数：event_type, from, to, limit, password
- 响应：CSV文件下载

---

## 3. 错误码表

| HTTP状态码 | 错误码/字段 | 含义 |
|------------|------------|------|
| 400 | Invalid input | 输入参数错误 |
| 400 | Invalid university ID | 大学ID无效 |
| 404 | University not found | 未找到大学 |
| 405 | Method not allowed | 请求方法不支持 |
| 401 | Unauthorized | 管理端密码错误 |
| 500 | Database error | 数据库错误 |
| 500 | Server error | 服务器内部错误 |
| 200 | success | 操作成功 |

---

## 4. 数据库表结构

### mood_types
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| slug | VARCHAR(50) | 唯一标识 |
| name | VARCHAR(80) | 类型名称 |
| short_desc | VARCHAR(255) | 简短描述 |
| color | VARCHAR(7) | 主题色 |
| created_at | DATETIME | 创建时间 |

### universities
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| name | VARCHAR(200) | 大学名称 |
| province | VARCHAR(100) | 省份 |
| city | VARCHAR(100) | 城市 |
| type | VARCHAR(80) | 类型 |
| mood_type_id | INT | 气质类型ID |
| keywords | VARCHAR(255) | 关键词 |
| one_line | VARCHAR(255) | 一句话简介 |
| external_id | VARCHAR(128) | 外部ID（可选） |
| logo_url | VARCHAR(255) | logo链接 |
| created_at | DATETIME | 创建时间 |

### university_votes
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| university_id | INT | 大学ID |
| mood_type_id | INT | 气质类型ID |
| client_id | VARCHAR(128) | 用户ID |
| created_at | DATETIME | 创建时间 |

### university_likes
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| university_id | INT | 大学ID |
| client_id | VARCHAR(128) | 用户ID |
| created_at | DATETIME | 创建时间 |

### analytics_events
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| event_type | VARCHAR(50) | 事件类型 |
| entity_id | INT | 相关实体ID |
| client_id | VARCHAR(128) | 用户ID |
| ip | VARCHAR(64) | IP地址 |
| user_agent | VARCHAR(255) | UA信息 |
| created_at | DATETIME | 创建时间 |
| meta | TEXT | 事件元数据 |

### assessment_questions
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| question_text | TEXT | 题目内容 |
| question_order | INT | 排序 |
| is_active | BOOLEAN | 是否启用 |
| created_at | DATETIME | 创建时间 |

### assessment_options
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| question_id | INT | 题目ID |
| option_text | TEXT | 选项内容 |
| option_order | INT | 排序 |

### assessment_option_weights
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| option_id | INT | 选项ID |
| mood_type_id | INT | 气质类型ID |
| weight | INT | 权重 |

---

## 5. 示例数据

### mood_types
| id | slug | name | short_desc | color |
|----|------|------|------------|-------|
| 1 | rational_creator | 理性创造型 | 注重理性与创新 | #3B82F6 |
| 2 | artistic_explorer | 艺术梦想型 | 追求艺术与探索 | #F59E42 |
| ... | ... | ... | ... | ... |

### universities
| id | name | province | city | type | mood_type_id | keywords | one_line |
|----|------|---------|------|------|--------------|----------|----------|
| 1 | 清华大学 | 北京 | 北京 | 综合 | 1 | 工程,科技,创新,理工 | 自强不息，厚德载物的理工强校 |
| 2 | 中央美术学院 | 北京 | 北京 | 艺术 | 2 | 美术,设计,艺术,创作 | 中国美术教育的最高学府 |
| ... | ... | ... | ... | ... | ... | ... | ... |

---

## 6. 前端路由与页面映射

| 页面 | 路径 | 关联API |
|------|------|---------|
| assessment.html | /assessment.html | /api/assessment/questions, /api/assessment/submit |
| university.html | /university/{id} | /api/universities/{id} |
| admin_import.html | /admin_import.html | /api/admin/import/universities |
| admin_analytics.html | /admin_analytics.html | /api/admin/analytics/export |
| index.html | /index.html | /api/universities |
| 数据基础信息（新） | 无页面直达 | /api/universities_basic |
| 性格标签（新） | 无页面直达 | /api/personality_tags |
| 大学标签映射（新） | /university/{id} | /api/university/{id}/tags |

.htaccess 路由映射：
- /university/{id} => university.html?id={id}
- /api/* => api/index.php

---

## 7. 示例响应：测评题目（中文）

- 接口：`GET /api/assessment/questions`
- 说明：返回所有启用的测评题目及其选项，文本为中文。

示例：
```
{
  "success": true,
  "message": "Assessment questions retrieved successfully",
  "data": {
    "questions": [
      {
        "id": 1,
        "question_text": "你更喜欢的学习方式是？",
        "question_order": 1,
        "options": [
          {"id": 1, "option_text": "通过实验和动手实践来学习", "option_order": 1},
          {"id": 2, "option_text": "通过阅读和深度思考来学习", "option_order": 2},
          {"id": 3, "option_text": "通过讨论和交流来学习", "option_order": 3},
          {"id": 4, "option_text": "通过创作和表达来学习", "option_order": 4}
        ]
      },
      {
        "id": 2,
        "question_text": "在团队合作中，你通常扮演什么角色？",
        "question_order": 2,
        "options": [
          {"id": 5, "option_text": "技术专家，负责解决具体问题", "option_order": 1},
          {"id": 6, "option_text": "思想家，提供创新理念和方向", "option_order": 2},
          {"id": 7, "option_text": "协调者，促进团队沟通合作", "option_order": 3},
          {"id": 8, "option_text": "领导者，制定计划并推动执行", "option_order": 4}
        ]
      }
      // ... 题目3-7省略
    ],
    "statistics": {"total_questions": 7, "total_options": 28}
  }
}
```

字符集与编码说明：
- 数据库：`utf8mb4` 字符集与 `utf8mb4_unicode_ci` 排序规则。
- 导入：使用 `SET NAMES utf8mb4;` 确保客户端与服务器一致。
- 接口输出：`Content-Type: application/json; charset=utf-8` 且使用 `JSON_UNESCAPED_UNICODE`。

> 文档覆盖 T001-T015 所有接口、表结构、错误码、前端页面与路由，便于后续开发者与 Claude 快速查阅与扩展。
### 2.8 获取大学基础信息
- GET /api/universities_basic
- 参数：page, per_page, region, nature, level, q
- 示例：
  - /api/universities_basic?page=1&per_page=10
  - /api/universities_basic?region=华北&level=双一流
- 响应：
```json
{
  "success": true,
  "data": {
    "total": 6,
    "page": 1,
    "per_page": 10,
    "universities": [
      {
        "id": 1,
        "name": "清华大学",
        "region": "华北",
        "nature": "公立",
        "level": "双一流",
        "key_majors": ["计算机科学与技术", "电子信息工程"]
      }
    ]
  }
}
```

### 2.9 获取性格标签
- GET /api/personality_tags
- 参数：无
- 示例：/api/personality_tags
- 响应：
```json
{
  "success": true,
  "data": {
    "tags": [
      {"id": 1, "tag_name": "研究导向", "description": "偏好理论与学术研究…"}
    ],
    "total_tags": 6
  }
}
```

### 2.10 获取某大学的关联性格标签
- GET /api/university/{id}/tags
- 参数：id（路径参数）
- 示例：/api/university/1/tags
- 响应：
```json
{
  "success": true,
  "data": {
    "university_id": 1,
    "tags": [
      {"id": 1, "tag_name": "研究导向", "description": "偏好理论与学术研究…"}
    ],
    "total_tags": 2
}
}
### 2.11 管理端导入测评题库
- POST /api/admin/import/questions
- 参数：password（Header `X-Admin-Password` 或 JSON 字段 `admin_password`）
- 负载格式：
{
  "admin_password": "<REPLACE_WITH_ADMIN_PASSWORD>",
  "questions": [
    {
      "question_text": "示例问题文本",
      "options": [
        {
          "option_text": "选项A",
          "weights": [
            {"mood_type_id": 1, "weight": 3},
            {"mood_type_id": 6, "weight": 1}
          ]
        },
        {"option_text": "选项B", "weights": [{"mood_type_id": 3, "weight": 3}]},
        {"option_text": "选项C", "weights": [{"mood_type_id": 5, "weight": 3}]},
        {"option_text": "选项D", "weights": [{"mood_type_id": 4, "weight": 3}]}
      ]
    }
  ]
}
- 响应：
{
  "success": true,
  "inserted_questions": 30,
  "inserted_options": 120,
  "inserted_weights": 200,
  "errors": [],
  "warnings": []
}
- 说明：题库导入后的获取接口为 `GET /api/assessment/questions`（默认随机返回5题，支持 `limit` 与 `all=true`），提交通道为 `POST /api/assessment/submit`（现需5个答案）。
```