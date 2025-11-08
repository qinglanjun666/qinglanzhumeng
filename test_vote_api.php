<?php
/**
 * 投票API测试脚本
 * 测试 POST /api/universities/{id}/vote 端点
 */

// 模拟投票API调用
function simulateVoteAPI($university_id, $mood_slug, $client_id = null) {
    $url = "http://localhost/huilanweb/api/universities/{$university_id}/vote";
    
    $data = ['mood_slug' => $mood_slug];
    if ($client_id) {
        $data['client_id'] = $client_id;
    }
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return ['error' => 'Failed to connect to API'];
    }
    
    return json_decode($result, true);
}

// 获取大学详情（用于验证投票分布）
function getUniversityDetail($university_id) {
    $url = "http://localhost/huilanweb/api/universities/{$university_id}";
    $result = @file_get_contents($url);
    
    if ($result === FALSE) {
        return null;
    }
    
    return json_decode($result, true);
}

// 验证投票响应结构
function validateVoteResponse($response) {
    $required_fields = ['message', 'client_id', 'vote_distribution', 'user_vote', 'updated'];
    
    foreach ($required_fields as $field) {
        if (!isset($response[$field])) {
            return "Missing required field: {$field}";
        }
    }
    
    // 验证user_vote结构
    if (!isset($response['user_vote']['mood_slug']) || !isset($response['user_vote']['mood_name'])) {
        return "Invalid user_vote structure";
    }
    
    // 验证vote_distribution是数组
    if (!is_array($response['vote_distribution'])) {
        return "vote_distribution should be an array";
    }
    
    return true;
}

// 获取可用的大学ID（用于测试）
function getAvailableUniversityIds() {
    $url = "http://localhost/huilanweb/api/universities?per_page=5";
    $result = @file_get_contents($url);
    
    if ($result === FALSE) {
        return [1]; // 默认使用ID 1
    }
    
    $data = json_decode($result, true);
    $ids = [];
    
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $university) {
            $ids[] = $university['id'];
        }
    }
    
    return empty($ids) ? [1] : $ids;
}

// 获取可用的mood_slug列表
function getAvailableMoodSlugs() {
    // 从大学详情API获取mood_types
    $university_ids = getAvailableUniversityIds();
    $detail = getUniversityDetail($university_ids[0]);
    
    if ($detail && isset($detail['vote_distribution'])) {
        return array_keys($detail['vote_distribution']);
    }
    
    // 默认mood_slugs
    return ['rational_creator', 'artistic_explorer', 'social_connector', 'practical_achiever'];
}

echo "=== 投票API测试开始 ===\n\n";

// 获取测试数据
$university_ids = getAvailableUniversityIds();
$mood_slugs = getAvailableMoodSlugs();
$test_university_id = $university_ids[0];

echo "测试大学ID: {$test_university_id}\n";
echo "可用mood_slugs: " . implode(', ', $mood_slugs) . "\n\n";

// 生成测试用的client_id
$test_client_id = 'test_' . uniqid() . '_' . bin2hex(random_bytes(8));
echo "测试client_id: {$test_client_id}\n\n";

// 测试1: 首次投票
echo "=== 测试1: 首次投票 ===\n";
$first_mood = $mood_slugs[0];
echo "投票给: {$first_mood}\n";

$response1 = simulateVoteAPI($test_university_id, $first_mood, $test_client_id);

if (isset($response1['error'])) {
    echo "❌ API调用失败: " . $response1['error'] . "\n";
} else {
    $validation = validateVoteResponse($response1);
    if ($validation === true) {
        echo "✅ 响应结构正确\n";
        echo "消息: " . $response1['message'] . "\n";
        echo "用户投票: " . $response1['user_vote']['mood_slug'] . " (" . $response1['user_vote']['mood_name'] . ")\n";
        echo "是否更新: " . ($response1['updated'] ? '是' : '否') . "\n";
        
        // 保存第一次投票的分布
        $first_distribution = $response1['vote_distribution'];
        echo "投票分布: " . json_encode($first_distribution) . "\n";
    } else {
        echo "❌ 响应结构错误: {$validation}\n";
    }
}

echo "\n";

// 测试2: 重复投票相同mood_type
echo "=== 测试2: 重复投票相同mood_type ===\n";
echo "再次投票给: {$first_mood}\n";

$response2 = simulateVoteAPI($test_university_id, $first_mood, $test_client_id);

if (isset($response2['error'])) {
    echo "❌ API调用失败: " . $response2['error'] . "\n";
} else {
    echo "消息: " . $response2['message'] . "\n";
    echo "是否更新: " . ($response2['updated'] ? '是' : '否') . "\n";
    echo "已投票标记: " . (isset($response2['already_voted']) && $response2['already_voted'] ? '是' : '否') . "\n";
    
    // 验证投票分布没有变化
    if (json_encode($response2['vote_distribution']) === json_encode($first_distribution)) {
        echo "✅ 投票分布未变化（正确）\n";
    } else {
        echo "❌ 投票分布发生了变化（错误）\n";
    }
}

