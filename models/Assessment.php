<?php
/**
 * Assessment 模型类
 * 处理测评相关的业务逻辑和匹配算法
 */

class Assessment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 获取所有活跃的测评题目
     */
    public function getActiveQuestions() {
        $sql = "
            SELECT 
                q.id as question_id,
                q.question_text,
                q.question_order,
                o.id as option_id,
                o.option_text,
                o.option_order
            FROM assessment_questions q
            LEFT JOIN assessment_options o ON q.id = o.question_id
            WHERE q.is_active = TRUE
            ORDER BY q.question_order ASC, o.option_order ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 验证答案数组的有效性
     */
    public function validateAnswers($answers) {
        if (!is_array($answers) || empty($answers)) {
            return ['valid' => false, 'message' => 'Answers must be a non-empty array'];
        }
        
        // 获取所有有效的选项ID
        $sql = "
            SELECT o.id 
            FROM assessment_options o
            JOIN assessment_questions q ON o.question_id = q.id
            WHERE q.is_active = TRUE
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $valid_option_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        
        // 验证每个答案
        foreach ($answers as $option_id) {
            if (!in_array($option_id, $valid_option_ids)) {
                return ['valid' => false, 'message' => "Invalid option ID: $option_id"];
            }
        }
        
        return ['valid' => true, 'message' => 'All answers are valid'];
    }
    
    /**
     * 计算用户气质类型
     * 根据答案选项的权重累加计算
     */
    public function calculateUserMoodType($answers) {
        // 验证答案
        $validation = $this->validateAnswers($answers);
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }
        
        // 获取所有选项的权重
        $placeholders = str_repeat('?,', count($answers) - 1) . '?';
        $sql = "
            SELECT 
                w.mood_type_id,
                SUM(w.weight) as total_weight
            FROM assessment_option_weights w
            WHERE w.option_id IN ($placeholders)
            GROUP BY w.mood_type_id
            ORDER BY total_weight DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($answers);
        $mood_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($mood_scores)) {
            throw new Exception('No mood type scores calculated');
        }
        
        // 获取得分最高的气质类型
        $top_mood_id = $mood_scores[0]['mood_type_id'];
        
        // 获取气质类型详细信息
        $mood_sql = "
            SELECT id, slug, name, short_desc, color
            FROM mood_types
            WHERE id = ?
        ";
        
        $mood_stmt = $this->pdo->prepare($mood_sql);
        $mood_stmt->execute([$top_mood_id]);
        $mood_type = $mood_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mood_type) {
            throw new Exception('Mood type not found');
        }
        
        return [
            'mood_type' => $mood_type,
            'scores' => $mood_scores
        ];
    }
    
    /**
     * 获取匹配的大学列表
     * 优先返回相同气质类型的大学，不足时补充其他大学
     */
    public function getMatchedUniversities($mood_type_id, $limit = 6) {
        // 首先获取相同气质类型的大学
        $primary_sql = "
            SELECT 
                u.id,
                u.name,
                u.province,
                u.city,
                u.type,
                u.mood_type_id,
                u.keywords,
                u.one_line,
                u.logo_url,
                mt.slug as mood_slug,
                mt.name as mood_name,
                mt.short_desc as mood_desc,
                mt.color as mood_color,
                'primary' as match_type
            FROM universities u
            JOIN mood_types mt ON u.mood_type_id = mt.id
            WHERE u.mood_type_id = ?
            ORDER BY u.name ASC
        ";
        
        $primary_stmt = $this->pdo->prepare($primary_sql);
        // 确保以整数类型绑定，提升兼容性
        $primary_stmt->bindValue(1, (int)$mood_type_id, PDO::PARAM_INT);
        $primary_stmt->execute();
        $primary_universities = $primary_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $matched_universities = $primary_universities;
        
        // 如果主要匹配的大学数量不足，补充其他大学
        if (count($matched_universities) < $limit) {
            $remaining_limit = $limit - count($matched_universities);
            
            // 为了兼容 MariaDB/MySQL 原生预处理在 LIMIT 上不支持参数绑定的限制，
            // 这里将 LIMIT 使用安全的整数值直接拼接到 SQL 中。
            $secondary_sql = "
                SELECT 
                    u.id,
                    u.name,
                    u.province,
                    u.city,
                    u.type,
                    u.mood_type_id,
                    u.keywords,
                    u.one_line,
                    u.logo_url,
                    mt.slug as mood_slug,
                    mt.name as mood_name,
                    mt.short_desc as mood_desc,
                    mt.color as mood_color,
                    'secondary' as match_type
                FROM universities u
                JOIN mood_types mt ON u.mood_type_id = mt.id
                WHERE u.mood_type_id != ?
                ORDER BY u.name ASC
                LIMIT " . (int)$remaining_limit . "
            ";
            
            $secondary_stmt = $this->pdo->prepare($secondary_sql);
            // 仅绑定 mood_type_id，LIMIT 使用已拼接的安全整数
            $secondary_stmt->bindValue(1, (int)$mood_type_id, PDO::PARAM_INT);
            $secondary_stmt->execute();
            $secondary_universities = $secondary_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $matched_universities = array_merge($matched_universities, $secondary_universities);
        }
        
        // 限制返回数量
        return array_slice($matched_universities, 0, $limit);
    }
    
    /**
     * 处理完整的测评提交
     * 计算气质类型并返回匹配的大学
     */
    public function processAssessment($answers) {
        try {
            // 计算用户气质类型
            $mood_result = $this->calculateUserMoodType($answers);
            $user_mood = $mood_result['mood_type'];
            
            // 获取匹配的大学
            $matched_universities = $this->getMatchedUniversities($user_mood['id']);
            
            // 添加统计信息
            $stats = [
                'total_answers' => count($answers),
                'mood_scores' => $mood_result['scores'],
                'matched_count' => count($matched_universities),
                'primary_matches' => count(array_filter($matched_universities, function($u) {
                    return $u['match_type'] === 'primary';
                }))
            ];
            
            return [
                'success' => true,
                'user_mood' => $user_mood,
                'matched_universities' => $matched_universities,
                'statistics' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取测评统计信息
     */
    public function getAssessmentStats() {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM assessment_questions WHERE is_active = TRUE) as total_questions,
                (SELECT COUNT(*) FROM assessment_options o 
                 JOIN assessment_questions q ON o.question_id = q.id 
                 WHERE q.is_active = TRUE) as total_options,
                (SELECT COUNT(*) FROM mood_types) as total_mood_types,
                (SELECT COUNT(*) FROM universities) as total_universities
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 计算各气质类型的得分（用于测试）
     */
    public function calculateMoodScores($answers) {
        $moodScores = [];
        
        // 初始化所有气质类型得分为0
        for ($i = 1; $i <= 4; $i++) {
            $moodScores[$i] = 0;
        }
        
        // 计算每个答案的权重
        foreach ($answers as $optionId) {
            $stmt = $this->pdo->prepare("
                SELECT mood_type_id, weight 
                FROM assessment_option_weights 
                WHERE option_id = ?
            ");
            $stmt->execute([$optionId]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $moodScores[$row['mood_type_id']] += $row['weight'];
            }
        }
        
        return $moodScores;
    }

    /**
     * 获取推荐大学的公开方法（用于测试）
     */
    public function getRecommendedUniversities($userMoodId) {
        return $this->getMatchedUniversities($userMoodId);
    }
}
?>