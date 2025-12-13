<?php
/**
 * 服务器端测试脚本
 * 用于检查存储目录状态、API配置和基本功能
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查当前目录结构
function checkDirectoryStructure() {
    echo "=== 目录结构检查 ===\n";
    
    $baseDir = dirname(__FILE__);
    echo "当前工作目录: " . getcwd() . "\n";
    echo "脚本所在目录: " . $baseDir . "\n";
    
    // 检查存储目录
    $storageDir = $baseDir . '/storage/';
    echo "存储目录路径: " . $storageDir . "\n";
    echo "存储目录是否存在: " . (is_dir($storageDir) ? '是' : '否') . "\n";
    
    if (is_dir($storageDir)) {
        echo "存储目录权限: " . substr(sprintf('%o', fileperms($storageDir)), -4) . "\n";
        echo "存储目录是否可写: " . (is_writable($storageDir) ? '是' : '否') . "\n";
        
        // 检查存储文件
        $files = ['users.json', 'friends.json', 'messages.json', 'user_sessions.json'];
        foreach ($files as $file) {
            $filePath = $storageDir . $file;
            echo "文件 {$file} 是否存在: " . (file_exists($filePath) ? '是' : '否') . "\n";
            if (file_exists($filePath)) {
                echo "文件 {$file} 大小: " . filesize($filePath) . " bytes\n";
                echo "文件 {$file} 是否可写: " . (is_writable($filePath) ? '是' : '否') . "\n";
            }
        }
    } else {
        echo "创建存储目录尝试: " . (mkdir($storageDir, 0755, true) ? '成功' : '失败') . "\n";
    }
    
    echo "\n";
}

// 检查API配置
function checkApiConfiguration() {
    echo "=== API配置检查 ===\n";
    
    // 检查db.php配置
    if (file_exists('api/db.php')) {
        include('api/db.php');
        echo "USE_FILE_STORAGE 配置: " . (defined('USE_FILE_STORAGE') ? USE_FILE_STORAGE : '未定义') . "\n";
        
        // 检查存储实例
        global $storage, $db;
        echo "FileStorage实例是否存在: " . (isset($storage) ? '是' : '否') . "\n";
        echo "Database实例是否存在: " . (isset($db) ? '是' : '否') . "\n";
    } else {
        echo "api/db.php 文件不存在\n";
    }
    
    echo "\n";
}

// 测试基本的文件操作
function testFileOperations() {
    echo "=== 文件操作测试 ===\n";
    
    $storageDir = dirname(__FILE__) . '/storage/';
    $testFile = $storageDir . 'test.txt';
    
    // 写入测试
    $writeResult = file_put_contents($testFile, "Test content at " . date('Y-m-d H:i:s'));
    echo "写入测试文件结果: " . ($writeResult !== false ? '成功' : '失败') . "\n";
    
    // 读取测试
    if (file_exists($testFile)) {
        $content = file_get_contents($testFile);
        echo "读取测试文件结果: " . ($content !== false ? '成功' : '失败') . "\n";
        if ($content !== false) {
            echo "测试文件内容: " . $content . "\n";
        }
        
        // 删除测试
        $deleteResult = unlink($testFile);
        echo "删除测试文件结果: " . ($deleteResult ? '成功' : '失败') . "\n";
    }
    
    echo "\n";
}

// 测试Auth类基本功能
function testAuthClass() {
    echo "=== Auth类测试 ===\n";
    
    if (file_exists('api/auth.php')) {
        include('api/db.php');
        include('api/auth.php');
        
        try {
            $auth = new Auth();
            echo "Auth类实例化: 成功\n";
            
            // 测试用户列表获取
            if (method_exists($auth, 'getUsers')) {
                $users = $auth->getUsers();
                echo "获取用户列表: 成功，共 " . count($users) . " 个用户\n";
            } else {
                echo "Auth类没有getUsers方法\n";
            }
        } catch (Exception $e) {
            echo "Auth类实例化失败: " . $e->getMessage() . "\n";
        }
    } else {
        echo "api/auth.php 文件不存在\n";
    }
    
    echo "\n";
}

// 运行所有测试
function runAllTests() {
    checkDirectoryStructure();
    checkApiConfiguration();
    testFileOperations();
    testAuthClass();
    
    echo "=== 测试完成 ===\n";
}

// 执行测试
runAllTests();
?>