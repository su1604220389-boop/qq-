<?php
/**
 * 完整API测试脚本
 * 正确包含所有必要的文件和初始化所有必要的变量
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 确保工作目录正确
chdir(dirname(__FILE__));

echo "=== 完整API测试开始 ===\n\n";

// 包含必要的配置和文件
echo "1. 包含配置文件...\n";
require_once 'config.php';
echo "   ✓ config.php 包含成功\n\n";

echo "2. 包含数据库/存储文件...\n";
require_once 'db.php';
echo "   ✓ db.php 包含成功\n\n";

// 检查存储实例
if (USE_FILE_STORAGE) {
    if (isset($GLOBALS['storage']) && $GLOBALS['storage'] instanceof FileStorage) {
        echo "3. 文件存储实例检查...\n";
        echo "   ✓ FileStorage 实例已成功创建\n\n";
    } else {
        echo "3. 文件存储实例检查...\n";
        echo "   ✗ ERROR: FileStorage 实例不存在或类型错误\n\n";
        exit(1);
    }
} else {
    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof Database) {
        echo "3. 数据库实例检查...\n";
        echo "   ✓ Database 实例已成功创建\n\n";
    } else {
        echo "3. 数据库实例检查...\n";
        echo "   ✗ ERROR: Database 实例不存在或类型错误\n\n";
        exit(1);
    }
}

// 包含Auth类
echo "4. 包含Auth类...\n";
require_once 'api/auth.php';
echo "   ✓ auth.php 包含成功\n\n";

// 测试Auth类初始化
echo "5. 初始化Auth类...\n";
try {
    $auth = new Auth();
    echo "   ✓ Auth 类初始化成功\n\n";
} catch (Exception $e) {
    echo "   ✗ ERROR: Auth 类初始化失败: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 测试用户列表获取
echo "6. 测试用户列表获取...\n";
try {
    if (USE_FILE_STORAGE) {
        $users = $GLOBALS['storage']->getAllUsers();
        echo "   ✓ 成功获取所有用户: " . (is_array($users) ? count($users) : 0) . " 个用户\n\n";
    } else {
        // 数据库模式下的用户列表获取
        $users = $GLOBALS['db']->getAllUsers();
        echo "   ✓ 成功获取所有用户: " . (is_array($users) ? count($users) : 0) . " 个用户\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ ERROR: 获取用户列表失败: " . $e->getMessage() . "\n\n";
}

// 测试注册新用户
echo "7. 测试注册新用户...\n";
$uniqueId = time();
$testUser = [
    'username' => 'testuser_' . $uniqueId,
    'email' => 'test_' . $uniqueId . '@example.com',
    'password' => 'password123'
];

try {
    $registerResult = $auth->register(
        $testUser['username'],
        $testUser['email'],
        $testUser['password'],
        '127.0.0.1' // 模拟IP地址
    );
    
    echo "   注册结果: " . json_encode($registerResult, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "   ✗ ERROR: 用户注册失败: " . $e->getMessage() . "\n\n";
}

// 测试登录
echo "8. 测试登录功能...\n";
try {
    $loginResult = $auth->login($testUser['email'], $testUser['password']);
    
    echo "   登录结果: " . json_encode($loginResult, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "   ✗ ERROR: 用户登录失败: " . $e->getMessage() . "\n\n";
}

echo "=== 完整API测试完成 ===\n";
