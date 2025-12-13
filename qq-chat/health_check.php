<?php
/**
 * 服务器健康检查脚本
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit;
}

// 收集服务器信息
$info = array(
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'working_dir' => getcwd(),
    'magic_quotes' => array(
        'gpc' => '已废弃 (PHP 5.4+)',
        'runtime' => '已废弃 (PHP 5.4+)'
    ),
    'file_uploads' => ini_get('file_uploads'),
    'max_upload_size' => ini_get('upload_max_filesize'),
    'memory_limit' => ini_get('memory_limit'),
    'error_reporting' => error_reporting(),
    'display_errors' => ini_get('display_errors')
);

// 检查关键文件和目录
$info['files'] = array(
    'api/auth.php' => file_exists('api/auth.php'),
    'api/db.php' => file_exists('api/db.php'),
    'api/file_storage.php' => file_exists('api/file_storage.php'),
    'storage/' => is_dir('storage/'),
    'storage/users.json' => file_exists('storage/users.json')
);

// 检查存储目录权限
if (is_dir('storage/')) {
    $info['storage_permissions'] = substr(sprintf('%o', fileperms('storage/')), -4);
    $info['storage_writable'] = is_writable('storage/');
}

// 尝试加载基本类
try {
    require_once 'api/db.php';
    $info['db_loaded'] = true;
    $info['storage_available'] = isset($storage);
    
    require_once 'api/auth.php';
    $info['auth_loaded'] = true;
    
    $auth = new Auth();
    $info['auth_instantiated'] = true;
    
} catch (Exception $e) {
    $info['error'] = array(
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    );
}

// 返回JSON响应
echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);