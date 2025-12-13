<?php
/**
 * 全面的API测试脚本
 * 测试Auth类的核心功能
 */

// 包含必要的文件
require_once 'api/config.php';
require_once 'api/db.php';
require_once 'api/auth.php';

// 测试登录功能
function testAuthLogin($email, $password) {
    echo "=== 测试Auth类登录功能 ===\n";
    
    // 创建Auth实例
    $auth = new Auth();
    
    // 调用登录方法
    $result = $auth->login($email, $password);
    
    echo "登录结果: " . json_encode($result) . "\n";
    
    if (isset($result['status']) && $result['status'] === 'success') {
        echo "Auth类登录测试通过！\n\n";
        return $result['user'];
    } else {
        echo "Auth类登录测试失败！\n\n";
        return false;
    }
}

// 测试获取用户信息
function testAuthGetUser($userId) {
    echo "=== 测试Auth类获取用户信息 ===\n";
    
    // 创建Auth实例
    $auth = new Auth();
    
    // 调用获取用户方法
    $user = $auth->getUserById($userId);
    
    echo "用户信息: " . json_encode($user) . "\n";
    
    if ($user) {
        echo "Auth类获取用户信息测试通过！\n\n";
        return true;
    } else {
        echo "Auth类获取用户信息测试失败！\n\n";
        return false;
    }
}

// 测试检查用户是否已登录
function testAuthIsLoggedIn($userId) {
    echo "=== 测试Auth类检查登录状态 ===\n";
    
    // 创建Auth实例
    $auth = new Auth();
    
    // 先登录
    $auth->login('testregister@example.com', 'password123');
    
    // 调用检查登录状态方法
    $isLoggedIn = $auth->isLoggedIn();
    
    echo "登录状态: " . ($isLoggedIn ? "已登录" : "未登录") . "\n";
    
    if ($isLoggedIn) {
        echo "Auth类检查登录状态测试通过！\n\n";
        return true;
    } else {
        echo "Auth类检查登录状态测试失败！\n\n";
        return false;
    }
}

// 主测试函数
function runTests() {
    // 测试用户信息
    $email = 'testregister@example.com';
    $password = 'password123';
    
    // 登录测试
    $user = testAuthLogin($email, $password);
    
    if ($user) {
        // 获取用户信息测试
        testAuthGetUser($user['id']);
        
        // 检查登录状态测试
        testAuthIsLoggedIn($user['id']);
    }
    
    echo "=== 所有Auth类测试完成 ===\n";
}

// 运行测试
runTests();
