<?php
/**
 * 用户登出接口
 */

// 启动会话
session_start();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 添加CORS头信息
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 引入配置文件
require_once 'config.php';
require_once 'auth.php';

// 创建认证实例
$auth = new Auth();

// 执行登出
$auth->logout();

echo json_encode(['status' => 'success', 'message' => '登出成功']);
?>\