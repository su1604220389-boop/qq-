<?php
// 创建新用户并测试登录功能
require_once 'api/db.php';
require_once 'api/auth.php';

// 使用db.php中定义的$storage变量
// 初始化auth类
$auth = new Auth($storage);

echo "创建新用户并测试登录\n";
echo "======================\n";

// 生成随机用户名和邮箱
$username = 'testuser_' . time();
$email = 'test_' . time() . '@example.com';
$password = '123456';
$ip = '127.0.0.1';

echo "新用户信息:\n";
echo "用户名: $username\n";
echo "邮箱: $email\n";
echo "密码: $password\n";

// 注册新用户
echo "\n1. 注册新用户:\n";
$registerResult = $auth->register($username, $email, $password, $ip);
echo "注册结果: " . ($registerResult['status'] == 'success' ? '成功' : '失败') . "\n";
if ($registerResult['status'] == 'error') {
    echo "错误信息: " . $registerResult['message'] . "\n";
    exit;
}

// 登录新用户
echo "\n2. 测试登录新用户:\n";
$loginResult = $auth->login($email, $password);
echo "登录结果: " . ($loginResult['status'] == 'success' ? '成功' : '失败') . "\n";
if ($loginResult['status'] == 'success') {
    echo "登录成功！用户信息:\n";
    print_r($loginResult['user']);
} else {
    echo "错误信息: " . $loginResult['message'] . "\n";
}

// 查看新用户的数据
echo "\n3. 查看新用户数据:\n";
$usersJson = file_get_contents('storage/users.json');
$users = json_decode($usersJson, true);
$newUser = null;
foreach ($users as $user) {
    if ($user['email'] == $email) {
        $newUser = $user;
        break;
    }
}

if ($newUser) {
    echo "用户ID: " . $newUser['id'] . "\n";
    echo "用户名: " . $newUser['username'] . "\n";
    echo "邮箱: " . $newUser['email'] . "\n";
    echo "密码哈希: " . $newUser['password'] . "\n";
    echo "哈希长度: " . strlen($newUser['password']) . "\n";
    
    // 直接验证哈希
    echo "直接验证哈希: " . (password_verify($password, $newUser['password']) ? '成功' : '失败') . "\n";
} else {
    echo "未找到新用户数据\n";
}
?>