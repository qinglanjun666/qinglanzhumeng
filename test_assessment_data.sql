-- Assessment questions and options test data (ASCII-only, matches current schema)

USE huilanweb;

-- 确保客户端与服务器使用UTF8MB4字符集，防止中文乱码
SET NAMES utf8mb4;

-- Reset tables to ensure predictable IDs
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE assessment_option_weights;
TRUNCATE TABLE assessment_options;
TRUNCATE TABLE assessment_questions;
SET FOREIGN_KEY_CHECKS=1;

-- Insert questions (7)
INSERT INTO assessment_questions (question_text, question_order, is_active) VALUES 
('你更喜欢的学习方式是？', 1, TRUE),
('在团队合作中，你通常扮演什么角色？', 2, TRUE),
('你对未来职业的期望是？', 3, TRUE),
('你更倾向于选择哪种课外活动？', 4, TRUE),
('面对挑战时，你的第一反应是？', 5, TRUE),
('你认为大学最重要的收获应该是？', 6, TRUE),
('你更喜欢什么样的校园环境？', 7, TRUE);

-- Insert options (4 per question)
-- Q1: Learning style
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(1, '通过实验和动手实践来学习', 1),
(1, '通过阅读和深度思考来学习', 2),
(1, '通过讨论和交流来学习', 3),
(1, '通过创作和表达来学习', 4);

-- Q2: Team role
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(2, '技术专家，负责解决具体问题', 1),
(2, '思想家，提供创新理念和方向', 2),
(2, '协调者，促进团队沟通合作', 3),
(2, '领导者，制定计划并推动执行', 4);

-- Q3: Career expectation
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(3, '成为某个领域的技术专家', 1),
(3, '从事研究工作，探索未知领域', 2),
(3, '在国际舞台上发挥影响力', 3),
(3, '创造有艺术价值的作品', 4);

-- Q4: Extracurricular activity
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(4, '科技创新竞赛和实验室研究', 1),
(4, '学术讲座和读书会', 2),
(4, '社团活动和志愿服务', 3),
(4, '艺术创作和文化活动', 4);

-- Q5: Facing challenges
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(5, '分析问题，寻找技术解决方案', 1),
(5, '深入研究，从理论角度理解', 2),
(5, '寻求帮助，与他人协作解决', 3),
(5, '跳出框架，寻找创新突破', 4);

-- Q6: University gains
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(6, '扎实的专业技能和实践能力', 1),
(6, '深厚的学术素养和研究能力', 2),
(6, '广泛的人脉关系和社交能力', 3),
(6, '独特的创造力和审美能力', 4);

-- Q7: Campus environment
INSERT INTO assessment_options (question_id, option_text, option_order) VALUES
(7, '设备先进的实验室和创新空间', 1),
(7, '安静的图书馆和学术氛围', 2),
(7, '活跃的社团和丰富的校园生活', 3),
(7, '开放包容的文化和国际化环境', 4);

-- Insert option weights (option_id -> mood_type_id with weight)
-- Assumes option IDs 1..28 by insertion order after TRUNCATE
-- Mood type IDs used: 1-rational_creator, 2-artistic_dreamer, 3-practical_leader, 4-scholarly_thinker

-- Q1
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(1, 1, 3), (1, 3, 1),
(2, 4, 3), (2, 1, 1),
(3, 3, 2), (3, 4, 1),
(4, 2, 3), (4, 4, 1);

-- Q2
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(5, 3, 3), (5, 1, 2),
(6, 4, 3), (6, 2, 1),
(7, 3, 2), (7, 4, 1),
(8, 3, 3), (8, 1, 1);

-- Q3
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(9, 1, 3), (9, 4, 1),
(10, 4, 3), (10, 1, 1),
(11, 3, 2), (11, 1, 1),
(12, 2, 3), (12, 4, 1);

-- Q4
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(13, 1, 3), (13, 4, 1),
(14, 4, 3), (14, 1, 1),
(15, 3, 2), (15, 4, 1),
(16, 2, 3), (16, 4, 1);

-- Q5
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(17, 1, 3), (17, 3, 1),
(18, 4, 3), (18, 1, 1),
(19, 3, 2), (19, 4, 1),
(20, 2, 3), (20, 1, 1);

-- Q6
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(21, 1, 3), (21, 3, 2),
(22, 4, 3), (22, 1, 1),
(23, 3, 2), (23, 4, 1),
(24, 2, 3), (24, 1, 1);

-- Q7
INSERT INTO assessment_option_weights (option_id, mood_type_id, weight) VALUES
(25, 1, 3), (25, 3, 1),
(26, 4, 3), (26, 1, 1),
(27, 3, 2), (27, 4, 1),
(28, 2, 3), (28, 1, 1);

-- Done
SELECT 'Assessment test data loaded (ASCII only).' AS status;