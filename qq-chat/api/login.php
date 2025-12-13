<?php
/**
 * 用户登录接口
 */

// 启动会话
session_start();

// 包含认证文件
require_once 'auth.php';

// 创建认证实例
$auth = new Auth();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// 设置CORS头
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '请求方法错误']);
    exit;
}

// 获取请求数据
$data = json_decode(file_get_contents('php://input'), true);

// 验证数据
if ((!isset($data['email']) && !isset($data['username'])) || !isset($data['password'])) {
    echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
    exit;
}

// 支持邮箱或用户名登录
$loginIdentifier = isset($data['email']) ? trim($data['email']) : trim($data['username']);
// 处理反斜杠转义（兼容可能的魔术引号设置）
$password = stripslashes($data['password']);

// 只有当登录标识符看起来像邮箱时，才验证其格式
if (strpos($loginIdentifier, '@') !== false && !filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => '邮箱格式不正确']);
    exit;
}

// 登录用户
$result = $auth->login($loginIdentifier, $password);

// 返回结果
echo json_encode($result, JSON_UNESCAPED_UNICODE);