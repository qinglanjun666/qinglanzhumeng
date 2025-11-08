<?php
/**
 * 命令行API测试脚本
 * 直接测试API逻辑，不依赖Web服务器
 */

echo "=== 绘斓网站 API 功能测试 ===\n\n";

// 模拟$_GET参数进行测试
function testUniversityAPI($params = []) {
    // 保存原始$_GET
    $original_get = $_GET;
    
    // 设置测试参数
    $_GET = $params;
    
    // 引入必要的文件
    include_once 'config/database.php';
    include_once 'models/University.php';
    
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
        
        // 获取查询参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 20;
        $mood_type = isset($_GET['mood_type']) ? trim($_GET['mood_type']) : null;
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;
        
        // 验证mood_type参数（如果提供）
        if ($mood_type && !empty($mood_type)) {
            $check_query = "SELECT COUNT(*) as count FROM mood_types WHERE slug = :slug";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':slug', $mood_type);
            $check_stmt->execute();
            $result = $check_stmt->fetch();
            
            if ($result['count'] == 0) {
                echo "❌ 无效的mood_type参数: $mood_type\n";
                return false;
            }
        }
        
        // 获取大学列表
        $result = $university->getUniversities($page, $per_page, $mood_type, $q);
        
        // 恢复原始$_GET
        $_GET = $original_get;
        
        return $result;
        
    } catch (Exception $e) {
        echo "❌ 错误: " . $e->getMessage() . "\n";
        $_GET = $original_get;
        return false;
    }
}

// 测试用例
$test_cases = [
    [
        'name' => '基础分页测试',
        'params' => ['page' => 1, 'per_page' => 5],
        'description' => '测试基本的分页功能'
    ],
    [
        'name' => '气质类型筛选测试',
        'params' => ['mood_type' => 'rational_creator'],
        'description' => '测试按理性创造型筛选'
    ],
    [
        'name' => '搜索功能测试',
        'params' => ['q' => '清华'],
        'description' => '测试搜索关键字功能'
    ],
    [
        'name' => '组合查询测试',
        'params' => ['page' => 1, 'per_page' => 10, 'mood_type' => 'scholarly_thinker', 'q' => '大学'],
        'description' => '测试组合查询功能'
    ],
    [
        'name' => '默认参数测试',
        'params' => [],
        'description' => '测试默认参数（无筛选条件）'
    ]
];

$passed = 0;
$total = count($test_cases);

foreach ($test_cases as $i => $test) {
    echo "测试 " . ($i + 1) . ": " . $test['name'] . "\n";
    echo "描述: " . $test['description'] . "\n";
    echo "参数: " . json_encode($test['params']) . "\n";
    
    $result = testUniversityAPI($test['params']);
    
    if ($result !== false) {
        echo "✅ 测试通过\n";
        echo "总记录数: " . $result['total'] . "\n";
        echo "当前页: " . $result['page'] . "\n";
        echo "每页数量: " . $result['per_page'] . "\n";
        echo "返回记录数: " . count($result['data']) . "\n";
        
        if (!empty($result['data'])) {
            echo "第一条记录: " . $result['data'][0]['name'] . " (" . $result['data'][0]['mood_type_slug'] . ")\n";
        }
        
        $passed++;
    } else {
        echo "❌ 测试失败\n";
    }
    
    echo str_repeat("-", 50) . "\n\n";
}

echo "=== 测试总结 ===\n";
echo "通过: $passed/$total\n";

if ($passed == $total) {
    echo "🎉 所有测试通过！API功能正常\n";
} else {
    echo "❌ 部分测试失败，请检查配置\n";
}

echo "\n=== 验收标准检查 ===\n";

// 验收标准1: 检查返回结构
echo "1. 检查API返回结构...\n";
$basic_result = testUniversityAPI(['page' => 1, 'per_page' => 20]);
if ($basic_result && isset($basic_result['total']) && isset($basic_result['data'])) {
    echo "✅ 返回结构包含total字段和data字段\n";
    
    if (!empty($basic_result['data'])) {
        $first_item = $basic_result['data'][0];
        $required_fields = ['id', 'name', 'province', 'city', 'type', 'mood_type_slug', 'one_line', 'logo_url', 'like_count', 'poll_counts'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $first_item)) {
                $missing_fields[] = $field;
            }
        }
        
        if (empty($missing_fields)) {
            echo "✅ 数据项包含所有必需字段\n";
        } else {
            echo "❌ 缺少字段: " . implode(', ', $missing_fields) . "\n";
        }
    }
} else {
    echo "❌ 返回结构不正确\n";
}

// 验收标准2: 检查气质类型筛选
echo "\n2. 检查气质类型筛选功能...\n";
$filter_result = testUniversityAPI(['mood_type' => 'rational_creator']);
if ($filter_result !== false) {
    echo "✅ 气质类型筛选功能正常\n";
    if (!empty($filter_result['data'])) {
        $all_match = true;
        foreach ($filter_result['data'] as $item) {
            if ($item['mood_type_slug'] !== 'rational_creator') {
                $all_match = false;
                break;
            }
        }
        if ($all_match) {
            echo "✅ 筛选结果正确，所有记录都属于指定气质类型\n";
        } else {
            echo "❌ 筛选结果不正确，包含其他气质类型的记录\n";
        }
    }
} else {
    echo "❌ 气质类型筛选功能异常\n";
}

echo "\n=== 手动测试说明 ===\n";
echo "1. 确保MySQL服务已启动并执行了database_init.sql\n";
echo "2. 启动Apache服务后，可通过以下URL测试:\n";
echo "   - http://localhost/huilanweb/api/universities?page=1&per_page=20\n";
echo "   - http://localhost/huilanweb/api/universities?mood_type=rational_creator\n";
echo "   - http://localhost/huilanweb/test_api.php (Web界面测试)\n";
echo "3. 或使用curl命令测试API端点\n";
?>