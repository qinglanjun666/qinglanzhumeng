# 绘斓网站数据库设置说明

## T001任务完成交付

### 文件说明
- `database_init.sql` - 完整的数据库初始化脚本
- `test_queries.sql` - 验证查询脚本
- `DATABASE_SETUP.md` - 本说明文件

### 手动测试步骤

#### 1. 启动XAMPP MySQL服务
1. 打开XAMPP控制面板
2. 启动MySQL服务
3. 确认MySQL服务状态为"Running"

#### 2. 执行数据库初始化脚本
```bash
# 方法1：通过命令行
C:\xampp\mysql\bin\mysql.exe -u root -p < C:\xampp\htdocs\huilanweb\database_init.sql

# 方法2：通过phpMyAdmin
1. 访问 http://localhost/phpmyadmin
2. 点击"导入"选项卡
3. 选择 database_init.sql 文件
4. 点击"执行"
```

#### 3. 验收标准测试

##### 验收标准1：SQL脚本能在空数据库中执行并创建表与示例数据
- 执行 `database_init.sql` 后应无错误
- 数据库 `huilanweb` 应被创建
- 4个表应被成功创建：mood_types, universities, university_votes, university_likes

##### 验收标准2：universities至少包含8条示例数据
执行查询：
```sql
SELECT COUNT(*) FROM universities;
```
预期结果：应返回 10（实际插入了10条数据，超过要求的8条）

##### 验收标准3：测试JOIN查询验证外键有效性
执行查询：
```sql
SELECT u.name, m.name FROM universities u JOIN mood_types m ON u.mood_type_id=m.id LIMIT 5;
```
预期结果：应返回5行数据，显示大学名称和对应的气质类型名称

### 数据库结构概览

#### mood_types表（8条记录）
- 理性创造型、艺术梦想型、实用领导型、学者思辨型
- 社交和谐型、创新先锋型、文化传承型、国际探索型

#### universities表（10条记录）
涵盖不同省份和类型：
- 北京：清华大学、中央美术学院、北京大学、中国人民大学、北京师范大学
- 上海：复旦大学、上海交通大学
- 安徽：中国科学技术大学
- 浙江：浙江大学
- 江苏：南京大学

### 表结构特点
1. 所有外键约束正确设置
2. 包含必要的索引以提高查询性能
3. 使用UTF8MB4字符集支持中文
4. 包含唯一约束防止重复投票/点赞
5. 自动时间戳记录创建时间

### 验证完成确认
执行 `test_queries.sql` 中的所有查询，确认：
- [x] 大学数量 >= 8条
- [x] JOIN查询正常工作
- [x] 所有表结构正确创建
- [x] 示例数据覆盖不同省份与类型