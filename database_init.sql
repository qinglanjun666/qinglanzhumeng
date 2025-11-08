-- 绘斓网站数据库初始化脚本
-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS huilanweb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE huilanweb;
-- 确保客户端与连接字符集为UTF-8
SET NAMES utf8mb4;

-- 删除已存在的表（按依赖关系倒序）
DROP TABLE IF EXISTS assessment_option_weights;
DROP TABLE IF EXISTS assessment_options;
DROP TABLE IF EXISTS assessment_questions;
DROP TABLE IF EXISTS university_likes;
DROP TABLE IF EXISTS university_votes;
DROP TABLE IF EXISTS analytics_events;
DROP TABLE IF EXISTS universities;
DROP TABLE IF EXISTS mood_types;

-- 1. 创建气质类型表
CREATE TABLE mood_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(80) NOT NULL,
    short_desc VARCHAR(255) NOT NULL,
    color VARCHAR(7) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. 创建大学表
CREATE TABLE universities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    type VARCHAR(80) NOT NULL,
    mood_type_id INT NOT NULL,
    keywords VARCHAR(255) NOT NULL,
    one_line VARCHAR(255) NOT NULL,
    external_id VARCHAR(128) UNIQUE NULL,
    logo_url VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mood_type_id) REFERENCES mood_types(id)
);

-- 3. 创建大学投票表
CREATE TABLE university_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    university_id INT NOT NULL,
    mood_type_id INT NOT NULL,
    client_id VARCHAR(128) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id),
    FOREIGN KEY (mood_type_id) REFERENCES mood_types(id),
    UNIQUE KEY unique_vote (university_id, client_id)
);

-- 4. 创建大学点赞表
CREATE TABLE university_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    university_id INT NOT NULL,
    client_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(64) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id),
    UNIQUE KEY unique_like (university_id, client_id)
);

-- 5. 创建基础埋点事件表
CREATE TABLE analytics_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    client_id VARCHAR(128) NULL,
    ip VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    meta TEXT NULL
);

-- 5. 创建测评题目表
CREATE TABLE assessment_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_text TEXT NOT NULL,
    question_order INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. 创建测评选项表
CREATE TABLE assessment_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    option_order INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE
);

-- 7. 创建选项权重表
CREATE TABLE assessment_option_weights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    option_id INT NOT NULL,
    mood_type_id INT NOT NULL,
    weight INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES assessment_options(id) ON DELETE CASCADE,
    FOREIGN KEY (mood_type_id) REFERENCES mood_types(id),
    UNIQUE KEY unique_option_mood (option_id, mood_type_id)
);

-- 插入气质类型示例数据
INSERT INTO mood_types (slug, name, short_desc, color) VALUES
('rational_creator', '理性创造型', '注重逻辑思维与创新实践，善于将理论转化为现实', '#3498db'),
('artistic_dreamer', '艺术梦想型', '富有想象力和创造力，追求美感与个性表达', '#e74c3c'),
('practical_leader', '实用领导型', '注重实际效果，具备强烈的责任感和领导能力', '#2ecc71'),
('scholarly_thinker', '学者思辨型', '热爱知识探索，善于深度思考和学术研究', '#9b59b6'),
('social_harmonizer', '社交和谐型', '重视人际关系，善于沟通协调和团队合作', '#f39c12'),
('innovative_pioneer', '创新先锋型', '勇于突破传统，追求前沿技术和新兴领域', '#1abc9c'),
('cultural_guardian', '文化传承型', '注重传统文化传承，具有深厚的人文底蕴', '#34495e'),
('global_explorer', '国际探索型', '具有国际视野，热衷于跨文化交流与合作', '#e67e22');

