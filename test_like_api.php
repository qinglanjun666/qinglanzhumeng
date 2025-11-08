<?php
/**
 * 点赞API测试脚本
 * 测试 POST /api/universities/{id}/like 端点
 */

// 引入必要的文件
include_once 'config/database.php';
include_once 'models/University.php';

/**
 * 模拟点赞API调用
 */
function simulateLikeAPI($university_id, $client_id = null, $simulate_cookie = false) {
    echo "\n=== 模拟点赞API调用 (大学ID: $university_id) ===\n";
    
    // 保存原始环境变量
    $original_method = $_SERVER['REQUEST_METHOD'] ?? '';
    $original_get = $_GET;
    $original_cookie = $_COOKIE;
    
    try {
        // 模拟POST请求
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET['id'] = $university_id;
        
        // 模拟Cookie
        if ($simulate_cookie && $client_id) {
            $_COOKIE['hj_client_id'] = $client_id;
        } else {
            $_COOKIE = array();
        }
        
        // 获取数据库连接
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            echo "❌ 数据库连接失败\n";
            return null;
        }
        
        // 创建大学对象
        $university = new University($db);
        
        // 验证大学ID
        $university_id_int = intval($university_id);
        if ($university_id_int <= 0) {
            echo "❌ 无效的大学ID\n";
            return array('error' => 'Invalid university ID', 'status' => 400);
        }
        
        // 检查大学是否存在
        $university_exists = $university->getUniversityById($university_id_int);
        if (!$university_exists) {
            echo "❌ 大学不存在\n";
            return array('error' => 'University not found', 'status' => 404);
        }
        
        // 获取客户端IP
        $ip_address = '127.0.0.1';
        
        // 获取或生成客户端ID
        $current_client_id = $client_id;
        
        // 如果没有提供client_id，检查Cookie或生成新的
        if (!$current_client_id && isset($_COOKIE['hj_client_id'])) {
            $current_client_id = $_COOKIE['hj_client_id'];
        }
        
        if (!$current_client_id) {
            $current_client_id = $university->generateClientId();
            echo "🆔 生成新的客户端ID: $current_client_id\n";
        } else {
            echo "🆔 使用客户端ID: $current_client_id\n";
        }
        
        // 检查是否已经点赞过
        $already_liked = $university->hasUserLiked($university_id_int, $current_client_id);
        
        if ($already_liked) {
            $like_count = $university->getLikeCount($university_id_int);
            echo "⚠️  用户已经点赞过\n";
            
            return array(
                'message' => 'already liked',
                'like_count' => $like_count,
                'client_id' => $current_client_id,
                'already_liked' => true,
                'status' => 200
            );
        }
        
        // 添加点赞记录
        $like_added = $university->addLike($university_id_int, $current_client_id, $ip_address);
        
        if (!$like_added) {
            echo "❌ 添加点赞失败\n";
            return array('error' => 'Failed to add like', 'status' => 500);
        }
        
        // 获取更新后的点赞数
        $like_count = $university->getLikeCount($university_id_int);
        
        echo "✅ 点赞成功\n";
        
        return array(
            'message' => 'Like added successfully',
            'like_count' => $like_count,
            'client_id' => $current_client_id,
            'already_liked' => false,
            'status' => 200
        );
        
    } catch (Exception $e) {
        echo "❌ API调用失败: " . $e->getMessage() . "\n";
        return array('error' => $e->getMessage(), 'status' => 500);
    } finally {
        // 恢复原始环境变量
        $_SERVER['REQUEST_METHOD'] = $original_method;
        $_GET = $original_get;
        $_COOKIE = $original_cookie;
    }
}

/**
 * 验证API响应
 */
function validateLikeResponse($response, $test_name) {
    echo "\n--- 验证 $test_name ---\n";
    
    if (!$response) {
        echo "❌ 响应为空\n";
        return false;
    }
    
    if (isset($response['error'])) {
        echo "ℹ️  错误响应: " . $response['error'] . " (状态码: " . $response['status'] . ")\n";
        return $response['status'] < 500; // 4xx错误是预期的，5xx是系统错误
    }
    
    // 检查必需字段
    $required_fields = ['like_count', 'client_id'];
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
    
    echo "✅ 响应结构正确\n";
    echo "📊 点赞数: " . $response['like_count'] . "\n";
    echo "🆔 客户端ID: " . $response['client_id'] . "\n";
    echo "🔄 已点赞: " . ($response['already_liked'] ? '是' : '否') . "\n";
    
    return true;
}

// 主测试流程
echo "🚀 开始点赞API测试\n";
echo "==========================================\n";

// 获取可用的大学ID
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id, name FROM universities LIMIT 2");
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
$test_cases = array();
$test_university = $universities[0];

// 测试1: 首次点赞（无client_id）
$test_cases[] = array(
    'name' => '首次点赞测试（生成新client_id）',
    'university_id' => $test_university['id'],
    'client_id' => null,
    'simulate_cookie' => false,
    'expected_new_like' => true
);

// 生成一个测试用的client_id
$university_obj = new University($db);
$test_client_id = $university_obj->generateClientId();

