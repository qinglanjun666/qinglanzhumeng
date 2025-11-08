<?php
/**
 * AI配置：Anthropic Claude集成
 * 从环境变量 ANTROPHIC_API_KEY 或 ANTHROPIC_API_KEY 读取密钥
 */
return [
    'provider' => 'anthropic',
    'anthropic_api_key' => getenv('ANTHROPIC_API_KEY') ?: getenv('ANTROPHIC_API_KEY') ?: '',
    'anthropic_endpoint' => 'https://api.anthropic.com/v1/messages',
    'anthropic_model' => 'claude-3-5-sonnet-20241022',
    'anthropic_version' => '2023-06-01',
    'timeout' => 15,
    // 没有配置密钥时启用dry_run，返回基于本地数据的示例答案
    'dry_run' => empty(getenv('ANTHROPIC_API_KEY')) && empty(getenv('ANTROPHIC_API_KEY')),
];
?>