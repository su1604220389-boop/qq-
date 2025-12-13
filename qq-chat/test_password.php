<?php
/**
 * 密码验证测试脚本
 */

// 包含文件存储类
require_once 'api/file_storage.php';

// 创建文件存储实例
$storage = new FileStorage();

// 获取所有用户
$users = $storage->getUsers();

// 测试密码验证
foreach ($users as $user) {
    echo "用户: {$user['email']}\n";
    echo "密码哈希(带斜杠): {$user['password']}\n";
    
    // 移除反斜杠转义
    $decodedPassword = stripslashes($user['password']);
    echo "密码哈希(无斜杠): {$decodedPassword}\n";
    
    // 测试原始密码哈希
    $originalVerify = password_verify('test123', $user['password']);
    echo "原始密码验证结果: " . ($originalVerify ? '成功' : '失败') . "\n";
    
    // 测试解码后的密码哈希
    $decodedVerify = password_verify('test123', $decodedPassword);
    echo "解码后密码验证结果: " . ($decodedVerify ? '成功' : '失败') . "\n";
    
    echo "--------------------------------------------------\n";
}