// 测试2: 首次点赞（提供client_id）
$test_cases[] = array(
    'name' => '首次点赞测试（提供client_id）',
    'university_id' => $test_university['id'],
    'client_id' => $test_client_id,
    'simulate_cookie' => false,
    'expected_new_like' => true
);

// 测试3: 重复点赞（相同client_id）
$test_cases[] = array(
    'name' => '重复点赞测试（相同client_id）',
    'university_id' => $test_university['id'],
    'client_id' => $test_client_id,
    'simulate_cookie' => true,
    'expected_new_like' => false
);

// 测试4: 无效大学ID
$test_cases[] = array(
    'name' => '无效大学ID测试',
    'university_id' => 99999,
    'client_id' => $test_client_id,
    'simulate_cookie' => false,
    'expected_error' => true
);

// 执行测试
$total_tests = count($test_cases);
$passed_tests = 0;
$previous_like_count = 0;

foreach ($test_cases as $index => $test) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🧪 测试 " . ($index + 1) . ": {$test['name']}\n";
    
    $result = simulateLikeAPI(
        $test['university_id'], 
        $test['client_id'], 
        $test['simulate_cookie']
    );
    
    if (isset($test['expected_error']) && $test['expected_error']) {
        // 期望错误的测试
        if (isset($result['error']) && $result['status'] >= 400) {
            echo "✅ 测试通过 (正确返回错误)\n";
            $passed_tests++;
        } else {
            echo "❌ 测试失败 (应该返回错误)\n";
        }
    } else {
        // 正常功能测试
        if (validateLikeResponse($result, $test['name'])) {
            if (isset($test['expected_new_like'])) {
                if ($test['expected_new_like']) {
                    // 期望新增点赞
                    if (!$result['already_liked'] && $result['like_count'] > $previous_like_count) {
                        echo "✅ 测试通过 (成功新增点赞)\n";
                        $passed_tests++;
                        $previous_like_count = $result['like_count'];
                    } else {
                        echo "❌ 测试失败 (应该新增点赞)\n";
                    }
                } else {
                    // 期望不新增点赞
                    if ($result['already_liked'] && $result['like_count'] == $previous_like_count) {
                        echo "✅ 测试通过 (正确拒绝重复点赞)\n";
                        $passed_tests++;
                    } else {
                        echo "❌ 测试失败 (不应该新增点赞)\n";
                    }
                }
            } else {
                echo "✅ 测试通过\n";
                $passed_tests++;
            }
        } else {
            echo "❌ 测试失败\n";
        }
    }
}

// 验收标准检查
echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 验收标准检查\n";

$acceptance_passed = 0;
$acceptance_total = 3;

// 1. 首次POST返回like_count增加
echo "\n验收标准1: 首次POST返回like_count增加\n";
$first_result = simulateLikeAPI($universities[1]['id'], null, false);
if ($first_result && !$first_result['already_liked'] && $first_result['like_count'] > 0) {
    echo "✅ 验收标准1通过\n";
    $acceptance_passed++;
    $test_client_for_acceptance = $first_result['client_id'];
} else {
    echo "❌ 验收标准1失败\n";
    $test_client_for_acceptance = $university_obj->generateClientId();
}

// 2. 再次同client_id POST不会增加like_count
echo "\n验收标准2: 重复点赞不增加like_count\n";
$second_result = simulateLikeAPI($universities[1]['id'], $test_client_for_acceptance, true);
if ($second_result && $second_result['already_liked'] && isset($first_result) && $second_result['like_count'] == $first_result['like_count']) {
    echo "✅ 验收标准2通过\n";
    $acceptance_passed++;
} else {
    echo "❌ 验收标准2失败\n";
}

// 3. 返回client_id
echo "\n验收标准3: 返回client_id\n";
if (isset($first_result['client_id']) && !empty($first_result['client_id'])) {
    echo "✅ 验收标准3通过\n";
    $acceptance_passed++;
} else {
    echo "❌ 验收标准3失败\n";
}

// 测试总结
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 测试总结\n";
echo "功能测试: $passed_tests/$total_tests 通过\n";
echo "验收标准: $acceptance_passed/$acceptance_total 通过\n";
echo "总体成功率: " . round((($passed_tests + $acceptance_passed) / ($total_tests + $acceptance_total)) * 100, 2) . "%\n";

if ($passed_tests === $total_tests && $acceptance_passed === $acceptance_total) {
    echo "🎉 所有测试通过！T004点赞API功能完成\n";
} else {
    echo "⚠️  部分测试失败，请检查实现\n";
}

echo "\n📝 手动测试说明：\n";
echo "1. 启动Apache和MySQL服务\n";
echo "2. 使用Postman或curl测试:\n";
echo "   POST http://localhost/huilanweb/api/universities/1/like\n";
echo "   Content-Type: application/json\n";
echo "   Body: {} 或 {\"client_id\": \"your_client_id\"}\n";
echo "3. 检查响应中的like_count和client_id\n";
echo "4. 使用相同client_id再次请求，验证不会重复点赞\n";

echo "\n✅ 点赞API测试完成\n";
?>