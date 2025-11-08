-- 测试查询脚本 - 用于验证数据库创建是否成功

-- 验收标准1：检查大学数量（应该 >= 8）
SELECT COUNT(*) as university_count FROM universities;

-- 验收标准2：测试JOIN查询，验证外键关系
SELECT u.name as university_name, m.name as mood_type_name 
FROM universities u 
JOIN mood_types m ON u.mood_type_id = m.id 
LIMIT 5;

-- 额外验证查询
-- 检查气质类型数量
SELECT COUNT(*) as mood_type_count FROM mood_types;

-- 检查每个省份的大学数量
SELECT province, COUNT(*) as count 
FROM universities 
GROUP BY province 
ORDER BY count DESC;

-- 检查每种类型的大学数量
SELECT type, COUNT(*) as count 
FROM universities 
GROUP BY type 
ORDER BY count DESC;

-- 检查每种气质类型对应的大学数量
SELECT m.name as mood_type, COUNT(u.id) as university_count
FROM mood_types m
LEFT JOIN universities u ON m.id = u.mood_type_id
GROUP BY m.id, m.name
ORDER BY university_count DESC;

-- 验证表结构
DESCRIBE mood_types;
DESCRIBE universities;
DESCRIBE university_votes;
DESCRIBE university_likes;