<?php
/**
 * API核心功能测试脚本
 * 直接调用API的核心功能，模拟请求处理流程
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 模拟HTTP请求环境
function setupHttpRequest($method, $data = []) {
    // 保存原始请求数据
    $originalServer = $_SERVER;
    $originalInput = file_get_contents('php://input');
    
    // 设置模拟请求数据
    $_SERVER['REQUEST_METHOD'] = $method;
    
    if ($method === 'POST' && !empty($data)) {
        // 创建临时文件来模拟php://input
        $inputData = json_encode($data);
        $tempFile = tempnam(sys_get_temp_dir(), 'php_input_');
        file_put_contents($tempFile, $inputData);
        
        // 重定向php://input到临时文件
        $inputStream = fopen($tempFile, 'r');
        stream_context_set_option(
            stream_context_get_default(),
            'http',
            'content',
            $inputData
        );
        
        return [
            'original_server' => $originalServer,
            'original_input' => $originalInput,
            'temp_file' => $tempFile,
            'input_stream' => $inputStream
        ];
    }
    
    return [
        'original_server' => $originalServer,
        'original_input' => $originalInput
    ];
}

// 恢复原始请求环境
function restoreHttpRequest($backup) {
    // 恢复原始服务器变量
    $_SERVER = $backup['original_server'];
    
    // 关闭并删除临时文件
    if (isset($backup['temp_file']) && file_exists($backup['temp_file'])) {
        if (isset($backup['input_stream'])) {
            fclose($backup['input_stream']);
        }
        unlink($backup['temp_file']);
    }
}

// 测试登录功能
function testLoginFunction() {
    echo "=== 测试登录功能 ===\n";
    
    // 测试数据
    $testData = [
        'email' => 'test@example.com',
        'password' => 'password123'
    ];
    
    try {
        // 设置模拟请求
        $backup = setupHttpRequest('POST', $testData);
        
        // 直接调用登录逻辑
        require_once 'api/auth.php';
        
        $auth = new Auth();
        $result = $auth->login($testData['email'], $testData['password']);
        
        // 恢复原始环境
        restoreHttpRequest($backup);
        
        echo "登录功能响应: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        
        return $result;
    } catch (Exception $e) {
        echo "登录功能测试失败: " . $e->getMessage() . "\n\n";
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// 测试注册功能
function testRegisterFunction() {
    echo "=== 测试注册功能 ===\n";
    
    // 生成唯一测试用户
    $uniqueId = time();
    $testData = [
        'username' => 'testuser_' . $uniqueId,
        'email' => 'test_' . $uniqueId . '@example.com',
        'password' => 'password123'
    ];
    
    try {
        // 设置模拟请求
        $backup = setupHttpRequest('POST', $testData);
        
        // 直接调用注册逻辑
        require_once 'api/auth.php';
        
        $auth = new Auth();
        $result = $auth->register(
            $testData['username'],
            $testData['email'],
            $testData['password'],
            '127.0.0.1' // 模拟IP地址
        );
        
        // 恢复原始环境
        restoreHttpRequest($backup);
        
        echo "注册功能响应: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        
        return $result;
    } catch (Exception $e) {
        echo "注册功能测试失败: " . $e->getMessage() . "\n\n";
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// 运行测试
echo "API核心功能测试开始...\n\n";

// 测试登录功能
testLoginFunction();

// 测试注册功能
testRegisterFunction();

echo "API核心功能测试完成！\n";