-- 插入大学示例数据
INSERT INTO universities (name, province, city, type, mood_type_id, keywords, one_line, logo_url) VALUES
('清华大学', '北京', '北京', '综合', 1, '工程,科技,创新,理工', '自强不息，厚德载物的理工强校', NULL),
('中央美术学院', '北京', '北京', '艺术', 2, '美术,设计,艺术,创作', '中国美术教育的最高学府', NULL),
('北京大学', '北京', '北京', '综合', 4, '人文,社科,学术,思辨', '思想自由，兼容并包的学术殿堂', NULL),
('复旦大学', '上海', '上海', '综合', 3, '管理,经济,领导力,实用', '博学而笃行，切问而近思', NULL),
('中国人民大学', '北京', '北京', '综合', 5, '人文,社会,和谐,沟通', '人民共和国建设者的摇篮', NULL),
('中国科学技术大学', '安徽', '合肥', '理工', 6, '科技,创新,前沿,突破', '红专并进，理实交融的科技先锋', NULL),
('北京师范大学', '北京', '北京', '师范', 7, '教育,师范,传承,人文', '学为人师，行为世范', NULL),
('上海交通大学', '上海', '上海', '综合', 1, '工程,交通,国际,创新', '饮水思源，爱国荣校', NULL),
('浙江大学', '浙江', '杭州', '综合', 6, '创新,求是,多元,前沿', '求是创新的综合性研究型大学', NULL),
('南京大学', '江苏', '南京', '综合', 4, '学术,研究,诚朴,思辨', '诚朴雄伟，励学敦行', NULL);

-- 创建索引以提高查询性能
CREATE INDEX idx_universities_mood_type ON universities(mood_type_id);
CREATE INDEX idx_universities_province ON universities(province);
CREATE INDEX idx_universities_type ON universities(type);
CREATE INDEX idx_votes_university ON university_votes(university_id);
CREATE INDEX idx_votes_client ON university_votes(client_id);
CREATE INDEX idx_likes_university ON university_likes(university_id);
CREATE INDEX idx_likes_client ON university_likes(client_id);
CREATE INDEX idx_analytics_event_type ON analytics_events(event_type);
CREATE INDEX idx_analytics_entity ON analytics_events(entity_id);
CREATE INDEX idx_analytics_client ON analytics_events(client_id);
CREATE INDEX idx_analytics_created_at ON analytics_events(created_at);
CREATE INDEX idx_assessment_questions_order ON assessment_questions(question_order);
CREATE INDEX idx_assessment_options_question ON assessment_options(question_id);
CREATE INDEX idx_assessment_weights_option ON assessment_option_weights(option_id);

-- 插入测评题目数据
INSERT INTO assessment_questions (question_text, question_order) VALUES
('你更喜欢的学习方式是？', 1),
('在团队合作中，你通常扮演什么角色？', 2),
('你对未来职业的期望是？', 3),
('你更倾向于选择哪种课外活动？', 4),
('面对挑战时，你的第一反应是？', 5),
('你认为大学最重要的收获应该是？', 6),
('你更喜欢什么样的校园环境？', 7);

-- 插入测评选项数据
-- 题目1：学习方式
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(1, '通过实验和动手实践来学习', 1),
(1, '通过阅读和深度思考来学习', 2),
(1, '通过讨论和交流来学习', 3),
(1, '通过创作和表达来学习', 4);

-- 题目2：团队角色
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(2, '技术专家，负责解决具体问题', 1),
(2, '思想家，提供创新理念和方向', 2),
(2, '协调者，促进团队沟通合作', 3),
(2, '领导者，制定计划并推动执行', 4);

-- 题目3：职业期望
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(3, '成为某个领域的技术专家', 1),
(3, '从事研究工作，探索未知领域', 2),
(3, '在国际舞台上发挥影响力', 3),
(3, '创造有艺术价值的作品', 4);

-- 题目4：课外活动
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(4, '科技创新竞赛和实验室研究', 1),
(4, '学术讲座和读书会', 2),
(4, '社团活动和志愿服务', 3),
(4, '艺术创作和文化活动', 4);

-- 题目5：面对挑战
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(5, '分析问题，寻找技术解决方案', 1),
(5, '深入研究，从理论角度理解', 2),
(5, '寻求帮助，与他人协作解决', 3),
(5, '跳出框架，寻找创新突破', 4);

-- 题目6：大学收获
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(6, '扎实的专业技能和实践能力', 1),
(6, '深厚的学术素养和研究能力', 2),
(6, '广泛的人脉关系和社交能力', 3),
(6, '独特的创造力和审美能力', 4);

-- 题目7：校园环境
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(7, '设备先进的实验室和创新空间', 1),
(7, '安静的图书馆和学术氛围', 2),
(7, '活跃的社团和丰富的校园生活', 3),
(7, '开放包容的文化和国际化环境', 4);

-- 插入选项权重配置
-- 权重说明：每个选项对不同气质类型的权重分配
-- 气质类型ID对应：1-理性创造型, 2-艺术梦想型, 3-实用领导型, 4-学者思辨型, 5-社交和谐型, 6-创新先锋型, 7-文化传承型, 8-国际探索型

