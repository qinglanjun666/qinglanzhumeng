<?php
/**
 * 命令行大学详情API测试脚本
 * 独立于Web服务器运行
 */

// 引入必要的文件
include_once 'config/database.php';
include_once 'models/University.php';

/**
 * 模拟API调用测试
 */
function simulateDetailAPI($id) {
    echo "\n=== 模拟API调用: /api/universities/$id ===\n";
    
    // 保存原始$_GET
    $original_get = $_GET;
    
    try {
        // 模拟$_GET参数
        $_GET['id'] = $id;
        
        // 获取数据库连接
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            echo "❌ 数据库连接失败\n";
            return null;
        }
        
        // 创建大学对象
        $university = new University($db);
        
        // 验证ID参数
        $university_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($university_id <= 0) {
            echo "❌ 无效的大学ID\n";
            return null;
        }
        
        // 获取大学详情
        $result = $university->getUniversityDetail($university_id);
        
        if (!$result) {
            echo "❌ 未找到大学 (ID: $university_id)\n";
            return null;
        }
        
        echo "✅ 成功获取大学详情\n";
        return $result;
        
    } catch (Exception $e) {
        echo "❌ API调用失败: " . $e->getMessage() . "\n";
        return null;
    } finally {
        // 恢复原始$_GET
        $_GET = $original_get;
    }
}

/**
 * 验证API响应结构
 */
