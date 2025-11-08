<?php
/**
 * 大学详情API测试脚本
 * 测试 /api/universities/{id} 端点
 */

// 引入必要的文件
include_once 'config/database.php';
include_once 'models/University.php';

/**
 * 测试大学详情API
 */
function testUniversityDetailAPI($id) {
    echo "\n=== 测试大学详情 API (ID: $id) ===\n";
    
    try {
        // 获取数据库连接
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            echo "❌ 数据库连接失败\n";
            return false;
        }
        
        // 创建大学对象
        $university = new University($db);
        
        // 获取大学详情
        $result = $university->getUniversityDetail($id);
        
        if (!$result) {
            echo "❌ 未找到ID为 $id 的大学\n";
            return false;
        }
        
        echo "✅ 成功获取大学详情\n";
        echo "📊 返回数据结构：\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // 验证必需字段
        $required_fields = ['id', 'name', 'province', 'city', 'type', 'one_line', 'keywords', 'logo_url', 'mood_type', 'like_count', 'vote_distribution'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($result[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (empty($missing_fields)) {
            echo "✅ 所有必需字段都存在\n";
        } else {
            echo "❌ 缺少字段: " . implode(', ', $missing_fields) . "\n";
        }
        
        // 验证mood_type结构
        if (isset($result['mood_type'])) {
            $mood_required = ['id', 'slug', 'name', 'short_desc', 'color'];
            $mood_missing = [];
            
            foreach ($mood_required as $field) {
                if (!isset($result['mood_type'][$field])) {
                    $mood_missing[] = $field;
                }
            }
            
            if (empty($mood_missing)) {
                echo "✅ mood_type 结构正确\n";
            } else {
                echo "❌ mood_type 缺少字段: " . implode(', ', $mood_missing) . "\n";
            }
        }
        
        // 验证vote_distribution
        if (isset($result['vote_distribution'])) {
            echo "✅ vote_distribution 包含 " . count($result['vote_distribution']) . " 个心情类型\n";
            
            // 检查是否包含所有心情类型
            $stmt = $db->prepare("SELECT slug FROM mood_types");
            $stmt->execute();
            $all_moods = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $missing_moods = [];
            foreach ($all_moods as $mood_slug) {
                if (!isset($result['vote_distribution'][$mood_slug])) {
                    $missing_moods[] = $mood_slug;
                }
            }
            
            if (empty($missing_moods)) {
                echo "✅ vote_distribution 包含所有心情类型\n";
            } else {
                echo "❌ vote_distribution 缺少心情类型: " . implode(', ', $missing_moods) . "\n";
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ 测试失败: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 获取可用的大学ID列表
 */
function getAvailableUniversityIds() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT id, name FROM universities LIMIT 5");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "❌ 获取大学列表失败: " . $e->getMessage() . "\n";
        return [];
    }
}

// 主测试流程
echo "🚀 开始测试大学详情API\n";
echo "==========================================\n";

// 获取可用的大学ID
$universities = getAvailableUniversityIds();

if (empty($universities)) {
    echo "❌ 无法获取大学数据，请检查数据库连接和数据\n";
    exit(1);
}

echo "📋 可用的大学列表：\n";
foreach ($universities as $uni) {
    echo "  - ID: {$uni['id']}, 名称: {$uni['name']}\n";
}

$success_count = 0;
$total_tests = 0;

// 测试有效ID
foreach ($universities as $uni) {
    $total_tests++;
    if (testUniversityDetailAPI($uni['id'])) {
        $success_count++;
    }
}

// 测试无效ID
echo "\n=== 测试无效ID ===\n";
$total_tests++;
try {
    $database = new Database();
    $db = $database->getConnection();
    $university = new University($db);
    
    $result = $university->getUniversityDetail(99999);
    if ($result === null) {
        echo "✅ 无效ID正确返回null\n";
        $success_count++;
    } else {
        echo "❌ 无效ID应该返回null\n";
    }
} catch (Exception $e) {
    echo "❌ 测试无效ID失败: " . $e->getMessage() . "\n";
}

// 测试总结
echo "\n==========================================\n";
echo "📊 测试总结\n";
echo "总测试数: $total_tests\n";
echo "成功: $success_count\n";
echo "失败: " . ($total_tests - $success_count) . "\n";
echo "成功率: " . round(($success_count / $total_tests) * 100, 2) . "%\n";

if ($success_count === $total_tests) {
    echo "🎉 所有测试通过！\n";
} else {
    echo "⚠️  部分测试失败，请检查实现\n";
}

echo "\n📝 手动测试说明：\n";
echo "1. 确保Apache和MySQL服务正在运行\n";
echo "2. 使用浏览器访问: http://localhost/huilanweb/api/universities/1\n";
echo "3. 使用curl测试: curl \"http://localhost/huilanweb/api/universities/1\"\n";
echo "4. 使用Postman测试GET请求到上述URL\n";

echo "\n✅ T003 大学详情API测试完成\n";
?>