-- 题目1选项权重：学习方式
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 通过实验和动手实践来学习
(1, 1, 3), (1, 6, 2), (1, 3, 1),
-- 通过阅读和深度思考来学习  
(2, 4, 3), (2, 7, 2), (2, 1, 1),
-- 通过讨论和交流来学习
(3, 5, 3), (3, 8, 2), (3, 3, 1),
-- 通过创作和表达来学习
(4, 2, 3), (4, 7, 2), (4, 6, 1);

-- 题目2选项权重：团队角色
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 技术专家，负责解决具体问题
(5, 1, 3), (5, 6, 2), (5, 4, 1),
-- 思想家，提供创新理念和方向
(6, 4, 3), (6, 6, 2), (6, 2, 1),
-- 协调者，促进团队沟通合作
(7, 5, 3), (7, 3, 2), (7, 8, 1),
-- 领导者，制定计划并推动执行
(8, 3, 3), (8, 8, 2), (8, 1, 1);

-- 题目3选项权重：职业期望
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 成为某个领域的技术专家
(9, 1, 3), (9, 6, 2), (9, 4, 1),
-- 从事研究工作，探索未知领域
(10, 4, 3), (10, 6, 2), (10, 1, 1),
-- 在国际舞台上发挥影响力
(11, 8, 3), (11, 3, 2), (11, 5, 1),
-- 创造有艺术价值的作品
(12, 2, 3), (12, 7, 2), (12, 6, 1);

-- 题目4选项权重：课外活动
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 科技创新竞赛和实验室研究
(13, 1, 3), (13, 6, 2), (13, 4, 1),
-- 学术讲座和读书会
(14, 4, 3), (14, 7, 2), (14, 1, 1),
-- 社团活动和志愿服务
(15, 5, 3), (15, 3, 2), (15, 8, 1),
-- 艺术创作和文化活动
(16, 2, 3), (16, 7, 2), (16, 5, 1);

-- 题目5选项权重：面对挑战
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 分析问题，寻找技术解决方案
(17, 1, 3), (17, 6, 2), (17, 3, 1),
-- 深入研究，从理论角度理解
(18, 4, 3), (18, 1, 2), (18, 7, 1),
-- 寻求帮助，与他人协作解决
(19, 5, 3), (19, 8, 2), (19, 3, 1),
-- 跳出框架，寻找创新突破
(20, 6, 3), (20, 2, 2), (20, 8, 1);

-- 题目6选项权重：大学收获
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 扎实的专业技能和实践能力
(21, 1, 3), (21, 3, 2), (21, 6, 1),
-- 深厚的学术素养和研究能力
(22, 4, 3), (22, 7, 2), (22, 1, 1),
-- 广泛的人脉关系和社交能力
(23, 5, 3), (23, 8, 2), (23, 3, 1),
-- 独特的创造力和审美能力
(24, 2, 3), (24, 6, 2), (24, 7, 1);

-- 题目7选项权重：校园环境
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
-- 设备先进的实验室和创新空间
(25, 1, 3), (25, 6, 2), (25, 3, 1),
-- 安静的图书馆和学术氛围
(26, 4, 3), (26, 7, 2), (26, 1, 1),
-- 活跃的社团和丰富的校园生活
(27, 5, 3), (27, 2, 2), (27, 8, 1),
-- 开放包容的文化和国际化环境
(28, 8, 3), (28, 6, 2), (28, 5, 1);

-- 显示创建结果
SELECT 'Database initialization completed!' as status;
SELECT COUNT(*) as mood_types_count FROM mood_types;
SELECT COUNT(*) as universities_count FROM universities;
SELECT COUNT(*) as assessment_questions_count FROM assessment_questions;
SELECT COUNT(*) as assessment_options_count FROM assessment_options;
SELECT COUNT(*) as assessment_weights_count FROM assessment_option_weights;

-- ================================
-- 附加：基础大学信息与性格标签模型
-- ================================

