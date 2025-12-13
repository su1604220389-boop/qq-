<?php
// 测试Auth类的登录功能
require_once 'api/config.php';
require_once 'api/auth.php';

// 启动会话
session_start();

// 实例化Auth类
$auth = new Auth();

echo "=== 测试Auth类登录功能 ===\n";

// 测试登录（使用正确的邮箱和密码）
$email = 'testregister@example.com';
$password = 'test123';

$loginResult = $auth->login($email, $password);
echo "登录结果: " . json_encode($loginResult, JSON_UNESCAPED_UNICODE) . "\n";

// 检查登录状态
if (isset($_SESSION['user_id'])) {
    echo "会话已设置，用户ID: {$_SESSION['user_id']}\n";
    echo "用户名: {$_SESSION['username']}\n";
} else {
    echo "会话未设置\n";
}

echo "=== 测试Auth类通过用户名登录功能 ===\n";

// 测试通过用户名登录
$username = 'testuser_register';
$loginResultUsername = $auth->login($username, $password);
echo "用户名登录结果: " . json_encode($loginResultUsername, JSON_UNESCAPED_UNICODE) . "\n";

echo "=== 测试完成 ===\n";
