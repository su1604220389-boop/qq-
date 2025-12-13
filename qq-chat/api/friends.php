<?php
/**
 * 好友相关接口
 */

// 启动会话
session_start();

// 包含认证文件
require_once 'auth.php';

// 创建认证实例
$auth = new Auth();

// 设置响应头
header('Content-Type: application/json');

// 检查用户是否已登录
if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => '请先登录']);
    exit;
}

// 获取当前用户
$currentUser = $auth->getCurrentUser();

// 检查是否使用文件存储
$useFileStorage = defined('USE_FILE_STORAGE') && USE_FILE_STORAGE;

// 根据请求方法处理
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // 获取好友列表
        if (isset($_GET['action']) && $_GET['action'] === 'requests') {
            // 获取好友请求
            if ($useFileStorage) {
                // 使用文件存储
                global $storage;
                $requests = $storage->getFriendRequests($currentUser['id']);
            } else {
                // 使用数据库存储
                global $db;
                
                $stmt = $db->query(
                    "SELECT u.id, u.username, u.avatar, u.status, f.created_at 
                     FROM users u
                     JOIN friends f ON u.id = f.user_id
                     WHERE f.friend_id = :currentUserId AND f.status = 0",
                    ['currentUserId' => $currentUser['id']]
                );
                
                $requests = $stmt->fetchAll();
            }
            
            echo json_encode(['status' => 'success', 'requests' => $requests]);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'search') {
            // 搜索用户
            if (!isset($_GET['username'])) {
                echo json_encode(['status' => 'error', 'message' => '缺少用户名参数']);
                exit;
            }
            
            $username = trim($_GET['username']);
            
            if (empty($username)) {
                echo json_encode(['status' => 'error', 'message' => '用户名不能为空']);
                exit;
            }
            
            // 根据存储模式搜索用户
            if ($useFileStorage) {
                // 使用文件存储
                global $storage;
                
                // 搜索用户
                $users = $storage->searchUsers($username, $currentUser['id']);
                
                // 获取当前用户的好友列表
                $friends = $storage->getFriends($currentUser['id']);
                $friendIds = array_column($friends, 'id');
                
                // 过滤掉已经是好友的用户
                $users = array_filter($users, function($user) use ($friendIds) {
                    return !in_array($user['id'], $friendIds);
                });
            } else {
                // 使用数据库存储
                global $db;
                
                // 搜索用户
                $stmt = $db->query(
                    "SELECT id, username, avatar, status FROM users 
                     WHERE username LIKE :username AND id != :currentUserId
                     LIMIT 20",
                    [
                        'username' => '%' . $username . '%',
                        'currentUserId' => $currentUser['id']
                    ]
                );
                
                $users = $stmt->fetchAll();
                
                // 检查是否已经是好友
                $userIds = array_column($users, 'id');
                
                if (!empty($userIds)) {
                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                    
                    $stmt = $db->query(
                        "SELECT friend_id FROM friends 
                         WHERE user_id = :currentUserId AND friend_id IN ($placeholders)",
                        array_merge(['currentUserId' => $currentUser['id']], $userIds)
                    );
                    
                    $friendIds = array_column($stmt->fetchAll(), 'friend_id');
                    
                    // 过滤掉已经是好友的用户
                    $users = array_filter($users, function($user) use ($friendIds) {
                        return !in_array($user['id'], $friendIds);
                    });
                }
            }
            
            echo json_encode(['status' => 'success', 'users' => $users]);
        } else {
            // 获取好友列表
            if ($useFileStorage) {
                // 使用文件存储
                global $storage;
                $friends = $storage->getFriendsWithMessages($currentUser['id']);
            } else {
                // 使用数据库存储
                global $db;
                $stmt = $db->query(
                    "SELECT u.id, u.username, u.avatar, u.status, 
                            MAX(m.created_at) as last_message_time,
                            (SELECT content FROM messages 
                             WHERE (sender_id = u.id AND receiver_id = :currentUserId) 
                             OR (sender_id = :currentUserId AND receiver_id = u.id)
                             ORDER BY created_at DESC LIMIT 1) as last_message,
                            (SELECT COUNT(*) FROM messages 
                             WHERE sender_id = u.id AND receiver_id = :currentUserId AND is_read = 0) as unread_count
                     FROM users u
                     JOIN friends f ON u.id = f.friend_id
                     LEFT JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = :currentUserId) 
                                          OR (m.sender_id = :currentUserId AND m.receiver_id = u.id)
                     WHERE f.user_id = :currentUserId AND f.status = 1
                     GROUP BY u.id
                     ORDER BY u.status DESC, last_message_time DESC",
                    ['currentUserId' => $currentUser['id']]
                );
                
                $friends = $stmt->fetchAll();
            }
            
            echo json_encode(['status' => 'success', 'friends' => $friends]);
        }
        break;
        
    case 'POST':
        // 添加好友
        // 尝试从JSON请求体获取数据
        $postData = json_decode(file_get_contents('php://input'), true);
        
        // 如果JSON解析失败或没有friend_id，则尝试从$_POST获取
        if (!isset($postData['friend_id']) && !isset($_POST['friend_id'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少好友ID参数']);
            exit;
        }
        
        // 优先使用JSON数据，其次使用$_POST
        $friendId = intval($postData['friend_id'] ?? $_POST['friend_id']);
        
        if ($friendId <= 0) {
            echo json_encode(['status' => 'error', 'message' => '无效的好友ID']);
            exit;
        }
        
        if ($useFileStorage) {
            // 使用文件存储
            global $storage;
            
            // 检查用户是否存在
            $friend = $storage->getUserById($friendId);
            if (!$friend) {
                echo json_encode(['status' => 'error', 'message' => '用户不存在']);
                exit;
            }
            
            // 检查是否已经是好友
            $friends = $storage->getFriends($currentUser['id']);
            $friendIds = array_column($friends, 'id');
            
            if (in_array($friendId, $friendIds)) {
                echo json_encode(['status' => 'error', 'message' => '已经发送过好友请求']);
                exit;
            }
            
            // 发送好友请求
            $storage->sendFriendRequest($currentUser['id'], $friendId);
        } else {
            // 使用数据库存储
            global $db;
            
            // 检查用户是否存在
            $stmt = $db->query("SELECT id FROM users WHERE id = :id", ['id' => $friendId]);
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => '用户不存在']);
                exit;
            }
            
            // 检查是否已经是好友
            $stmt = $db->query(
                "SELECT id FROM friends 
                 WHERE user_id = :currentUserId AND friend_id = :friendId",
                ['currentUserId' => $currentUser['id'], 'friendId' => $friendId]
            );
            
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => '已经发送过好友请求']);
                exit;
            }
            
            // 发送好友请求
            $db->query(
                "INSERT INTO friends (user_id, friend_id, status, created_at) 
                 VALUES (:currentUserId, :friendId, 0, NOW())",
                ['currentUserId' => $currentUser['id'], 'friendId' => $friendId]
            );
        }
        
        echo json_encode(['status' => 'success', 'message' => '好友请求已发送']);
        break;
        
    case 'PUT':
        // 接受/拒绝好友请求
        // 尝试从JSON请求体获取数据
        $postData = json_decode(file_get_contents('php://input'), true);
        
        // 如果JSON解析失败或没有必要参数，则尝试从$_POST获取
        if ((!isset($postData['friend_id']) && !isset($_POST['friend_id'])) || 
            (!isset($postData['action']) && !isset($_POST['action']))) {
            echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
            exit;
        }
        
        // 优先使用JSON数据，其次使用$_POST
        $friendId = intval($postData['friend_id'] ?? $_POST['friend_id']);
        $action = $postData['action'] ?? $_POST['action'];
        
        if ($friendId <= 0) {
            echo json_encode(['status' => 'error', 'message' => '无效的好友ID']);
            exit;
        }
        
        if ($action !== 'accept' && $action !== 'reject') {
            echo json_encode(['status' => 'error', 'message' => '无效的操作']);
            exit;
        }
        
        if ($useFileStorage) {
            // 使用文件存储
            global $storage;
            
            // 检查好友请求是否存在
            $friendRequest = $storage->getFriendRequest($friendId, $currentUser['id']);
            
            if (!$friendRequest) {
                echo json_encode(['status' => 'error', 'message' => '好友请求不存在']);
                exit;
            }
            
            if ($action === 'accept') {
                // 接受好友请求
                $storage->acceptFriendRequest($friendId, $currentUser['id']);
                echo json_encode(['status' => 'success', 'message' => '已接受好友请求']);
            } else {
                // 拒绝好友请求
                $storage->rejectFriendRequest($friendId, $currentUser['id']);
                echo json_encode(['status' => 'success', 'message' => '已拒绝好友请求']);
            }
        } else {
            // 使用数据库存储
            global $db;
            
            // 检查好友请求是否存在
            $stmt = $db->query(
                "SELECT id FROM friends 
                 WHERE user_id = :friendId AND friend_id = :currentUserId AND status = 0",
                ['friendId' => $friendId, 'currentUserId' => $currentUser['id']]
            );
            
            $friendRequest = $stmt->fetch();
            
            if (!$friendRequest) {
                echo json_encode(['status' => 'error', 'message' => '好友请求不存在']);
                exit;
            }
            
            if ($action === 'accept') {
                // 接受好友请求
                $db->query(
                    "UPDATE friends SET status = 1 WHERE id = :id",
                    ['id' => $friendRequest['id']]
                );
                
                // 添加反向好友关系
                $db->query(
                    "INSERT INTO friends (user_id, friend_id, status, created_at) 
                     VALUES (:currentUserId, :friendId, 1, NOW())",
                    ['currentUserId' => $currentUser['id'], 'friendId' => $friendId]
                );
                
                echo json_encode(['status' => 'success', 'message' => '已接受好友请求']);
            } else {
                // 拒绝好友请求
                $db->query(
                    "DELETE FROM friends WHERE id = :id",
                    ['id' => $friendRequest['id']]
                );
                
                echo json_encode(['status' => 'success', 'message' => '已拒绝好友请求']);
            }
        }
        break;
        
    case 'DELETE':
        // 删除好友
        parse_str(file_get_contents('php://input'), $data);
        
        if (!isset($data['friend_id'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少好友ID参数']);
            exit;
        }
        
        $friendId = intval($data['friend_id']);
        
        if ($friendId <= 0) {
            echo json_encode(['status' => 'error', 'message' => '无效的好友ID']);
            exit;
        }
        
        if ($useFileStorage) {
            // 使用文件存储
            global $storage;
            $storage->deleteFriend($currentUser['id'], $friendId);
        } else {
            // 使用数据库存储
            global $db;
            
            // 删除好友关系
            $db->query(
                "DELETE FROM friends 
                 WHERE (user_id = :currentUserId AND friend_id = :friendId) 
                 OR (user_id = :friendId AND friend_id = :currentUserId)",
                ['currentUserId' => $currentUser['id'], 'friendId' => $friendId]
            );
        }
        
        echo json_encode(['status' => 'success', 'message' => '好友已删除']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => '请求方法错误']);
        break;
}