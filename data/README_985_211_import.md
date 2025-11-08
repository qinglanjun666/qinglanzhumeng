# 985/211 大学导入说明

使用 `admin_import_universities.php` 管理端接口批量导入所有中国的 985/211 大学基础信息，并为每所大学设置对应的气质类型（mood_slug），以便与测评结果关联推荐。

## 接口
- 路径：`POST /api/admin/import/universities`
- 认证：Header `X-Admin-Password: zxasqw123456`（或 form 字段 `password`）
- 字段：支持上传文件 `file` 或表单文本 `csv_text`
- 可选参数：`match_by=name|external_id`（默认 name）

## CSV 模板
使用仓库中的 `data/universities_985_211_template.csv`，包含如下列：

```
name,province,city,type,mood_slug,keywords,one_line,logo_url,external_id,tags
```

示例：
```
清华大学,北京,北京,综合,rational_creator,工程|科技|创新,自强不息，厚德载物,,985_001
北京大学,北京,北京,综合,scholarly_thinker,人文|社科|学术,思想自由，兼容并包,,985_002
```

## mood_slug 可选值
- `rational_creator` 理性创造型
- `artistic_dreamer` 文艺探索型
- `practical_leader` 务实领导型
- `scholarly_thinker` 学者思辨型
- `social_harmony` 社交和谐型
- `innovative_pioneer` 创新先锋型
- `cultural_guardian` 文化传承型
- `global_explorer` 国际探索型

## tags 列说明（可选）
- 用途：性格标签作为推荐加权项；导入时会自动写入 `personality_tags`，并建立 `university_personality_tags` 映射（依赖 `universities_basic`，若不存在会按省市/type/keywords自动补全基础记录）。
- 格式：使用 `|`、`,` 或 `;` 分隔，如：`985|工程强|创新驱动|严谨务实`
- 去重：导入器会去重同一学校的重复标签。

## 导入示例命令（表单文本）
```
curl -X POST "http://localhost/huilanweb/api/admin/import/universities" \
  -H "X-Admin-Password: zxasqw123456" \
  -F "match_by=external_id" \
  -F "csv_text=$(cat data/universities_985_211_template.csv)"
```

## 注意事项
- 优先使用 `external_id` 作为唯一键进行更新（不变更名称也能更新记录）
- 若数据库 `universities.external_id` 列不存在，接口会自动回退到 `name` 匹配并返回警告
- `logo_url` 可留空，前端会使用占位图
- 保持 `keywords` 使用竖线分隔（如 `工程|科技|创新`），用于前端检索
 - `tags` 为可选列，导入器将自动创建缺失的标签并建立映射