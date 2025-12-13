<?php
/**
 * FileStorage直接测试脚本
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== FileStorage直接测试 ===\n";

// 直接创建FileStorage实例
try {
    require_once 'api/file_storage.php';
    
    // 测试FileStorage构造函数
    echo "尝试创建FileStorage实例...\n";
    $storage = new FileStorage();
    echo "FileStorage实例创建成功！\n";
    
    // 测试基本功能
    echo "\n=== FileStorage功能测试 ===\n";
    
    // 获取存储路径
    $reflection = new ReflectionClass($storage);
    $property = $reflection->getProperty('storagePath');
    $property->setAccessible(true);
    $storagePath = $property->getValue($storage);
    echo "存储路径: " . $storagePath . "\n";
    
    // 测试获取用户列表
    if (method_exists($storage, 'getAllUsers')) {
        $users = $storage->getAllUsers();
        echo "获取所有用户: 成功，共 " . count($users) . " 个用户\n";
        
        // 显示前2个用户的基本信息
        if (count($users) > 0) {
            echo "\n部分用户信息: \n";
            for ($i = 0; $i < min(2, count($users)); $i++) {
                $user = $users[$i];
                echo "用户 " . ($i + 1) . ": ID=" . $user['id'] . ", 用户名=" . $user['username'] . ", 邮箱=" . $user['email'] . "\n";
            }
        }
    } else {
        echo "getAllUsers方法不存在\n";
    }
    
} catch (Exception $e) {
    echo "创建FileStorage实例失败: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . " 第 " . $e->getLine() . " 行\n";
}

// 测试Auth类
echo "\n=== Auth类测试 ===\n";
try {
    require_once 'api/db.php'; // 这应该会创建$storage实例
    require_once 'api/auth.php';
    
    global $storage;
    echo "db.php中创建的storage实例: " . (isset($storage) ? '是' : '否') . "\n";
    
    $auth = new Auth();
    echo "Auth类实例化成功！\n";
    
    // 测试登录方法
    echo "\n测试登录功能...\n";
    
    // 使用已知的测试用户
    $testEmail = 'test@example.com';
    $testPassword = 'password123';
    
    $loginResult = $auth->login($testEmail, $testPassword);
    echo "登录测试结果: " . json_encode($loginResult) . "\n";
    
} catch (Exception $e) {
    echo "Auth类测试失败: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . " 第 " . $e->getLine() . " 行\n";
    echo "错误追踪: " . $e->getTraceAsString() . "\n";
}

echo "\n=== 测试完成 ===\n";