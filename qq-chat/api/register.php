<?php
/**
 * 用户注册接口
 */

// 确保没有PHP错误输出
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('default_charset', 'UTF-8');

// 启动会话
session_start();

// 包含认证文件
require_once 'auth.php';

// 创建认证实例
$auth = new Auth();

// 设置响应头，确保UTF-8编码
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
// 设置CORS头，允许前端访问
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
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
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
    exit;
}

$username = trim($data['username']);
$email = trim($data['email']);
// 处理反斜杠转义（兼容可能的魔术引号设置）
$password = stripslashes($data['password']);

// 验证用户名
if (strlen($username) < 2 || strlen($username) > 20) {
    echo json_encode(['status' => 'error', 'message' => '用户名长度应在2-20个字符之间']);
    exit;
}

// 验证邮箱
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => '邮箱格式不正确']);
    exit;
}

// 验证密码
if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => '密码长度不能少于6位']);
    exit;
}

// 获取用户IP
$ip = $_SERVER['REMOTE_ADDR'];

// 注册用户
$result = $auth->register($username, $email, $password, $ip);

// 返回结果，确保没有BOM或其他额外字符
if (ob_get_level() > 0) {
    ob_clean(); // 清理输出缓冲区
}
echo json_encode($result, JSON_UNESCAPED_UNICODE);

// 确保没有其他输出
exit;