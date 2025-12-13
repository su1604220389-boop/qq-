<?php
/**
 * 数据库初始化脚本
 * 运行此脚本创建所需的数据库表
 */

// 包含配置文件
require_once 'config.php';

try {
    // 创建数据库连接
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // 创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    
    // 创建用户表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT 'https://p3-flow-imagex-sign.byteimg.com/tos-cn-i-a9rns2rl98/rc/pc/super_tool/c5c1cefd210649449e5654488b536610~tplv-a9rns2rl98-image.image?rcl=20251211013204EB34B3447BDC28D5F481&rk3s=8e244e95&rrcfp=f06b921b&x-expires=1767980014&x-signature=vtVRI6%2BteRoXMgyzR8opnDwyN2A%3D',
            status TINYINT DEFAULT 0 COMMENT '0:离线, 1:在线, 2:忙碌',
            last_active DATETIME,
            created_at DATETIME NOT NULL,
            register_ip VARCHAR(45) NOT NULL,
            INDEX idx_email (email),
            INDEX idx_status (status)
        )
    ");
    
    // 创建好友表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS friends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            friend_id INT NOT NULL,
            status TINYINT DEFAULT 0 COMMENT '0:请求中, 1:已接受',
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY (user_id, friend_id),
            INDEX idx_user_id (user_id),
            INDEX idx_friend_id (friend_id)
        )
    ");
    
    // 创建消息表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT NOT NULL,
            is_read TINYINT DEFAULT 0,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_sender_id (sender_id),
            INDEX idx_receiver_id (receiver_id),
            INDEX idx_is_read (is_read)
        )
    ");
    
    echo "数据库初始化成功！";
    
} catch (PDOException $e) {
    echo "数据库初始化失败: " . $e->getMessage();
}