<?php
/**
 * 用户信息接口
 */

// 启动会话
session_start();

// 包含认证文件
require_once 'auth.php';

// 创建认证实例
$auth = new Auth();

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

// 检查用户是否已登录
if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => '请先登录']);
    exit;
}

// 获取当前用户
$currentUser = $auth->getCurrentUser();

// 根据请求方法处理
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // 获取用户信息
        echo json_encode([
            'status' => 'success',
            'user' => [
                'id' => $currentUser['id'],
                'username' => $currentUser['username'],
                'email' => $currentUser['email'],
                'avatar' => $currentUser['avatar'],
                'status' => $currentUser['status'],
                'last_active' => isset($currentUser['last_active']) ? $currentUser['last_active'] : $currentUser['created_at'],
                'created_at' => $currentUser['created_at']
            ]
        ]);
        break;
        
    case 'PUT':
        // 更新用户信息
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证数据
        if (!isset($data)) {
            echo json_encode(['status' => 'error', 'message' => '请求数据为空']);
            exit;
        }
        
        // 准备更新数据
        $updateData = [];
        
        if (isset($data['username'])) {
            $username = trim($data['username']);
            if (strlen($username) < 2 || strlen($username) > 20) {
                echo json_encode(['status' => 'error', 'message' => '用户名长度应在2-20个字符之间']);
                exit;
            }
            $updateData['username'] = $username;
        }
        
        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }
        
        if (isset($data['status'])) {
            $status = intval($data['status']);
            if ($status < 0 || $status > 2) {
                echo json_encode(['status' => 'error', 'message' => '状态值无效']);
                exit;
            }
            $updateData['status'] = $status;
        }
        
        // 更新用户信息
        if (!empty($updateData)) {
            $result = $auth->updateProfile($currentUser['id'], $updateData);
            
            // 如果更新了用户名，更新会话中的用户名
            if (isset($updateData['username'])) {
                $_SESSION['username'] = $updateData['username'];
            }
            
            echo json_encode($result);
        } else {
            echo json_encode(['status' => 'error', 'message' => '没有要更新的字段']);
        }
        break;
        
    case 'POST':
        // 处理不同的操作
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证数据
        if (!isset($data)) {
            echo json_encode(['status' => 'error', 'message' => '请求数据为空']);
            exit;
        }
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'change_email':
                    // 修改邮箱
                    if (!isset($data['new_email']) || !isset($data['password'])) {
                        echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
                        exit;
                    }
                    
                    $newEmail = $data['new_email'];
                    $password = $data['password'];
                    
                    // 验证邮箱格式
                    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(['status' => 'error', 'message' => '邮箱格式不正确']);
                        exit;
                    }
                    
                    // 验证密码
                    if (!password_verify($password, $currentUser['password'])) {
                        echo json_encode(['status' => 'error', 'message' => '密码错误']);
                        exit;
                    }
                    
                    // 检查邮箱是否已被使用
                    if ($auth->isUsingFileStorage()) {
                        // 使用文件存储
                        $existingUser = $auth->getStorage()->getUserByEmail($newEmail);
                        if ($existingUser && $existingUser['id'] != $currentUser['id']) {
                            echo json_encode(['status' => 'error', 'message' => '邮箱已被使用']);
                            exit;
                        }
                        
                        // 更新邮箱
                        $auth->getStorage()->updateUser($currentUser['id'], ['email' => $newEmail]);
                    } else {
                        // 使用数据库存储
                        global $db;
                        
                        // 检查邮箱是否已被使用
                        $stmt = $db->query(
                            "SELECT id FROM users WHERE email = :email AND id != :currentUserId",
                            ['email' => $newEmail, 'currentUserId' => $currentUser['id']]
                        );
                        
                        if ($stmt->fetch()) {
                            echo json_encode(['status' => 'error', 'message' => '邮箱已被使用']);
                            exit;
                        }
                        
                        // 更新邮箱
                        $db->query(
                            "UPDATE users SET email = :email WHERE id = :currentUserId",
                            ['email' => $newEmail, 'currentUserId' => $currentUser['id']]
                        );
                    }
                    
                    echo json_encode(['status' => 'success', 'message' => '邮箱修改成功']);
                    break;
                    
                case 'change_password':
                    // 修改密码
                    if (!isset($data['current_password']) || !isset($data['new_password'])) {
                        echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
                        exit;
                    }
                    
                    $currentPassword = $data['current_password'];
                    $newPassword = $data['new_password'];
                    
                    if (strlen($newPassword) < 6) {
                        echo json_encode(['status' => 'error', 'message' => '新密码长度不能少于6位']);
                        exit;
                    }
                    
                    $result = $auth->changePassword($currentUser['id'], $currentPassword, $newPassword);
                    echo json_encode($result);
                    break;
                    
                default:
                    echo json_encode(['status' => 'error', 'message' => '不支持的操作']);
                    break;
            }
        } else {
            // 更改密码（旧版API兼容）
            if (!isset($data['old_password']) || !isset($data['new_password'])) {
                echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
                exit;
            }
            
            $oldPassword = $data['old_password'];
            $newPassword = $data['new_password'];
            
            if (strlen($newPassword) < 6) {
                echo json_encode(['status' => 'error', 'message' => '新密码长度不能少于6位']);
                exit;
            }
            
            $result = $auth->changePassword($currentUser['id'], $oldPassword, $newPassword);
            echo json_encode($result);
        }
        break;
        
    case 'DELETE':
        // 注销账号
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['password'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少密码参数']);
            exit;
        }
        
        $password = $data['password'];
        
        // 验证密码
        if (!password_verify($password, $currentUser['password'])) {
            echo json_encode(['status' => 'error', 'message' => '密码错误']);
            exit;
        }
        
        $user_id = $currentUser['id'];
        
        if ($auth->isUsingFileStorage()) {
            // 使用文件存储
            // 使用公共方法删除用户数据
            $auth->getStorage()->deleteUser($user_id);
            
            // 使用公共方法删除好友关系
            $auth->getStorage()->deleteUserFriendships($user_id);
            
            // 使用公共方法删除用户消息
            $auth->getStorage()->deleteUserMessages($user_id);
            
            // 使用公共方法删除用户会话
            $auth->getStorage()->deleteUserSessions($user_id);
        } else {
            // 使用数据库存储
            global $db;
            
            // 开始事务
            $db->beginTransaction();
            
            try {
                // 删除用户的消息
                $db->query("DELETE FROM messages WHERE sender_id = :user_id OR receiver_id = :user_id", ['user_id' => $user_id]);
                
                // 删除用户的好友关系
                $db->query("DELETE FROM friends WHERE user_id = :user_id OR friend_id = :user_id", ['user_id' => $user_id]);
                
                // 删除用户数据
                $db->query("DELETE FROM users WHERE id = :user_id", ['user_id' => $user_id]);
                
                // 提交事务
                $db->commit();
            } catch (Exception $e) {
                // 回滚事务
                $db->rollBack();
                echo json_encode(['status' => 'error', 'message' => '注销账号失败: ' . $e->getMessage()]);
                exit;
            }
        }
        
        // 清除会话
        session_destroy();
        
        echo json_encode(['status' => 'success', 'message' => '账号注销成功']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => '请求方法错误']);
        break;
}