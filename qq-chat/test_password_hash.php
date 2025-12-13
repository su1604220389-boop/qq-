<?php
/**
 * 密码哈希生成和验证测试脚本
 */

// 测试密码
$testPassword = 'test123';

// 生成密码哈希
$hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
echo "生成的密码哈希: {$hashedPassword}\n";

// 验证密码哈希
$verifyResult = password_verify($testPassword, $hashedPassword);
echo "密码验证结果: " . ($verifyResult ? '成功' : '失败') . "\n";

// 测试users.json中的一个哈希值
$userPasswordHash = '$2y$12$jq0VDjFBz1svDgmsr0A9zeadmFjnWpYw9/DFARFdd.CJrMnVhdqUG'; // 来自users.json中的用户1604220389@qq.com
echo "\n验证用户密码哈希: " . ($userPasswordHash) . "\n";

$userVerifyResult = password_verify($testPassword, $userPasswordHash);
echo "用户密码验证结果: " . ($userVerifyResult ? '成功' : '失败') . "\n";

// 测试另一个密码
$anotherPassword = '123456';
$anotherVerifyResult = password_verify($anotherPassword, $userPasswordHash);
echo "使用密码'123456'验证结果: " . ($anotherVerifyResult ? '成功' : '失败') . "\n";