-- 大学基础信息表（名称、地区、性质、层次、重点专业）
CREATE TABLE IF NOT EXISTS universities_basic (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL COMMENT '大学名称',
  region VARCHAR(100) NOT NULL COMMENT '地区，如北京、上海、浙江-杭州',
  nature VARCHAR(50) NOT NULL COMMENT '性质，如公立/私立',
  level VARCHAR(100) NOT NULL COMMENT '层次，如双一流/985/211/本科/研究型',
  key_majors JSON NULL COMMENT '重点专业（JSON数组）',
  PRIMARY KEY (id),
  UNIQUE KEY uniq_university_name_basic (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 点赞：大学-标签点赞记录（持久化热度统计）
CREATE TABLE IF NOT EXISTS university_tag_likes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  university_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  client_id VARCHAR(64) NOT NULL,
  ip_address VARCHAR(64),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_uni_tag_client (university_id, tag_id, client_id),
  INDEX idx_uni_tag (university_id, tag_id),
  CONSTRAINT fk_utl_university FOREIGN KEY (university_id) REFERENCES universities_basic(id) ON DELETE CASCADE,
  CONSTRAINT fk_utl_tag FOREIGN KEY (tag_id) REFERENCES personality_tags(id) ON DELETE CASCADE,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- 性格标签表（标签名、描述）
CREATE TABLE IF NOT EXISTS personality_tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tag_name VARCHAR(100) NOT NULL COMMENT '标签名',
  description VARCHAR(500) NULL COMMENT '标签描述',
  PRIMARY KEY (id),
  UNIQUE KEY uniq_tag_name (tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 多对多关系：大学与性格标签
CREATE TABLE IF NOT EXISTS university_personality_tags (
  university_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (university_id, tag_id),
  CONSTRAINT fk_upt_university FOREIGN KEY (university_id) REFERENCES universities_basic(id) ON DELETE CASCADE,
  CONSTRAINT fk_upt_tag FOREIGN KEY (tag_id) REFERENCES personality_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入性格标签示例数据
INSERT INTO personality_tags (tag_name, description) VALUES
('研究导向', '偏好科研氛围与学术深度的学习者'),
('创新实践', '注重动手能力与项目驱动的学习者'),
('社交领导', '具备组织协调与领导力倾向的学习者'),
('艺术探索', '偏好艺术设计与人文表达的学习者'),
('务实应用', '重视应用场景与行业落地的学习者'),
('理性分析', '擅长逻辑与数据分析的学习者');

-- 插入大学基础信息示例数据
INSERT INTO universities_basic (name, region, nature, level, key_majors) VALUES
('清华大学', '北京', '公立', '双一流/985/211', JSON_ARRAY('计算机科学','电子信息','土木工程','机械工程')),
('北京大学', '北京', '公立', '双一流/985/211', JSON_ARRAY('数学','物理学','医学','法学')),
('浙江大学', '浙江-杭州', '公立', '双一流/985/211', JSON_ARRAY('信息工程','能源与动力工程','农业工程','控制科学与工程')),
('上海交通大学', '上海', '公立', '双一流/985/211', JSON_ARRAY('船舶与海洋工程','生物医学工程','材料科学与工程','电气工程及其自动化')),
('中国科学技术大学', '安徽-合肥', '公立', '双一流/985/211', JSON_ARRAY('物理学','天文学','计算机科学','化学')),
('南京大学', '江苏-南京', '公立', '双一流/985/211', JSON_ARRAY('地质学','大气科学','电子科学与工程','哲学'));

-- 为示例大学绑定性格标签（采用名称与标签名映射插入）
INSERT INTO university_personality_tags (university_id, tag_id)
SELECT ub.id, pt.id FROM (
  SELECT '清华大学' AS uname, '创新实践' AS tname UNION ALL
  SELECT '清华大学', '理性分析' UNION ALL
  SELECT '北京大学', '研究导向' UNION ALL
  SELECT '北京大学', '理性分析' UNION ALL
  SELECT '浙江大学', '创新实践' UNION ALL
  SELECT '浙江大学', '研究导向' UNION ALL
  SELECT '上海交通大学', '创新实践' UNION ALL
  SELECT '上海交通大学', '务实应用' UNION ALL
  SELECT '中国科学技术大学', '研究导向' UNION ALL
  SELECT '中国科学技术大学', '理性分析' UNION ALL
  SELECT '南京大学', '研究导向' UNION ALL
  SELECT '南京大学', '理性分析'
) m
JOIN universities_basic ub ON ub.name = m.uname
JOIN personality_tags pt ON pt.tag_name = m.tname;

-- 统计新表数据量
SELECT COUNT(*) as universities_basic_count FROM universities_basic;
SELECT COUNT(*) as personality_tags_count FROM personality_tags;
SELECT COUNT(*) as university_personality_tags_count FROM university_personality_tags;