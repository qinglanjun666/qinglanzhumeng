<?php
/**
 * MBTI题库接口
 * 返回20道、四维度（E/I、N/S、T/F、J/P）均衡的题库
 * 数据源优先读取 data/mbti_questions.json；若缺失或解析失败，使用内置备份题库。
 */

// CORS & Content-Type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 预检处理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 仅允许GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only GET is supported.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 尝试读取外部JSON题库
$data_file = __DIR__ . '/../data/mbti_questions.json';
$questions = null;

if (file_exists($data_file)) {
    $json = @file_get_contents($data_file);
    $decoded = @json_decode($json, true);
    if (is_array($decoded)) {
        $questions = $decoded;
    }
}

// 回退到内置题库（与 data/mbti_questions.json 保持结构一致）
if (!$questions) {
    $questions = [
        [
            'id' => 1,
            'question' => '你更喜欢哪种聚会？',
            'options' => [
                ['text' => '热闹的大型聚会', 'value' => 'E'],
                ['text' => '小圈子的深度交流', 'value' => 'I']
            ]
        ],
        [
            'id' => 2,
            'question' => '遇到新环境，你通常？',
            'options' => [
                ['text' => '主动结识新朋友', 'value' => 'E'],
                ['text' => '观察一阵再行动', 'value' => 'I']
            ]
        ],
        [
            'id' => 3,
            'question' => '假期你更愿意？',
            'options' => [
                ['text' => '和朋友一起出游', 'value' => 'E'],
                ['text' => '在家独处充电', 'value' => 'I']
            ]
        ],
        [
            'id' => 4,
            'question' => '工作中你更倾向于？',
            'options' => [
                ['text' => '团队合作', 'value' => 'E'],
                ['text' => '独立完成任务', 'value' => 'I']
            ]
        ],
        [
            'id' => 5,
            'question' => '你觉得自己更擅长？',
            'options' => [
                ['text' => '公开表达观点', 'value' => 'E'],
                ['text' => '私下交流想法', 'value' => 'I']
            ]
        ],
        [
            'id' => 6,
            'question' => '你更关注？',
            'options' => [
                ['text' => '未来的可能性', 'value' => 'N'],
                ['text' => '现实的细节', 'value' => 'S']
            ]
        ],
        [
            'id' => 7,
            'question' => '做决定时你更依赖？',
            'options' => [
                ['text' => '灵感和直觉', 'value' => 'N'],
                ['text' => '事实和经验', 'value' => 'S']
            ]
        ],
        [
            'id' => 8,
            'question' => '你喜欢的故事类型？',
            'options' => [
                ['text' => '奇幻、科幻', 'value' => 'N'],
                ['text' => '真实、纪实', 'value' => 'S']
            ]
        ],
        [
            'id' => 9,
            'question' => '你更容易记住？',
            'options' => [
                ['text' => '概念和理论', 'value' => 'N'],
                ['text' => '具体细节和数据', 'value' => 'S']
            ]
        ],
        [
            'id' => 10,
            'question' => '你解决问题时？',
            'options' => [
                ['text' => '喜欢创新方法', 'value' => 'N'],
                ['text' => '喜欢传统方案', 'value' => 'S']
            ]
        ],
        [
            'id' => 11,
            'question' => '你更看重？',
            'options' => [
                ['text' => '逻辑和分析', 'value' => 'T'],
                ['text' => '感受和关系', 'value' => 'F']
            ]
        ],
        [
            'id' => 12,
            'question' => '遇到冲突时？',
            'options' => [
                ['text' => '讲道理解决', 'value' => 'T'],
                ['text' => '体谅对方感受', 'value' => 'F']
            ]
        ],
        [
            'id' => 13,
            'question' => '你更容易被说服？',
            'options' => [
                ['text' => '有理有据的观点', 'value' => 'T'],
                ['text' => '真诚的情感表达', 'value' => 'F']
            ]
        ],
        [
            'id' => 14,
            'question' => '你在团队中？',
            'options' => [
                ['text' => '负责决策和规划', 'value' => 'T'],
                ['text' => '负责关怀和协调', 'value' => 'F']
            ]
        ],
        [
            'id' => 15,
            'question' => '你觉得成功的标准是？',
            'options' => [
                ['text' => '达成目标', 'value' => 'T'],
                ['text' => '让大家满意', 'value' => 'F']
            ]
        ],
        [
            'id' => 16,
            'question' => '你的日程安排？',
            'options' => [
                ['text' => '喜欢提前规划', 'value' => 'J'],
                ['text' => '随遇而安', 'value' => 'P']
            ]
        ],
        [
            'id' => 17,
            'question' => '工作方式？',
            'options' => [
                ['text' => '有条理按计划', 'value' => 'J'],
                ['text' => '灵活应变', 'value' => 'P']
            ]
        ],
        [
            'id' => 18,
            'question' => '你更喜欢？',
            'options' => [
                ['text' => '明确的规则和流程', 'value' => 'J'],
                ['text' => '自由发挥和探索', 'value' => 'P']
            ]
        ],
        [
            'id' => 19,
            'question' => '你处理突发事件时？',
            'options' => [
                ['text' => '先制定方案再行动', 'value' => 'J'],
                ['text' => '边做边调整', 'value' => 'P']
            ]
        ],
        [
            'id' => 20,
            'question' => '你觉得生活更有趣的是？',
            'options' => [
                ['text' => '计划中的成就感', 'value' => 'J'],
                ['text' => '意外的惊喜', 'value' => 'P']
            ]
        ]
    ];
}

// 统计维度均衡性（简要计数）
$dimCounts = ['E' => 0, 'I' => 0, 'N' => 0, 'S' => 0, 'T' => 0, 'F' => 0, 'J' => 0, 'P' => 0];
foreach ($questions as $q) {
    if (!empty($q['options']) && is_array($q['options'])) {
        foreach ($q['options'] as $opt) {
            if (isset($opt['value']) && isset($dimCounts[$opt['value']])) {
                $dimCounts[$opt['value']]++;
            }
        }
    }
}

// 响应
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'MBTI题库获取成功',
    'data' => [
        'questions' => $questions,
        'statistics' => [
            'total_questions' => count($questions),
            'dimension_counts' => $dimCounts
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>