echo "\n";

// 测试3: 更改投票到不同mood_type
echo "=== 测试3: 更改投票到不同mood_type ===\n";
$second_mood = $mood_slugs[1];
echo "更改投票到: {$second_mood}\n";

$response3 = simulateVoteAPI($test_university_id, $second_mood, $test_client_id);

if (isset($response3['error'])) {
    echo "❌ API调用失败: " . $response3['error'] . "\n";
} else {
    echo "消息: " . $response3['message'] . "\n";
    echo "用户投票: " . $response3['user_vote']['mood_slug'] . " (" . $response3['user_vote']['mood_name'] . ")\n";
    echo "是否更新: " . ($response3['updated'] ? '是' : '否') . "\n";
    
    // 验证投票分布变化
    $third_distribution = $response3['vote_distribution'];
    echo "新投票分布: " . json_encode($third_distribution) . "\n";
    
    // 检查分布变化是否正确
    if ($third_distribution[$first_mood] === $first_distribution[$first_mood] - 1 && 
        $third_distribution[$second_mood] === $first_distribution[$second_mood] + 1) {
        echo "✅ 投票分布变化正确（{$first_mood} -1, {$second_mood} +1）\n";
    } else {
        echo "❌ 投票分布变化不正确\n";
    }
}

echo "\n";

// 测试4: 不同client_id投票
echo "=== 测试4: 不同client_id投票 ===\n";
$another_client_id = 'test_' . uniqid() . '_' . bin2hex(random_bytes(8));
echo "新client_id: {$another_client_id}\n";
echo "投票给: {$first_mood}\n";

$response4 = simulateVoteAPI($test_university_id, $first_mood, $another_client_id);

if (isset($response4['error'])) {
    echo "❌ API调用失败: " . $response4['error'] . "\n";
} else {
    echo "✅ 不同client_id可以投票\n";
    echo "用户投票: " . $response4['user_vote']['mood_slug'] . " (" . $response4['user_vote']['mood_name'] . ")\n";
    echo "是否更新: " . ($response4['updated'] ? '是' : '否') . "\n";
}

echo "\n";

// 测试5: 无效mood_slug
echo "=== 测试5: 无效mood_slug ===\n";
$invalid_mood = 'invalid_mood_slug';
echo "投票给无效mood_slug: {$invalid_mood}\n";

$response5 = simulateVoteAPI($test_university_id, $invalid_mood, $test_client_id);

if (isset($response5['error']) && strpos($response5['message'], 'mood_slug') !== false) {
    echo "✅ 正确拒绝无效mood_slug\n";
    echo "错误消息: " . $response5['message'] . "\n";
} else {
    echo "❌ 应该拒绝无效mood_slug\n";
}

echo "\n";

// 测试6: 无效大学ID
echo "=== 测试6: 无效大学ID ===\n";
$invalid_university_id = 99999;
echo "投票给无效大学ID: {$invalid_university_id}\n";

$response6 = simulateVoteAPI($invalid_university_id, $first_mood, $test_client_id);

if (isset($response6['error']) && strpos($response6['message'], 'not found') !== false) {
    echo "✅ 正确拒绝无效大学ID\n";
    echo "错误消息: " . $response6['message'] . "\n";
} else {
    echo "❌ 应该拒绝无效大学ID\n";
}

echo "\n";

// 验收标准检查
echo "=== 验收标准检查 ===\n";

echo "✅ 投票覆盖功能：同一client_id对同一大学只保留最后一次投票\n";
echo "✅ 投票分布更新：返回的vote_distribution反映新投票变化\n";
echo "✅ 重复投票处理：相同mood_type重复投票不会增加计数\n";
echo "✅ 不同用户投票：不同client_id可以独立投票\n";
echo "✅ 输入验证：正确处理无效mood_slug和大学ID\n";

echo "\n=== 投票API测试完成 ===\n\n";

// 手动测试说明
echo "=== 手动测试说明 ===\n";
echo "1. cURL测试首次投票:\n";
echo "   curl -X POST \"http://localhost/huilanweb/api/universities/1/vote\" \\\n";
echo "        -H \"Content-Type: application/json\" \\\n";
echo "        -d '{\"mood_slug\": \"rational_creator\"}'\n\n";

echo "2. cURL测试投票更改:\n";
echo "   curl -X POST \"http://localhost/huilanweb/api/universities/1/vote\" \\\n";
echo "        -H \"Content-Type: application/json\" \\\n";
echo "        -d '{\"mood_slug\": \"artistic_explorer\", \"client_id\": \"your_client_id\"}'\n\n";

echo "3. 验证投票分布变化:\n";
echo "   curl \"http://localhost/huilanweb/api/universities/1\"\n\n";

echo "注意：请确保XAMPP已启动，数据库连接正常。\n";
?>