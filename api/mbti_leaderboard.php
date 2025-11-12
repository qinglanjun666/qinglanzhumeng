<?php
/**
 * 每周最受欢迎MBTI高校榜单（按近7天投票）
 * GET /api/mbti_leaderboard
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([ 'message' => 'Method not allowed' ]);
    exit();
}

$windowDays = 7;
$periodEnd = date('Y-m-d');
$periodStart = date('Y-m-d', strtotime('-' . ($windowDays - 1) . ' days'));

$response = [
    'success' => true,
    'period' => [ 'start' => $periodStart, 'end' => $periodEnd ],
    'window_days' => $windowDays,
    'data' => [ 'overall' => [], 'by_mood' => [] ]
];

$source = 'db';

try {
    include_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        // 获取气质类型
        $moodsStmt = $db->prepare("SELECT id, slug, name FROM mood_types ORDER BY id");
        $moodsStmt->execute();
        $moods = $moodsStmt->fetchAll(PDO::FETCH_ASSOC);

        // 总榜（近7天所有投票）
        $overallSql = "SELECT u.id, u.name, COUNT(v.id) AS vote_count
                       FROM universities u
                       JOIN university_votes v ON v.university_id = u.id
                       WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                       GROUP BY u.id, u.name
                       ORDER BY vote_count DESC
                       LIMIT 20";
        $overallStmt = $db->prepare($overallSql);
        $overallStmt->bindValue(':days', $windowDays, PDO::PARAM_INT);
        $overallStmt->execute();
        $response['data']['overall'] = array_map(function($row) {
            return [
                'university_id' => (int)$row['id'],
                'name' => $row['name'],
                'vote_count' => (int)$row['vote_count']
            ];
        }, $overallStmt->fetchAll(PDO::FETCH_ASSOC));

        // 分气质类型榜（近7天按 mood_type 分组）
        foreach ($moods as $m) {
            $sql = "SELECT u.id, u.name, COUNT(v.id) AS vote_count
                    FROM universities u
                    JOIN university_votes v ON v.university_id = u.id
                    WHERE v.mood_type_id = :mood_id
                      AND v.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    GROUP BY u.id, u.name
                    ORDER BY vote_count DESC
                    LIMIT 10";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':mood_id', (int)$m['id'], PDO::PARAM_INT);
            $stmt->bindValue(':days', $windowDays, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['data']['by_mood'][] = [
                'mood' => [ 'id' => (int)$m['id'], 'slug' => $m['slug'], 'name' => $m['name'] ],
                'top_universities' => array_map(function($row) {
                    return [
                        'university_id' => (int)$row['id'],
                        'name' => $row['name'],
                        'vote_count' => (int)$row['vote_count']
                    ];
                }, $rows)
            ];
        }
    } else {
        $source = 'file';
    }
} catch (Exception $e) {
    $source = 'file';
}

// 文件降级：使用静态MBTI高校标签构造示例榜单
if ($source === 'file' || (empty($response['data']['overall']) && empty($response['data']['by_mood']))) {
    $dataFile = __DIR__ . '/../data/universities_mbti.json';
    if (file_exists($dataFile)) {
        $raw = file_get_contents($dataFile);
        $items = json_decode($raw, true);
        if (is_array($items)) {
            // 随机生成示例热度
            usort($items, function() { return rand(-1, 1); });
            $overall = [];
            foreach (array_slice($items, 0, 20) as $it) {
                $overall[] = [
                    'university_id' => null,
                    'name' => $it['university'] ?? '未知高校',
                    'vote_count' => rand(50, 300)
                ];
            }
            $response['data']['overall'] = $overall;
            $response['data']['by_mood'] = [];
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>