function validateAPIResponse($response, $test_name) {
    echo "\n--- 验证 $test_name ---\n";
    
    if (!$response) {
        echo "❌ 响应为空\n";
        return false;
    }
    
    // 检查必需字段
    $required_fields = [
        'id', 'name', 'province', 'city', 'type', 
        'one_line', 'keywords', 'logo_url', 
        'mood_type', 'like_count', 'vote_distribution'
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($response[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo "❌ 缺少必需字段: " . implode(', ', $missing_fields) . "\n";
        return false;
    }
    
    echo "✅ 所有必需字段都存在\n";
    
    // 验证mood_type结构
    if (isset($response['mood_type'])) {
        $mood_required = ['id', 'slug', 'name', 'short_desc', 'color'];
        $mood_missing = [];
        
        foreach ($mood_required as $field) {
            if (!isset($response['mood_type'][$field])) {
                $mood_missing[] = $field;
            }
        }
        
        if (empty($mood_missing)) {
            echo "✅ mood_type 结构正确\n";
        } else {
            echo "❌ mood_type 缺少字段: " . implode(', ', $mood_missing) . "\n";
            return false;
        }
    }
    
    // 验证vote_distribution
    if (isset($response['vote_distribution']) && is_array($response['vote_distribution'])) {
        echo "✅ vote_distribution 包含 " . count($response['vote_distribution']) . " 个心情类型\n";
        
        // 检查所有值都是数字
        $all_numeric = true;
        foreach ($response['vote_distribution'] as $slug => $count) {
            if (!is_numeric($count)) {
                $all_numeric = false;
                break;
            }
        }
        
        if ($all_numeric) {
            echo "✅ vote_distribution 所有计数都是数字\n";
        } else {
            echo "❌ vote_distribution 包含非数字值\n";
            return false;
        }
    } else {
        echo "❌ vote_distribution 不是有效数组\n";
        return false;
    }
    
    // 验证like_count
    if (isset($response['like_count']) && is_numeric($response['like_count'])) {
        echo "✅ like_count 是有效数字: " . $response['like_count'] . "\n";
    } else {
        echo "❌ like_count 不是有效数字\n";
        return false;
    }
    
    return true;
}

// 主测试流程
echo "🚀 开始命令行大学详情API测试\n";
echo "==========================================\n";

// 获取可用的大学ID
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id, name FROM universities LIMIT 3");
    $stmt->execute();
    $universities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($universities)) {
        echo "❌ 无法获取大学数据\n";
        exit(1);
    }
    
    echo "📋 将测试以下大学：\n";
    foreach ($universities as $uni) {
        echo "  - ID: {$uni['id']}, 名称: {$uni['name']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 定义测试用例
$test_cases = [];

// 添加有效ID测试
foreach ($universities as $uni) {
    $test_cases[] = [
        'id' => $uni['id'],
        'name' => "有效ID测试 - {$uni['name']}",
        'should_succeed' => true
    ];
}

// 添加无效ID测试
$test_cases[] = [
    'id' => 99999,
    'name' => '无效ID测试',
    'should_succeed' => false
];

$test_cases[] = [
    'id' => 0,
    'name' => '零ID测试',
    'should_succeed' => false
];

$test_cases[] = [
    'id' => -1,
    'name' => '负数ID测试',
    'should_succeed' => false
];

// 执行测试
$total_tests = count($test_cases);
$passed_tests = 0;

foreach ($test_cases as $test) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🧪 测试: {$test['name']}\n";
    
    $result = simulateDetailAPI($test['id']);
    
    if ($test['should_succeed']) {
        if ($result && validateAPIResponse($result, $test['name'])) {
            echo "✅ 测试通过\n";
            $passed_tests++;
            
            // 显示部分响应数据
            echo "📊 响应数据预览：\n";
            echo "  - 大学名称: " . ($result['name'] ?? 'N/A') . "\n";
            echo "  - 省份: " . ($result['province'] ?? 'N/A') . "\n";
            echo "  - 心情类型: " . ($result['mood_type']['name'] ?? 'N/A') . "\n";
            echo "  - 点赞数: " . ($result['like_count'] ?? 0) . "\n";
            echo "  - 投票分布: " . json_encode($result['vote_distribution'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "❌ 测试失败\n";
        }
    } else {
        if (!$result) {
            echo "✅ 测试通过 (正确返回空结果)\n";
            $passed_tests++;
        } else {
            echo "❌ 测试失败 (应该返回空结果)\n";
        }
    }
}

// 验收标准检查
echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 验收标准检查\n";

$acceptance_passed = 0;
$acceptance_total = 3;

// 1. 检查有效ID返回正确结构
$valid_test = simulateDetailAPI($universities[0]['id']);
if ($valid_test && validateAPIResponse($valid_test, "验收标准1")) {
    echo "✅ 验收标准1: 有效ID返回正确结构\n";
    $acceptance_passed++;
} else {
    echo "❌ 验收标准1: 有效ID返回结构不正确\n";
}

// 2. 检查vote_distribution包含所有mood_types
if ($valid_test && isset($valid_test['vote_distribution'])) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM mood_types");
        $stmt->execute();
        $mood_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if (count($valid_test['vote_distribution']) == $mood_count) {
            echo "✅ 验收标准2: vote_distribution包含所有心情类型\n";
            $acceptance_passed++;
        } else {
            echo "❌ 验收标准2: vote_distribution缺少某些心情类型\n";
        }
    } catch (Exception $e) {
        echo "❌ 验收标准2: 无法验证心情类型数量\n";
    }
} else {
    echo "❌ 验收标准2: vote_distribution不存在\n";
}

// 3. 检查无效ID返回null
$invalid_test = simulateDetailAPI(99999);
if (!$invalid_test) {
    echo "✅ 验收标准3: 无效ID正确返回null\n";
    $acceptance_passed++;
} else {
    echo "❌ 验收标准3: 无效ID应该返回null\n";
}

// 测试总结
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 测试总结\n";
echo "功能测试: $passed_tests/$total_tests 通过\n";
echo "验收标准: $acceptance_passed/$acceptance_total 通过\n";
echo "总体成功率: " . round((($passed_tests + $acceptance_passed) / ($total_tests + $acceptance_total)) * 100, 2) . "%\n";

if ($passed_tests === $total_tests && $acceptance_passed === $acceptance_total) {
    echo "🎉 所有测试通过！T003任务完成\n";
} else {
    echo "⚠️  部分测试失败，请检查实现\n";
}

echo "\n📝 手动测试说明：\n";
echo "1. 启动Apache和MySQL服务\n";
echo "2. 浏览器测试: http://localhost/huilanweb/api/universities/1\n";
echo "3. cURL测试: curl \"http://localhost/huilanweb/api/universities/1\"\n";
echo "4. Postman测试: GET http://localhost/huilanweb/api/universities/1\n";
echo "5. 测试无效ID: http://localhost/huilanweb/api/universities/99999\n";

echo "\n✅ 命令行测试完成\n";
?>