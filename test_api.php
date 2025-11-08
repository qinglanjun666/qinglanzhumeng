<?php
/**
 * API测试脚本
 * 用于测试大学列表API的各种功能
 */

echo "<h1>绘斓网站 API 测试</h1>";

// 测试用例
$test_cases = [
    [
        'name' => '基础分页测试',
        'url' => 'http://localhost/huilanweb/api/universities?page=1&per_page=5',
        'description' => '测试基本的分页功能'
    ],
    [
        'name' => '气质类型筛选测试',
        'url' => 'http://localhost/huilanweb/api/universities?mood_type=rational_creator',
        'description' => '测试按理性创造型筛选'
    ],
    [
        'name' => '搜索功能测试',
        'url' => 'http://localhost/huilanweb/api/universities?q=清华',
        'description' => '测试搜索关键字功能'
    ],
    [
        'name' => '组合查询测试',
        'url' => 'http://localhost/huilanweb/api/universities?page=1&per_page=10&mood_type=scholarly_thinker&q=大学',
        'description' => '测试组合查询功能'
    ]
];

function testAPI($url, $name, $description) {
    echo "<h3>$name</h3>";
    echo "<p><strong>描述:</strong> $description</p>";
    echo "<p><strong>URL:</strong> <a href='$url' target='_blank'>$url</a></p>";
    
    // 使用cURL发送请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'><strong>错误:</strong> $error</p>";
        return false;
    }
    
    echo "<p><strong>HTTP状态码:</strong> $http_code</p>";
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if ($data) {
            echo "<p style='color: green;'><strong>✅ 测试通过</strong></p>";
            echo "<p><strong>返回数据结构:</strong></p>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
            
            // 显示数据结构摘要
            if (isset($data['data']) && is_array($data['data'])) {
                echo "总记录数: " . ($data['total'] ?? 'N/A') . "\n";
                echo "当前页: " . ($data['page'] ?? 'N/A') . "\n";
                echo "每页数量: " . ($data['per_page'] ?? 'N/A') . "\n";
                echo "总页数: " . ($data['total_pages'] ?? 'N/A') . "\n";
                echo "返回记录数: " . count($data['data']) . "\n\n";
                
                if (!empty($data['data'])) {
                    echo "第一条记录示例:\n";
                    print_r($data['data'][0]);
                }
            } else {
                print_r($data);
            }
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'><strong>⚠️ 响应不是有效的JSON</strong></p>";
            echo "<pre>$response</pre>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ 测试失败</strong></p>";
        echo "<pre>$response</pre>";
    }
    
    echo "<hr>";
    return $http_code == 200;
}

// 执行所有测试
$passed = 0;
$total = count($test_cases);

foreach ($test_cases as $test) {
    if (testAPI($test['url'], $test['name'], $test['description'])) {
        $passed++;
    }
}

echo "<h2>测试总结</h2>";
echo "<p>通过: $passed/$total</p>";

if ($passed == $total) {
    echo "<p style='color: green; font-weight: bold;'>🎉 所有测试通过！</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ 部分测试失败，请检查配置</p>";
}

// 显示手动测试说明
echo "<h2>手动测试说明</h2>";
echo "<ol>";
echo "<li>确保XAMPP的Apache和MySQL服务已启动</li>";
echo "<li>确保数据库已通过database_init.sql初始化</li>";
echo "<li>点击上面的链接直接在浏览器中测试API</li>";
echo "<li>或使用curl命令行工具测试</li>";
echo "</ol>";

echo "<h3>cURL测试命令示例:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px;'>";
echo "curl \"http://localhost/huilanweb/api/universities?page=1&per_page=20\"\n";
echo "curl \"http://localhost/huilanweb/api/universities?mood_type=rational_creator\"\n";
echo "curl \"http://localhost/huilanweb/api/universities?q=清华\"\n";
echo "</pre>";
?>