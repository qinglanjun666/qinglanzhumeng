<?php
/**
 * 大学模型类
 */

class University {
    private $conn;
    private $table_name = "universities";
    private $supportsExternalIdCache = null;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 获取大学列表（支持分页和筛选）
     */
    public function getUniversities($page = 1, $per_page = 20, $mood_type = null, $q = null) {
        // 构建基础查询
        $query = "SELECT 
                    u.id,
                    u.name,
                    u.province,
                    u.city,
                    u.type,
                    m.slug as mood_type_slug,
                    u.one_line,
                    u.logo_url,
                    (SELECT COUNT(*) FROM university_likes ul WHERE ul.university_id = u.id) as like_count,
                    (SELECT COUNT(*) FROM university_votes uv WHERE uv.university_id = u.id) as poll_counts
                  FROM " . $this->table_name . " u
                  JOIN mood_types m ON u.mood_type_id = m.id";

        // 构建WHERE条件
        $conditions = [];
        $params = [];

        if ($mood_type) {
            $conditions[] = "m.slug = :mood_type";
            $params[':mood_type'] = $mood_type;
        }

        if ($q) {
            $conditions[] = "(u.name LIKE :q OR u.keywords LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // 获取总数
        $count_query = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
        $count_stmt = $this->conn->prepare($count_query);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch()['total'];

        // 添加排序和分页
        $query .= " ORDER BY u.id ASC";
        $offset = ($page - 1) * $per_page;
        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $universities = $stmt->fetchAll();

        // 转换数据类型
        foreach ($universities as &$university) {
            $university['id'] = (int)$university['id'];
            $university['like_count'] = (int)$university['like_count'];
            $university['poll_counts'] = (int)$university['poll_counts'];
        }

        return [
            'data' => $universities,
            'total' => (int)$total,
            'page' => (int)$page,
            'per_page' => (int)$per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    /**
     * 随机获取指定数量的大学（按天固定种子，确保每日一致）
     */
    public function getRandomUniversities($limit = 6) {
        $limit_i = max(1, min(100, (int)$limit));
        // 以当天日期作为随机种子，保证当天固定，次日变化
        $seed = intval(date('Ymd'));

        $sql = "SELECT 
                    u.id,
                    u.name,
                    u.province,
                    u.city,
                    u.type,
                    m.slug as mood_type_slug,
                    u.one_line,
                    u.logo_url,
                    (SELECT COUNT(*) FROM university_likes ul WHERE ul.university_id = u.id) as like_count,
                    (SELECT COUNT(*) FROM university_votes uv WHERE uv.university_id = u.id) as poll_counts
                FROM " . $this->table_name . " u
                JOIN mood_types m ON u.mood_type_id = m.id
                ORDER BY RAND($seed)
                LIMIT $limit_i";

        $stmt = $this->conn->query($sql);
        $universities = $stmt->fetchAll();

        foreach ($universities as &$u) {
            $u['id'] = (int)$u['id'];
            $u['like_count'] = (int)$u['like_count'];
            $u['poll_counts'] = (int)$u['poll_counts'];
        }

        return [
            'data' => $universities,
            'total' => count($universities),
            'page' => 1,
            'per_page' => $limit_i,
            'total_pages' => 1
        ];
    }

    /**
     * 根据ID获取单个大学详情
     */
    public function getUniversityById($id) {
        $query = "SELECT 
                    u.id,
                    u.name,
                    u.province,
                    u.city,
                    u.type,
                    m.slug as mood_type_slug,
                    m.name as mood_type_name,
                    u.keywords,
                    u.one_line,
                    u.logo_url,
                    (SELECT COUNT(*) FROM university_likes ul WHERE ul.university_id = u.id) as like_count,
                    (SELECT COUNT(*) FROM university_votes uv WHERE uv.university_id = u.id) as poll_counts
                  FROM " . $this->table_name . " u
                  JOIN mood_types m ON u.mood_type_id = m.id
                  WHERE u.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $university = $stmt->fetch();
        
        if ($university) {
            $university['id'] = (int)$university['id'];
            $university['like_count'] = (int)$university['like_count'];
            $university['poll_counts'] = (int)$university['poll_counts'];
        }

        return $university;
    }

    /**
     * 获取大学详情（包含完整的投票分布和气质类型信息）
     */
    public function getUniversityDetail($id) {
        // 获取基础大学信息
        $query = "SELECT 
                    u.id,
                    u.name,
                    u.province,
                    u.city,
                    u.type,
                    u.one_line,
                    u.keywords,
                    u.logo_url,
                    m.id as mood_type_id,
                    m.slug as mood_type_slug,
                    m.name as mood_type_name,
                    m.short_desc as mood_type_short_desc,
                    m.color as mood_type_color
                  FROM " . $this->table_name . " u
                  JOIN mood_types m ON u.mood_type_id = m.id
                  WHERE u.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $university = $stmt->fetch();
        
        if (!$university) {
            return null;
        }

        // 转换数据类型
        $university['id'] = (int)$university['id'];
        $university['mood_type_id'] = (int)$university['mood_type_id'];

        // 构建mood_type对象
        $university['mood_type'] = [
            'id' => $university['mood_type_id'],
            'slug' => $university['mood_type_slug'],
            'name' => $university['mood_type_name'],
            'short_desc' => $university['mood_type_short_desc'],
            'color' => $university['mood_type_color']
        ];

        // 移除重复字段
        unset($university['mood_type_id'], $university['mood_type_slug'], 
              $university['mood_type_name'], $university['mood_type_short_desc'], 
              $university['mood_type_color']);

        // 获取点赞数
        $like_query = "SELECT COUNT(*) as like_count FROM university_likes WHERE university_id = :id";
        $like_stmt = $this->conn->prepare($like_query);
        $like_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $like_stmt->execute();
        $like_result = $like_stmt->fetch();
        $university['like_count'] = (int)$like_result['like_count'];

        // 获取投票分布
        $university['vote_distribution'] = $this->getVoteDistribution($id);

        return $university;
    }

    /**
     * 获取大学的投票分布统计
     */
    private function getVoteDistribution($university_id) {
        // 首先获取所有气质类型
        $all_moods_query = "SELECT slug FROM mood_types ORDER BY id";
        $all_moods_stmt = $this->conn->prepare($all_moods_query);
        $all_moods_stmt->execute();
        $all_moods = $all_moods_stmt->fetchAll(PDO::FETCH_COLUMN);

        // 初始化所有气质类型的投票数为0
        $vote_distribution = [];
        foreach ($all_moods as $mood_slug) {
            $vote_distribution[$mood_slug] = 0;
        }

        // 获取实际的投票统计
        $vote_query = "SELECT m.slug, COUNT(uv.id) as vote_count
                       FROM mood_types m
                       LEFT JOIN university_votes uv ON m.id = uv.mood_type_id AND uv.university_id = :university_id
                       GROUP BY m.id, m.slug
                       ORDER BY m.id";

        $vote_stmt = $this->conn->prepare($vote_query);
        $vote_stmt->bindParam(':university_id', $university_id, PDO::PARAM_INT);
        $vote_stmt->execute();
        $vote_results = $vote_stmt->fetchAll();

        // 更新实际的投票数
        foreach ($vote_results as $result) {
            $vote_distribution[$result['slug']] = (int)$result['vote_count'];
        }

        return $vote_distribution;
    }
    
    /**
     * 检查用户是否已经点赞过该大学
     */
    public function hasUserLiked($university_id, $client_id) {
        $query = "SELECT id FROM university_likes WHERE university_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $university_id);
        $stmt->bindParam(2, $client_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * 添加点赞记录
     */
    public function addLike($university_id, $client_id, $ip_address = null) {
        // 检查是否已经点赞过
        if ($this->hasUserLiked($university_id, $client_id)) {
            return false; // 已经点赞过
        }
        
        // 兼容旧库：检测是否存在 ip_address 列
        $supportsIp = $this->likesSupportsIpAddress();
        if ($supportsIp) {
            $query = "INSERT INTO university_likes (university_id, client_id, ip_address, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $university_id);
            $stmt->bindParam(2, $client_id);
            $stmt->bindParam(3, $ip_address);
        } else {
            $query = "INSERT INTO university_likes (university_id, client_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $university_id);
            $stmt->bindParam(2, $client_id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 获取大学的点赞总数
     */
    public function getLikeCount($university_id) {
        $query = "SELECT COUNT(*) as like_count FROM university_likes WHERE university_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $university_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['like_count'];
    }
    
    /**
     * 生成客户端ID
     */
    public function generateClientId() {
        return 'hj_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * 检查用户是否已经对该大学投票过
     */
    public function hasUserVoted($university_id, $client_id) {
        $query = "SELECT id, mood_type_id FROM university_votes WHERE university_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $university_id);
        $stmt->bindParam(2, $client_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : false;
    }
    
    /**
     * 根据mood_slug获取mood_type_id
     */
    public function getMoodTypeIdBySlug($mood_slug) {
        $query = "SELECT id FROM mood_types WHERE slug = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $mood_slug);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    /**
     * 验证mood_slug是否有效
     */
    public function isValidMoodSlug($mood_slug) {
        return $this->getMoodTypeIdBySlug($mood_slug) !== null;
    }

    /**
     * 检查 university_likes 表是否包含 ip_address 列（带缓存）
     */
    private $likesIpAddressSupportCache = null;
    public function likesSupportsIpAddress() {
        if ($this->likesIpAddressSupportCache !== null) {
            return $this->likesIpAddressSupportCache;
        }
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM university_likes LIKE 'ip_address'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->likesIpAddressSupportCache = $col ? true : false;
        } catch (Exception $e) {
            $this->likesIpAddressSupportCache = false;
        }
        return $this->likesIpAddressSupportCache;
    }

    /**
     * 检查universities表是否支持external_id列（缓存）
     */
    public function supportsExternalId() {
        if ($this->supportsExternalIdCache !== null) {
            return $this->supportsExternalIdCache;
        }
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'external_id'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->supportsExternalIdCache = $col ? true : false;
        } catch (Exception $e) {
            $this->supportsExternalIdCache = false;
        }
        return $this->supportsExternalIdCache;
    }

    /**
     * 通过名称查找大学（若存在返回记录）
     */
    public function findUniversityByName($name) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE name = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 插入大学记录
     */
    public function insertUniversity($data) {
        $mood_type_id = $this->getMoodTypeIdBySlug($data['mood_slug']);
        if (!$mood_type_id) {
            return false;
        }
        $hasExternal = $this->supportsExternalId();
        if ($hasExternal) {
            $sql = "INSERT INTO " . $this->table_name . " (name, province, city, type, mood_type_id, keywords, one_line, external_id, logo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (name, province, city, type, mood_type_id, keywords, one_line, logo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        }
        $stmt = $this->conn->prepare($sql);
        $logo_url = isset($data['logo_url']) ? $data['logo_url'] : null;
        if ($hasExternal) {
            $stmt->bindParam(1, $data['name']);
            $stmt->bindParam(2, $data['province']);
            $stmt->bindParam(3, $data['city']);
            $stmt->bindParam(4, $data['type']);
            $stmt->bindParam(5, $mood_type_id, PDO::PARAM_INT);
            $stmt->bindParam(6, $data['keywords']);
            $stmt->bindParam(7, $data['one_line']);
            $external_id = isset($data['external_id']) ? $data['external_id'] : null;
            $stmt->bindParam(8, $external_id);
            $stmt->bindParam(9, $logo_url);
        } else {
            $stmt->bindParam(1, $data['name']);
            $stmt->bindParam(2, $data['province']);
            $stmt->bindParam(3, $data['city']);
            $stmt->bindParam(4, $data['type']);
            $stmt->bindParam(5, $mood_type_id, PDO::PARAM_INT);
            $stmt->bindParam(6, $data['keywords']);
            $stmt->bindParam(7, $data['one_line']);
            $stmt->bindParam(8, $logo_url);
        }
        if ($stmt->execute()) {
            return [ 'id' => (int)$this->conn->lastInsertId(), 'action' => 'inserted' ];
        }
        return false;
    }

    /**
     * 更新大学记录（通过ID）
     */
    public function updateUniversityById($id, $data) {
        $mood_type_id = $this->getMoodTypeIdBySlug($data['mood_slug']);
        if (!$mood_type_id) {
            return false;
        }
        $hasExternal = $this->supportsExternalId();
        if ($hasExternal) {
            $sql = "UPDATE " . $this->table_name . " SET province = ?, city = ?, type = ?, mood_type_id = ?, keywords = ?, one_line = ?, external_id = ?, logo_url = ? WHERE id = ?";
        } else {
            $sql = "UPDATE " . $this->table_name . " SET province = ?, city = ?, type = ?, mood_type_id = ?, keywords = ?, one_line = ?, logo_url = ? WHERE id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $logo_url = isset($data['logo_url']) ? $data['logo_url'] : null;
        if ($hasExternal) {
            $stmt->bindParam(1, $data['province']);
            $stmt->bindParam(2, $data['city']);
            $stmt->bindParam(3, $data['type']);
            $stmt->bindParam(4, $mood_type_id, PDO::PARAM_INT);
            $stmt->bindParam(5, $data['keywords']);
            $stmt->bindParam(6, $data['one_line']);
            $external_id = isset($data['external_id']) ? $data['external_id'] : null;
            $stmt->bindParam(7, $external_id);
            $stmt->bindParam(8, $logo_url);
            $stmt->bindParam(9, $id, PDO::PARAM_INT);
        } else {
            $stmt->bindParam(1, $data['province']);
            $stmt->bindParam(2, $data['city']);
            $stmt->bindParam(3, $data['type']);
            $stmt->bindParam(4, $mood_type_id, PDO::PARAM_INT);
            $stmt->bindParam(5, $data['keywords']);
            $stmt->bindParam(6, $data['one_line']);
            $stmt->bindParam(7, $logo_url);
            $stmt->bindParam(8, $id, PDO::PARAM_INT);
        }
        if ($stmt->execute()) {
            return [ 'id' => (int)$id, 'action' => 'updated' ];
        }
        return false;
    }

    /**
     * 基于名称的插入或更新
     */
    public function upsertUniversityByName($data) {
        $existing = $this->findUniversityByName($data['name']);
        if ($existing) {
            return $this->updateUniversityById((int)$existing['id'], $data);
        }
        return $this->insertUniversity($data);
    }

    /**
     * 通过external_id查找大学（若支持该列）
     */
    public function findUniversityByExternalId($external_id) {
        if (!$this->supportsExternalId()) {
            return null;
        }
        $sql = "SELECT * FROM " . $this->table_name . " WHERE external_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $external_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 基于external_id的插入或更新（需要表支持external_id）
     */
    public function upsertUniversityByExternalId($data) {
        $external_id = isset($data['external_id']) ? $data['external_id'] : null;
        if (!$external_id || !$this->supportsExternalId()) {
            // 不支持时回退到名称匹配
            return $this->upsertUniversityByName($data);
        }
        $existing = $this->findUniversityByExternalId($external_id);
        if ($existing) {
            return $this->updateUniversityById((int)$existing['id'], $data);
        }
        return $this->insertUniversity($data);
    }
    
    /**
     * 添加或更新用户投票
     */
    public function addOrUpdateVote($university_id, $client_id, $mood_slug, $ip_address = null) {
        // 验证mood_slug
        $mood_type_id = $this->getMoodTypeIdBySlug($mood_slug);
        if (!$mood_type_id) {
            return ['success' => false, 'message' => 'Invalid mood_slug'];
        }
        
        // 检查用户是否已经投票过
        $existing_vote = $this->hasUserVoted($university_id, $client_id);
        
        try {
            $this->conn->beginTransaction();
            
            if ($existing_vote) {
                // 如果投票的是同一个mood_type，不需要更新
                if ((int)$existing_vote['mood_type_id'] === $mood_type_id) {
                    $this->conn->rollback();
                    return [
                        'success' => true, 
                        'message' => 'Vote already exists for this mood type',
                        'updated' => false
                    ];
                }
                
                // 更新现有投票（兼容是否存在 updated_at 列）
                $supportsUpdatedAt = $this->votesSupportsUpdatedAt();
                if ($supportsUpdatedAt) {
                    $query = "UPDATE university_votes SET mood_type_id = ?, updated_at = NOW() WHERE university_id = ? AND client_id = ?";
                } else {
                    $query = "UPDATE university_votes SET mood_type_id = ? WHERE university_id = ? AND client_id = ?";
                }
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $mood_type_id);
                $stmt->bindParam(2, $university_id);
                $stmt->bindParam(3, $client_id);
                $result = $stmt->execute();
                
                $this->conn->commit();
                return [
                    'success' => $result, 
                    'message' => $result ? 'Vote updated successfully' : 'Failed to update vote',
                    'updated' => true
                ];
            } else {
                // 添加新投票（兼容是否存在 ip_address / updated_at 列）
                $supportsIp = $this->votesSupportsIpAddress();
                $supportsUpdatedAt = $this->votesSupportsUpdatedAt();
                if ($supportsIp && $supportsUpdatedAt) {
                    $query = "INSERT INTO university_votes (university_id, client_id, mood_type_id, ip_address, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $university_id);
                    $stmt->bindParam(2, $client_id);
                    $stmt->bindParam(3, $mood_type_id);
                    $stmt->bindParam(4, $ip_address);
                } elseif ($supportsIp && !$supportsUpdatedAt) {
                    $query = "INSERT INTO university_votes (university_id, client_id, mood_type_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $university_id);
                    $stmt->bindParam(2, $client_id);
                    $stmt->bindParam(3, $mood_type_id);
                    $stmt->bindParam(4, $ip_address);
                } elseif (!$supportsIp && $supportsUpdatedAt) {
                    $query = "INSERT INTO university_votes (university_id, client_id, mood_type_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $university_id);
                    $stmt->bindParam(2, $client_id);
                    $stmt->bindParam(3, $mood_type_id);
                } else {
                    $query = "INSERT INTO university_votes (university_id, client_id, mood_type_id, created_at) VALUES (?, ?, ?, NOW())";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $university_id);
                    $stmt->bindParam(2, $client_id);
                    $stmt->bindParam(3, $mood_type_id);
                }
                $result = $stmt->execute();
                
                $this->conn->commit();
                return [
                    'success' => $result, 
                    'message' => $result ? 'Vote added successfully' : 'Failed to add vote',
                    'updated' => false
                ];
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * 获取用户对特定大学的投票情况
     */
    public function getUserVote($university_id, $client_id) {
        $query = "SELECT uv.*, m.slug as mood_slug, m.name as mood_name 
                  FROM university_votes uv 
                  JOIN mood_types m ON uv.mood_type_id = m.id 
                  WHERE uv.university_id = ? AND uv.client_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $university_id);
        $stmt->bindParam(2, $client_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- 兼容：投票表列检测缓存与查询 ---
    private $votesIpAddressSupportCache = null;
    private $votesUpdatedAtSupportCache = null;
    public function votesSupportsIpAddress() {
        if ($this->votesIpAddressSupportCache !== null) return $this->votesIpAddressSupportCache;
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM university_votes LIKE 'ip_address'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->votesIpAddressSupportCache = $col ? true : false;
        } catch (Exception $e) {
            $this->votesIpAddressSupportCache = false;
        }
        return $this->votesIpAddressSupportCache;
    }
    public function votesSupportsUpdatedAt() {
        if ($this->votesUpdatedAtSupportCache !== null) return $this->votesUpdatedAtSupportCache;
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM university_votes LIKE 'updated_at'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->votesUpdatedAtSupportCache = $col ? true : false;
        } catch (Exception $e) {
            $this->votesUpdatedAtSupportCache = false;
        }
        return $this->votesUpdatedAtSupportCache;
    }
}
?>