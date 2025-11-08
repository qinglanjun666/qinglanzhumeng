<?php
/**
 * 测评模块测试脚本
 * 测试测评API的功能和匹配算法的准确性
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 包含必要的文件
require_once 'config/database.php';
require_once 'models/Assessment.php';

class AssessmentTester {
    private $baseUrl;
    private $assessment;
    
    public function __construct() {
        $this->baseUrl = 'http://localhost/huilanweb/api';
        
        // 创建数据库连接
        $database = new Database();
        $db = $database->getConnection();
        $this->assessment = new Assessment($db);
    }
    
    /**
     * 运行所有测试
     */
    public function runAllTests() {
        echo "=== 测评模块测试开始 ===\n\n";
        
        $this->testGetQuestions();
        $this->testKnownAnswerSets();
        $this->testInvalidInputs();
        $this->testMatchingAlgorithm();
        $this->testUniversityRecommendation();
        
        echo "\n=== 测评模块测试完成 ===\n";
    }
    
    /**
     * 测试获取题目API
     */
    public function testGetQuestions() {
        echo "1. 测试获取题目API\n";
        echo str_repeat("-", 50) . "\n";
        
        $response = $this->makeApiCall('/assessment/questions', 'GET');
        
        if ($response && $response['success']) {
            $questions = $response['data']['questions'];
            echo "✓ 成功获取 " . count($questions) . " 道题目\n";
            
            // 验证题目结构
            foreach ($questions as $index => $question) {
                if (!isset($question['id'], $question['question_text'], $question['options'])) {
                    echo "✗ 题目 " . ($index + 1) . " 结构不完整\n";
                    return false;
                }
                
                if (count($question['options']) < 2) {
                    echo "✗ 题目 " . ($index + 1) . " 选项数量不足\n";
                    return false;
                }
            }
            
            echo "✓ 所有题目结构验证通过\n";
            echo "✓ 题目数量: " . count($questions) . " (要求: 6-8题)\n";
            
            if (count($questions) >= 6 && count($questions) <= 8) {
                echo "✓ 题目数量符合要求\n";
            } else {
                echo "✗ 题目数量不符合要求 (6-8题)\n";
            }
        } else {
            echo "✗ 获取题目失败\n";
            if ($response) {
                echo "错误信息: " . ($response['message'] ?? '未知错误') . "\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * 测试已知答案集
     */
    public function testKnownAnswerSets() {
        echo "2. 测试已知答案集\n";
        echo str_repeat("-", 50) . "\n";
        
        // 定义测试用例：每个用例包含答案和期望的mood_type
        $testCases = [
            [
                'name' => '理性创造型',
                'answers' => [1, 5, 9, 13, 17, 21, 25], // 选择理性创造相关选项
                'expected_mood' => 1
            ],
            [
                'name' => '文艺探索型', 
                'answers' => [2, 6, 10, 14, 18, 22, 26], // 选择文艺探索相关选项
                'expected_mood' => 2
            ],
            [
                'name' => '务实应用型',
                'answers' => [3, 7, 11, 15, 19, 23, 27], // 选择务实应用相关选项
                'expected_mood' => 3
            ],
            [
                'name' => '社交领导型',
                'answers' => [4, 8, 12, 16, 20, 24, 28], // 选择社交领导相关选项
                'expected_mood' => 4
            ]
        ];
        
        foreach ($testCases as $testCase) {
            echo "测试用例: " . $testCase['name'] . "\n";
            
            $response = $this->makeApiCall('/assessment/submit', 'POST', [
                'answers' => $testCase['answers']
            ]);
            
            if ($response && $response['success']) {
                $userMood = $response['user_mood'];
                $matchedUniversities = $response['matched_universities'];
                
                echo "  结果气质类型: " . $userMood['name'] . " (ID: " . $userMood['id'] . ")\n";
                echo "  期望气质类型ID: " . $testCase['expected_mood'] . "\n";
                
                if ($userMood['id'] == $testCase['expected_mood']) {
                    echo "  ✓ 气质类型匹配正确\n";
                } else {
                    echo "  ✗ 气质类型匹配错误\n";
                }
                
                echo "  匹配大学数量: " . count($matchedUniversities) . "\n";
                
                if (count($matchedUniversities) >= 6) {
                    echo "  ✓ 推荐大学数量符合要求 (≥6所)\n";
                } else {
                    echo "  ⚠ 推荐大学数量不足6所\n";
                }
                
                // 检查匹配类型分布
                $primaryMatches = array_filter($matchedUniversities, function($uni) {
                    return $uni['match_type'] === 'primary';
                });
                
                echo "  完美匹配大学: " . count($primaryMatches) . "所\n";
                
            } else {
                echo "  ✗ 测试失败\n";
                if ($response) {
                    echo "  错误信息: " . ($response['message'] ?? '未知错误') . "\n";
                }
            }
            
            echo "\n";
        }
    }
    
    /**
     * 测试无效输入
     */
    public function testInvalidInputs() {
        echo "3. 测试无效输入处理\n";
        echo str_repeat("-", 50) . "\n";
        
        $invalidCases = [
            [
                'name' => '空答案数组',
                'data' => ['answers' => []]
            ],
            [
                'name' => '答案数量不足',
                'data' => ['answers' => [1, 2, 3]]
            ],
            [
                'name' => '无效选项ID',
                'data' => ['answers' => [999, 998, 997, 996, 995, 994, 993]]
            ],
            [
                'name' => '缺少answers字段',
                'data' => ['invalid' => 'data']
            ]
        ];
        
        foreach ($invalidCases as $case) {
            echo "测试: " . $case['name'] . "\n";
            
            $response = $this->makeApiCall('/assessment/submit', 'POST', $case['data']);
            
            if ($response && !$response['success']) {
                echo "  ✓ 正确返回错误信息: " . $response['message'] . "\n";
            } else {
                echo "  ✗ 应该返回错误但没有\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * 测试匹配算法
     */
    public function testMatchingAlgorithm() {
        echo "4. 测试匹配算法\n";
        echo str_repeat("-", 50) . "\n";
        
        // 直接测试Assessment类的方法
        try {
            // 测试权重计算
            $testAnswers = [1, 5, 9, 13, 17, 21, 25]; // 理性创造型答案
            $moodScores = $this->assessment->calculateMoodScores($testAnswers);
            
            echo "权重计算结果:\n";
            foreach ($moodScores as $moodId => $score) {
                echo "  气质类型 $moodId: $score 分\n";
            }
            
            $topMood = array_keys($moodScores, max($moodScores))[0];
            echo "最高分气质类型: $topMood\n";
            
            if ($topMood == 1) {
                echo "✓ 权重计算正确\n";
            } else {
                echo "✗ 权重计算可能有误\n";
            }
            
            // 测试大学推荐
            $universities = $this->assessment->getMatchedUniversities($topMood);
            echo "推荐大学数量: " . count($universities) . "\n";
            
            if (count($universities) > 0) {
                echo "✓ 成功获取推荐大学\n";
                
                // 检查匹配类型
                $primaryCount = 0;
                $secondaryCount = 0;
                
                foreach ($universities as $uni) {
                    if ($uni['match_type'] === 'primary') {
                        $primaryCount++;
                    } else {
                        $secondaryCount++;
                    }
                }
                
                echo "完美匹配: $primaryCount 所, 推荐匹配: $secondaryCount 所\n";
            } else {
                echo "✗ 未能获取推荐大学\n";
            }
            
        } catch (Exception $e) {
            echo "✗ 算法测试出错: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * 测试大学推荐策略
     */
    public function testUniversityRecommendation() {
        echo "5. 测试大学推荐策略\n";
        echo str_repeat("-", 50) . "\n";
        
        // 测试每种气质类型的推荐
        for ($moodId = 1; $moodId <= 4; $moodId++) {
            echo "测试气质类型 $moodId 的推荐:\n";
            
            try {
                $universities = $this->assessment->getMatchedUniversities($moodId);
                
                if (count($universities) > 0) {
                    echo "  ✓ 获取到 " . count($universities) . " 所大学\n";
                    
                    // 检查排序逻辑
                    $primaryMatches = array_filter($universities, function($uni) {
                        return $uni['match_type'] === 'primary';
                    });
                    
                    echo "  完美匹配: " . count($primaryMatches) . " 所\n";
                    
                    // 验证前几所是否为完美匹配
                    $firstFew = array_slice($universities, 0, min(3, count($universities)));
                    $allPrimary = true;
                    foreach ($firstFew as $uni) {
                        if ($uni['match_type'] !== 'primary' && count($primaryMatches) > 0) {
                            $allPrimary = false;
                            break;
                        }
                    }
                    
                    if ($allPrimary || count($primaryMatches) === 0) {
                        echo "  ✓ 排序逻辑正确\n";
                    } else {
                        echo "  ⚠ 排序逻辑可能需要优化\n";
                    }
                    
                } else {
                    echo "  ⚠ 该气质类型暂无匹配大学\n";
                }
                
            } catch (Exception $e) {
                echo "  ✗ 推荐测试出错: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * 发起API调用
     */
    private function makeApiCall($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            echo "cURL错误: " . curl_error($ch) . "\n";
            return null;
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode !== 200) {
            echo "HTTP错误: $httpCode\n";
            echo "响应: $response\n";
        }
        
        return $decoded;
    }
    
    /**
     * 生成测试报告
     */
    public function generateTestReport() {
        echo "\n=== 测试报告 ===\n";
        echo "测试时间: " . date('Y-m-d H:i:s') . "\n";
        echo "测试环境: " . $this->baseUrl . "\n";
        echo "\n";
        
        echo "手动测试建议:\n";
        echo "1. 访问 http://localhost/huilanweb/assessment.html 进行完整测评\n";
        echo "2. 尝试不同的答案组合，验证结果的一致性\n";
        echo "3. 检查前端界面的用户体验\n";
        echo "4. 验证推荐大学的相关性\n";
        echo "\n";
        
        echo "API测试命令:\n";
        echo "# 获取题目\n";
        echo "curl -X GET \"http://localhost/huilanweb/api/assessment/questions\"\n\n";
        
        echo "# 提交测评 (理性创造型)\n";
        echo "curl -X POST \"http://localhost/huilanweb/api/assessment/submit\" \\\n";
        echo "  -H \"Content-Type: application/json\" \\\n";
        echo "  -d '{\"answers\":[1,5,9,13,17,21,25]}'\n\n";
        
        echo "# 提交测评 (文艺探索型)\n";
        echo "curl -X POST \"http://localhost/huilanweb/api/assessment/submit\" \\\n";
        echo "  -H \"Content-Type: application/json\" \\\n";
        echo "  -d '{\"answers\":[2,6,10,14,18,22,26]}'\n\n";
    }
}

// 运行测试
if (php_sapi_name() === 'cli') {
    // 命令行模式
    $tester = new AssessmentTester();
    $tester->runAllTests();
    $tester->generateTestReport();
} else {
    // Web模式
    header('Content-Type: text/plain; charset=utf-8');
    $tester = new AssessmentTester();
    $tester->runAllTests();
    $tester->generateTestReport();
}
?>