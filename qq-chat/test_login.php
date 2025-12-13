<?php
// 测试登录功能
// 直接使用文件存储模式
require_once 'api/file_storage.php';
$storagePath = '../storage/';
$db = new FileStorage($storagePath);

// 测试邮箱登录
function testEmailLogin($email, $password) {
    global $db;
    
    // 获取用户信息
    $user = $db->getUserByEmail($email);
    if (!$user) {
        return [
            'success' => false,
            'message' => '用户不存在'
        ];
    }
    
    // 验证密码
    if (password_verify($password, $user['password'])) {
        return [
            'success' => true,
            'message' => '登录成功',
            'user' => $user
        ];
    } else {
        return [
            'success' => false,
            'message' => '密码错误'
        ];
    }
}

// 测试用户名登录
function testUsernameLogin($username, $password) {
    global $db;
    
    // 获取用户信息
    $user = $db->getUserByUsername($username);
    if (!$user) {
        return [
            'success' => false,
            'message' => '用户不存在'
        ];
    }
    
    // 验证密码
    if (password_verify($password, $user['password'])) {
        return [
            'success' => true,
            'message' => '登录成功',
            'user' => $user
        ];
    } else {
        return [
            'success' => false,
            'message' => '密码错误'
        ];
    }
}

// 测试用例
echo "测试登录功能\n";
echo "================\n";

// 测试邮箱登录
echo "\n1. 测试邮箱登录:\n";
$email = '1604220389@qq.com';
$password = '123456';
$result = testEmailLogin($email, $password);
echo "邮箱: $email\n";
echo "密码: $password\n";
echo "结果: " . ($result['success'] ? '成功' : '失败') . " - " . $result['message'] . "\n";

// 测试用户名登录
echo "\n2. 测试用户名登录:\n";
$username = 'test123'; // 假设users.json中有这个用户名
$password = '123456';
$result = testUsernameLogin($username, $password);
echo "用户名: $username\n";
echo "密码: $password\n";
echo "结果: " . ($result['success'] ? '成功' : '失败') . " - " . $result['message'] . "\n";

// 测试注册新用户并登录
echo "\n3. 测试注册新用户并登录:\n";
$newUser = [
    'username' => 'testuser' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => '123456',
    'register_ip' => '127.0.0.1'
];

// 检查用户名是否已存在
if ($db->getUserByUsername($newUser['username'])) {
    echo "用户名已存在\n";
} elseif ($db->getUserByEmail($newUser['email'])) {
    echo "邮箱已存在\n";
} else {
    // 注册用户
    $hashedPassword = password_hash($newUser['password'], PASSWORD_DEFAULT);
    $newUser['password'] = $hashedPassword;
    $newUser['created_at'] = date('Y-m-d H:i:s');
    $newUser['status'] = 1;
    $newUser['avatar'] = 'default.png';
    $newUser['last_active'] = date('Y-m-d H:i:s');
    
    if ($db->addUser($newUser)) {
        echo "注册成功\n";
        
        // 测试登录
        $loginResult = testEmailLogin($newUser['email'], $newUser['password']);
        echo "登录结果: " . ($loginResult['success'] ? '成功' : '失败') . " - " . $loginResult['message'] . "\n";
    } else {
        echo "注册失败\n";
    }
}
?>