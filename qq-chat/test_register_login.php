<?php
/**
 * 模拟完整的注册和登录流程测试脚本
 */

// 包含所需文件
require_once 'api/config.php';
require_once 'api/db.php';
require_once 'api/file_storage.php';
require_once 'api/auth.php';

// 创建认证实例
session_start();
$auth = new Auth();

// 测试注册
$username = 'testuser_register';
$email = 'testregister@example.com';
$password = 'test123';
$ip = '127.0.0.1';

echo "=== 测试注册流程 ===\n";
$registerResult = $auth->register($username, $email, $password, $ip);
echo "注册结果: " . json_encode($registerResult, JSON_UNESCAPED_UNICODE) . "\n";

// 清除会话
session_destroy();
session_start();

// 测试登录
echo "\n=== 测试登录流程 ===\n";
$loginResult = $auth->login($email, $password);
echo "登录结果: " . json_encode($loginResult, JSON_UNESCAPED_UNICODE) . "\n";

// 测试使用用户名登录
echo "\n=== 测试使用用户名登录 ===\n";
$usernameLoginResult = $auth->login($username, $password);
echo "用户名登录结果: " . json_encode($usernameLoginResult, JSON_UNESCAPED_UNICODE) . "\n";
