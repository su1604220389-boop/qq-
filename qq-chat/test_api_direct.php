<?php
/**
 * 直接API测试脚本
 * 用于在服务器上直接测试登录和注册API的功能
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 测试函数
function testLoginAPI() {
    echo "=== 测试登录API ===\n";
    
    // 测试数据
    $testData = [
        'email' => 'test@example.com',
        'password' => 'password123'
    ];
    
    // 模拟POST请求
    $result = callApi('api/login.php', 'POST', $testData);
    
    echo "登录API响应: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    return $result;
}

function testRegisterAPI() {
    echo "=== 测试注册API ===\n";
    
    // 生成唯一测试用户
    $uniqueId = time();
    $testData = [
        'username' => 'testuser_' . $uniqueId,
        'email' => 'test_' . $uniqueId . '@example.com',
        'password' => 'password123'
    ];
    
    // 模拟POST请求
    $result = callApi('api/register.php', 'POST', $testData);
    
    echo "注册API响应: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    return $result;
}

// API调用函数
function callApi($url, $method = 'POST', $data = []) {
    // 创建上下文选项
    $contextOptions = [
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/json\r\n',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    
    // 创建上下文
    $context = stream_context_create($contextOptions);
    
    // 执行请求
    $response = file_get_contents($url, false, $context);
    
    // 获取响应头
    $responseHeaders = $http_response_header;
    
    // 解析JSON响应
    $jsonResponse = json_decode($response, true);
    
    return [
        'status' => substr($responseHeaders[0], 9, 3),
        'headers' => $responseHeaders,
        'body' => $jsonResponse,
        'raw_body' => $response
    ];
}

// 运行测试
echo "API直接测试开始...\n\n";

testLoginAPI();
testRegisterAPI();

echo "API直接测试完成！\n";
