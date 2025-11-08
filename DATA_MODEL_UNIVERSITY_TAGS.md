# 数据系统 · 大学与性格标签数据模型

模块：数据系统  执行顺序：2  依赖：基础架构

## 目标
- 设计大学数据模型（基础字段）
- 设计性格标签数据模型（基础字段）
- 提供数据库表结构示例（MySQL）与静态 JSON 示例数据

## 数据表设计（MySQL 示例）

注意：为确保中文不乱码，建议在导入时执行 `SET NAMES utf8mb4;`，并使用 `DEFAULT CHARSET=utf8mb4`。

```sql
-- 建议在导入前执行
SET NAMES utf8mb4;

-- 大学表：名称、地区、性质、层次、重点专业
CREATE TABLE IF NOT EXISTS universities_basic (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL COMMENT '大学名称',
  region VARCHAR(100) NOT NULL COMMENT '地区，如北京、上海、浙江-杭州',
  nature VARCHAR(50) NOT NULL COMMENT '性质，如公立/私立',
  level VARCHAR(100) NOT NULL COMMENT '层次，如双一流/985/211/本科/研究型',
  key_majors JSON NULL COMMENT '重点专业（JSON数组）',
  PRIMARY KEY (id),
  UNIQUE KEY uniq_university_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 性格标签表：标签名、描述
CREATE TABLE IF NOT EXISTS personality_tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tag_name VARCHAR(100) NOT NULL COMMENT '标签名',
  description VARCHAR(500) NULL COMMENT '标签描述',
  PRIMARY KEY (id),
  UNIQUE KEY uniq_tag_name (tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 可选：大学与性格标签的多对多关系（推荐）
CREATE TABLE IF NOT EXISTS university_personality_tags (
  university_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (university_id, tag_id),
  CONSTRAINT fk_upt_university FOREIGN KEY (university_id) REFERENCES universities_basic(id) ON DELETE CASCADE,
  CONSTRAINT fk_upt_tag FOREIGN KEY (tag_id) REFERENCES personality_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

字段说明：
- `universities_basic.name`：大学名称（唯一）
- `universities_basic.region`：地区（省/市/城市）
- `universities_basic.nature`：性质（公立、私立等）
- `universities_basic.level`：层次（双一流、985、211、本科、研究型等）
- `universities_basic.key_majors`：重点专业（JSON 数组，例如 ["计算机科学", "机械工程"])；如数据库不支持 JSON，可改为 `TEXT` 存储 JSON 字符串
- `personality_tags.tag_name`：标签名（唯一）
- `personality_tags.description`：标签描述

## JSON 数据结构示例

示例文件路径：
- `data/universities.sample.json`
- `data/personality_tags.sample.json`

### universities.sample.json
```json
[
  {
    "id": 1,
    "name": "清华大学",
    "region": "北京",
    "nature": "公立",
    "level": "双一流/985/211",
    "key_majors": ["计算机科学", "电子信息", "土木工程", "机械工程"]
  },
  {
    "id": 2,
    "name": "北京大学",
    "region": "北京",
    "nature": "公立",
    "level": "双一流/985/211",
    "key_majors": ["数学", "物理学", "医学", "法学"]
  }
]
```

### personality_tags.sample.json
```json
[
  { "id": 1, "tag_name": "研究导向", "description": "偏好科研氛围与学术深度的学习者" },
  { "id": 2, "tag_name": "创新实践", "description": "注重动手能力与项目驱动的学习者" },
  { "id": 3, "tag_name": "社交领导", "description": "具备组织协调与领导力倾向的学习者" },
  { "id": 4, "tag_name": "艺术探索", "description": "偏好艺术设计与人文表达的学习者" }
]
```

## 示例数据集说明
- 以上 JSON 可直接作为静态数据源用于前端占位展示或初始填充。
- 若用于数据库初始化：
  - 建议将 `universities_basic.key_majors` 以 JSON 格式写入（MySQL 5.7+ 支持 JSON），或改用 `TEXT` 存储字符串。
  - 插入数据前执行 `SET NAMES utf8mb4;`，确保中文不乱码。

## 落地建议
- 若需与现有 API 集成，建议新增或扩展接口：
  - `GET /api/universities_basic`、`GET /api/personality_tags`
  - 以及 `GET /api/university/{id}/tags`（基于多对多关系表）
- 初期可沿用静态 JSON 文件作为数据源，后续再迁移到数据库表。