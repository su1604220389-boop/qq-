<?php
/**
 * 配置文件
 */

// 存储配置
define('USE_FILE_STORAGE', true); // 使用文件存储
define('STORAGE_PATH', dirname(__DIR__) . '/storage/');

// 应用配置
define('APP_NAME', 'QQ聊天');
define('APP_URL', 'http://xuliehaochaxu.cn:654');
define('APP_SECRET', 'your_secret_key_here'); // 用于生成令牌和加密

// 数据库配置（仅在USE_FILE_STORAGE为false时使用）
define('DB_HOST', 'localhost');
define('DB_NAME', 'qq_chat');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// 邮箱配置（可选）
define('EMAIL_HOST', 'smtp.example.com');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'noreply@example.com');
define('EMAIL_PASSWORD', 'your_email_password');
define('EMAIL_FROM', 'noreply@example.com');
define('EMAIL_FROM_NAME', 'QQ聊天');

// 其他配置
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', '../assets/images/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);