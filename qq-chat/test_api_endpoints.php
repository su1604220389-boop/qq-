<?php
/**
 * API端点测试脚本
 * 测试登录和注册API端点的功能
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置工作目录
chdir(dirname(__FILE__));

echo "=== API端点测试开始 ===\n\n";

// 测试登录API
function testLoginEndpoint() {
    echo "1. 测试登录API...\n";
    
    // 测试数据
    $testData = [
        'email' => 'testregister@example.com',
        'password' => 'test123'
    ];
    
    // 发送POST请求到登录API
    $response = httpRequest('api/login.php', 'POST', json_encode($testData), [
        'Content-Type: application/json'
    ]);
    
    echo "   状态码: " . $response['status_code'] . "\n";
    echo "   响应: " . $response['body'] . "\n\n";
    
    return $response;
}

// 测试注册API
function testRegisterEndpoint() {
    echo "2. 测试注册API...\n";
    
    // 生成唯一测试用户
    $uniqueId = time();
    $testData = [
        'username' => 'testuser_' . $uniqueId,
        'email' => 'test_' . $uniqueId . '@example.com',
        'password' => 'password123'
    ];
    
    // 发送POST请求到注册API
    $response = httpRequest('api/register.php', 'POST', json_encode($testData), [
        'Content-Type: application/json'
    ]);
    
    echo "   状态码: " . $response['status_code'] . "\n";
    echo "   响应: " . $response['body'] . "\n\n";
    
    return $response;
}

// HTTP请求函数
function httpRequest($url, $method = 'GET', $data = null, $headers = []) {
    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ];
    
    if ($data && $method === 'POST') {
        $options['http']['content'] = $data;
    }
    
    $context = stream_context_create($options);
    $body = file_get_contents($url, false, $context);
    
    // 获取状态码
    preg_match('/HTTP\/\d\.\d\s+(\d+)\s+/', $http_response_header[0], $matches);
    $statusCode = isset($matches[1]) ? $matches[1] : 0;
    
    return [
        'status_code' => $statusCode,
        'body' => $body,
        'headers' => $http_response_header
    ];
}

// 运行测试
testLoginEndpoint();
testRegisterEndpoint();

echo "=== API端点测试完成 ===\n";
