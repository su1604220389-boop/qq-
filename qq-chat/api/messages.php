<?php
/**
 * 消息相关接口
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
        // 获取消息列表
        if (!isset($_GET['friend_id'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少好友ID参数']);
            exit;
        }
        
        $friendId = intval($_GET['friend_id']);
        
        if ($friendId <= 0) {
            echo json_encode(['status' => 'error', 'message' => '无效的好友ID']);
            exit;
        }
        
        if ($useFileStorage) {
            // 使用文件存储
            global $storage;
            
            // 检查是否是好友
            if (!$storage->isFriend($currentUser['id'], $friendId)) {
                echo json_encode(['status' => 'error', 'message' => '不是好友关系']);
                exit;
            }
            
            // 获取消息列表
            $messages = $storage->getMessages($currentUser['id'], $friendId);
            
            // 标记对方发送的消息为已读
            $storage->markMessagesAsRead($currentUser['id'], $friendId);
            
            echo json_encode(['status' => 'success', 'messages' => $messages]);
        } else {
            // 使用数据库存储
            global $db;
            
            // 检查是否是好友
            $stmt = $db->query(
                "SELECT id FROM friends 
                 WHERE ((user_id = :currentUserId AND friend_id = :friendId) 
                 OR (user_id = :friendId AND friend_id = :currentUserId))
                 AND status = 1",
                ['currentUserId' => $currentUser['id'], 'friendId' => $friendId]
            );
            
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => '不是好友关系']);
                exit;
            }
            
            // 获取消息列表
            $stmt = $db->query(
                "SELECT * FROM messages 
                 WHERE (sender_id = :currentUserId AND receiver_id = :friendId) 
                 OR (sender_id = :friendId AND receiver_id = :currentUserId)
                 ORDER BY created_at DESC
                 LIMIT 50",
                ['currentUserId' => $currentUser['id'], 'friendId' => $friendId]
            );
            
            $messages = array_reverse($stmt->fetchAll());
            
            // 标记对方发送的消息为已读
            $db->query(
                "UPDATE messages SET is_read = 1 
                 WHERE sender_id = :friendId AND receiver_id = :currentUserId AND is_read = 0",
                ['friendId' => $friendId, 'currentUserId' => $currentUser['id']]
            );
            
            echo json_encode(['status' => 'success', 'messages' => $messages]);
        }
        break;
        
    case 'POST':
        // 发送消息
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['receiver_id']) || !isset($data['content'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
            exit;
        }
        
        $receiverId = intval($data['receiver_id']);
        $content = trim($data['content']);
        
        if ($receiverId <= 0) {
            echo json_encode(['status' => 'error', 'message' => '无效的接收者ID']);
            exit;
        }
        
        if (empty($content)) {
            echo json_encode(['status' => 'error', 'message' => '消息内容不能为空']);
            exit;
        }
        
        if ($useFileStorage) {
            // 使用文件存储
            global $storage;
            
            // 检查是否是好友
            if (!$storage->isFriend($currentUser['id'], $receiverId)) {
                echo json_encode(['status' => 'error', 'message' => '不是好友关系']);
                exit;
            }
            
            // 创建消息数据
            $messageData = [
                'sender_id' => $currentUser['id'],
                'receiver_id' => $receiverId,
                'content' => $content,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // 发送消息
            $messageId = $storage->addMessage($messageData);
            
            // 获取完整的消息数据
            $messageData['id'] = $messageId;
            
            echo json_encode(['status' => 'success', 'message' => $messageData]);
        } else {
            // 使用数据库存储
            global $db;
            
            // 检查是否是好友
            $stmt = $db->query(
                "SELECT id FROM friends 
                 WHERE ((user_id = :currentUserId AND friend_id = :receiverId) 
                 OR (user_id = :receiverId AND friend_id = :currentUserId))
                 AND status = 1",
                ['currentUserId' => $currentUser['id'], 'receiverId' => $receiverId]
            );
            
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => '不是好友关系']);
                exit;
            }
            
            // 发送消息
            $db->query(
                "INSERT INTO messages (sender_id, receiver_id, content, is_read, created_at) 
                 VALUES (:currentUserId, :receiverId, :content, 0, NOW())",
                ['currentUserId' => $currentUser['id'], 'receiverId' => $receiverId, 'content' => $content]
            );
            
            $messageId = $db->lastInsertId();
            
            // 获取插入的消息
            $stmt = $db->query(
                "SELECT * FROM messages WHERE id = :id",
                ['id' => $messageId]
            );
            
            $message = $stmt->fetch();
            
            echo json_encode(['status' => 'success', 'message' => $message]);
        }
        break;
        
    case 'PUT':
        // 标记消息已读
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['message_id'])) {
            echo json_encode(['status' => 'error', 'message' => '缺少消息ID参数']);
            exit;
        }
        
        $messageId = intval($data['message_id']);
        
        if ($messageId <= 0) {
            echo json_encode(['status' => 'error', 'message' => '无效的消息ID']);
            exit;
        }
        
        if ($useFileStorage) {
            // 使用文件存储 - 暂不支持单条消息标记已读
            echo json_encode(['status' => 'error', 'message' => '文件存储模式暂不支持单条消息标记已读']);
        } else {
            // 使用数据库存储
            global $db;
            
            // 标记消息已读
            $db->query(
                "UPDATE messages SET is_read = 1 
                 WHERE id = :messageId AND receiver_id = :currentUserId",
                ['messageId' => $messageId, 'currentUserId' => $currentUser['id']]
            );
            
            echo json_encode(['status' => 'success', 'message' => '消息已标记为已读']);
        }
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => '请求方法错误']);
        break;
}