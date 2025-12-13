<?php
// 测试密码验证
$hash = '$2y$12$cT8QlUZ45OVWZKVhnH/lbO3b7S.usth7xHi4.tSH50fgWReKL06Vq';
$password = 'password123';

echo "密码哈希: $hash\n";
echo "测试密码: $password\n";

echo "验证结果: " . (password_verify($password, $hash) ? '成功' : '失败') . "\n";

// 测试其他密码
$wrongPassword = 'wrongpassword';
echo "错误密码验证结果: " . (password_verify($wrongPassword, $hash) ? '成功' : '失败') . "\n";
