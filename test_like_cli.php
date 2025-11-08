<?php
/**
 * 简化的命令行点赞API测试脚本
 */

// 引入必要的文件
include_once 'config/database.php';
include_once 'models/University.php';

echo "🚀 开始点赞API功能测试\n";
echo "==========================================\n";

try {
    // 获取数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "❌ 数据库连接失败\n";
        exit(1);
    }
    
    // 创建大学对象
    $university = new University($db);
    
    // 获取测试用的大学
    $stmt = $db->prepare("SELECT id, name FROM universities LIMIT 1");
    $stmt->execute();
    $test_university = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test_university) {
        echo "❌ 无法获取测试大学数据\n";
        exit(1);
    }
    
    $university_id = $test_university['id'];
    $university_name = $test_university['name'];
    
    echo "📋 测试大学: {$university_name} (ID: {$university_id})\n";
    
    // 生成测试用的客户端ID
    $client_id = $university->generateClientId();
    echo "🆔 生成测试客户端ID: {$client_id}\n";
    
    // 获取初始点赞数
    $initial_like_count = $university->getLikeCount($university_id);
    echo "📊 初始点赞数: {$initial_like_count}\n";
    
    echo "\n=== 测试1: 首次点赞 ===\n";
    
    // 检查是否已经点赞过
    $already_liked = $university->hasUserLiked($university_id, $client_id);
    echo "🔍 检查是否已点赞: " . ($already_liked ? '是' : '否') . "\n";
    
    if (!$already_liked) {
        // 添加点赞
        $like_result = $university->addLike($university_id, $client_id, '127.0.0.1');
        
        if ($like_result) {
            echo "✅ 点赞添加成功\n";
            
            // 获取更新后的点赞数
            $new_like_count = $university->getLikeCount($university_id);
            echo "📊 更新后点赞数: {$new_like_count}\n";
            
            if ($new_like_count > $initial_like_count) {
                echo "✅ 点赞数正确增加\n";
            } else {
                echo "❌ 点赞数未增加\n";
            }
        } else {
            echo "❌ 点赞添加失败\n";
        }
    } else {
        echo "ℹ️  该客户端已经点赞过\n";
    }
    
    echo "\n=== 测试2: 重复点赞 ===\n";
    
    // 再次尝试点赞
    $duplicate_like_result = $university->addLike($university_id, $client_id, '127.0.0.1');
    
    if (!$duplicate_like_result) {
        echo "✅ 正确拒绝重复点赞\n";
        
        // 验证点赞数没有再次增加
        $final_like_count = $university->getLikeCount($university_id);
        echo "📊 最终点赞数: {$final_like_count}\n";
        
        if ($final_like_count == $new_like_count) {
            echo "✅ 点赞数未重复增加\n";
        } else {
            echo "❌ 点赞数异常增加\n";
        }
    } else {
        echo "❌ 错误允许了重复点赞\n";
    }
    
    echo "\n=== 测试3: 不同客户端点赞 ===\n";
    
    // 生成另一个客户端ID
    $client_id_2 = $university->generateClientId();
    echo "🆔 生成第二个客户端ID: {$client_id_2}\n";
    
    $second_client_like = $university->addLike($university_id, $client_id_2, '127.0.0.2');
    
    if ($second_client_like) {
        echo "✅ 不同客户端可以点赞\n";
        
        $final_like_count_2 = $university->getLikeCount($university_id);
        echo "📊 第二次点赞后总数: {$final_like_count_2}\n";
        
        if ($final_like_count_2 > $final_like_count) {
            echo "✅ 不同客户端点赞正确增加计数\n";
        } else {
            echo "❌ 不同客户端点赞未增加计数\n";
        }
    } else {
        echo "❌ 不同客户端点赞失败\n";
    }
    
    echo "\n=== 测试4: 客户端ID生成 ===\n";
    
    // 测试客户端ID生成
    $generated_ids = array();
    for ($i = 0; $i < 5; $i++) {
        $generated_ids[] = $university->generateClientId();
    }
    
    echo "🆔 生成的客户端ID样例:\n";
    foreach ($generated_ids as $index => $id) {
        echo "  " . ($index + 1) . ". {$id}\n";
    }
    
    // 检查ID唯一性
    $unique_ids = array_unique($generated_ids);
    if (count($unique_ids) == count($generated_ids)) {
        echo "✅ 生成的客户端ID都是唯一的\n";
    } else {
        echo "❌ 生成的客户端ID有重复\n";
    }
    
    // 检查ID格式
    $valid_format = true;
    foreach ($generated_ids as $id) {
        if (!preg_match('/^hj_[a-f0-9]+_[a-f0-9]{16}$/', $id)) {
            $valid_format = false;
            break;
        }
    }
    
    if ($valid_format) {
        echo "✅ 客户端ID格式正确\n";
    } else {
        echo "❌ 客户端ID格式不正确\n";
    }
    
    echo "\n=== 验收标准检查 ===\n";
    
    $acceptance_passed = 0;
    $acceptance_total = 3;
    
    // 验收标准1: 首次POST返回like_count增加
    if ($new_like_count > $initial_like_count) {
        echo "✅ 验收标准1: 首次点赞增加like_count\n";
        $acceptance_passed++;
    } else {
        echo "❌ 验收标准1: 首次点赞未增加like_count\n";
    }
    
    // 验收标准2: 重复点赞不增加like_count
    if (!$duplicate_like_result) {
        echo "✅ 验收标准2: 重复点赞正确拒绝\n";
        $acceptance_passed++;
    } else {
        echo "❌ 验收标准2: 重复点赞未被拒绝\n";
    }
    
    // 验收标准3: 返回client_id
    if (!empty($client_id) && preg_match('/^hj_/', $client_id)) {
        echo "✅ 验收标准3: 客户端ID生成正确\n";
        $acceptance_passed++;
    } else {
        echo "❌ 验收标准3: 客户端ID生成错误\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 测试总结\n";
    echo "验收标准通过: {$acceptance_passed}/{$acceptance_total}\n";
    echo "成功率: " . round(($acceptance_passed / $acceptance_total) * 100, 2) . "%\n";
    
    if ($acceptance_passed === $acceptance_total) {
        echo "🎉 所有验收标准通过！T004点赞功能实现完成\n";
    } else {
        echo "⚠️  部分验收标准未通过，请检查实现\n";
    }
    
    echo "\n📝 API使用示例：\n";
    echo "POST http://localhost/huilanweb/api/universities/{$university_id}/like\n";
    echo "Content-Type: application/json\n";
    echo "Body: {} 或 {\"client_id\": \"your_client_id\"}\n";
    
    echo "\n📝 预期响应：\n";
    echo "首次点赞: {\"message\":\"Like added successfully\",\"like_count\":{$final_like_count_2},\"client_id\":\"hj_...\",\"already_liked\":false}\n";
    echo "重复点赞: {\"message\":\"already liked\",\"like_count\":{$final_like_count_2},\"client_id\":\"hj_...\",\"already_liked\":true}\n";
    
} catch (Exception $e) {
    echo "❌ 测试过程中发生错误: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ 点赞API功能测试完成\n";
?>