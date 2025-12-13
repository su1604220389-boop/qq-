<?php
/**
 * 文件存储类
 * 用于处理用户数据的文件存储操作
 */
class FileStorage {
    private $storagePath;
    private $usersFile;
    private $friendsFile;
    private $messagesFile;
    private $sessionsFile;
    
    public function __construct() {
        // 设置存储路径
        $this->storagePath = dirname(__DIR__) . '/storage/';
        
        // 确保存储目录存在
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
        
        // 设置文件路径
        $this->usersFile = $this->storagePath . 'users.json';
        $this->friendsFile = $this->storagePath . 'friends.json';
        $this->messagesFile = $this->storagePath . 'messages.json';
        $this->sessionsFile = $this->storagePath . 'user_sessions.json';
        
        // 初始化文件
        $this->initFiles();
    }
    
    /**
     * 初始化存储文件
     */
    private function initFiles() {
        // 初始化用户文件
        if (!file_exists($this->usersFile)) {
            file_put_contents($this->usersFile, json_encode([]));
        }
        
        // 初始化好友关系文件
        if (!file_exists($this->friendsFile)) {
            file_put_contents($this->friendsFile, json_encode([]));
        }
        
        // 初始化消息文件
        if (!file_exists($this->messagesFile)) {
            file_put_contents($this->messagesFile, json_encode([]));
        }
        
        // 初始化会话文件
        if (!file_exists($this->sessionsFile)) {
            file_put_contents($this->sessionsFile, json_encode([]));
        }
    }
    
    /**
     * 读取文件内容
     * @param string $file 文件路径
     * @return array 文件内容
     */
    private function readFile($file) {
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        // 移除PHP的魔术引号导致的反斜杠转义
        $content = stripslashes($content);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * 写入文件内容
     * @param string $file 文件路径
     * @param array $data 要写入的数据
     * @return bool 是否写入成功
     */
    private function writeFile($file, $data) {
        return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE)) !== false;
    }
    
    /**
     * 获取所有用户
     * @return array 用户列表
     */
    public function getUsers() {
        return $this->readFile($this->usersFile);
    }
    
    /**
     * 根据ID获取用户
     * @param int $id 用户ID
     * @return array|null 用户信息或null
     */
    public function getUserById($id) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * 根据邮箱获取用户
     * @param string $email 用户邮箱
     * @return array|null 用户信息或null
     */
    public function getUserByEmail($email) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['email'] == $email) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * 根据用户名获取用户
     * @param string $username 用户名
     * @return array|null 用户信息或null
     */
    public function getUserByUsername($username) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] == $username) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * 搜索用户
     * @param string $keyword 搜索关键词
     * @param int $excludeId 排除的用户ID
     * @return array 用户列表
     */
    public function searchUsers($keyword, $excludeId = null) {
        $users = $this->getUsers();
        $results = [];
        
        foreach ($users as $user) {
            if ($excludeId && $user['id'] == $excludeId) {
                continue;
            }
            
            if (stripos($user['username'], $keyword) !== false) {
                $results[] = $user;
            }
        }
        
        return $results;
    }
    
    /**
     * 添加用户
     * @param array $user 用户信息
     * @return int 用户ID
     */
    public function addUser($user) {
        $users = $this->getUsers();
        
        // 生成用户ID
        $user['id'] = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1;
        
        // 设置默认头像
        if (!isset($user['avatar'])) {
            $user['avatar'] = 'https://p3-flow-imagex-sign.byteimg.com/tos-cn-i-a9rns2rl98/rc/pc/super_tool/c5c1cefd210649449e5654488b536610~tplv-a9rns2rl98-image.image?rcl=20251211013204EB34B3447BDC28D5F481&rk3s=8e244e95&rrcfp=f06b921b&x-expires=1767980014&x-signature=vtVRI6%2BteRoXMgyzR8opnDwyN2A%3D';
        }
        
        // 设置默认状态
        if (!isset($user['status'])) {
            $user['status'] = 0; // 离线
        }
        
        // 添加用户
        $users[] = $user;
        
        // 保存用户列表
        $this->writeFile($this->usersFile, $users);
        
        return $user['id'];
    }
    
    /**
     * 更新用户
     * @param int $id 用户ID
     * @param array $data 更新的数据
     * @return bool 是否更新成功
     */
    public function updateUser($id, $data) {
        $users = $this->getUsers();
        
        foreach ($users as &$user) {
            if ($user['id'] == $id) {
                // 更新用户信息
                $user = array_merge($user, $data);
                
                // 保存用户列表
                $this->writeFile($this->usersFile, $users);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 删除用户
     * @param int $id 用户ID
     * @return bool 是否删除成功
     */
    public function deleteUser($id) {
        $users = $this->getUsers();
        $originalCount = count($users);
        
        // 过滤掉指定ID的用户
        $users = array_filter($users, function($user) use ($id) {
            return $user['id'] != $id;
        });
        
        // 如果用户数量减少，说明用户被找到并删除
        if (count($users) < $originalCount) {
            // 重新索引数组并保存
            $this->writeFile($this->usersFile, array_values($users));
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取用户好友列表
     * @param int $userId 用户ID
     * @return array 好友列表
     */
    public function getFriends($userId) {
        $friendsData = $this->readFile($this->friendsFile);
        $users = $this->getUsers();
        $friends = [];
        $addedFriendIds = []; // 用于跟踪已添加的好友ID，避免重复
        
        foreach ($friendsData as $friendship) {
            // 跳过非好友关系
            if ($friendship['status'] != 1) {
                continue;
            }
            
            $friendId = null;
            
            // 确定好友ID
            if ($friendship['user_id'] == $userId) {
                $friendId = $friendship['friend_id'];
            } elseif ($friendship['friend_id'] == $userId) {
                $friendId = $friendship['user_id'];
            }
            
            // 如果找到了好友ID且尚未添加，则添加到好友列表
            if ($friendId && !in_array($friendId, $addedFriendIds)) {
                // 查找好友用户信息
                $friend = $this->getUserById($friendId);
                if ($friend) {
                    // 获取最后一条消息
                    $lastMessage = $this->getLastMessage($userId, $friendId);
                    
                    // 获取未读消息数
                    $unreadCount = $this->getUnreadCount($userId, $friendId);
                    
                    // 添加好友信息
                    $friends[] = [
                        'id' => $friend['id'],
                        'username' => $friend['username'],
                        'avatar' => $friend['avatar'],
                        'status' => $friend['status'],
                        'lastMessage' => $lastMessage ? $lastMessage['content'] : '',
                        'lastMessageTime' => $lastMessage ? $lastMessage['created_at'] : '',
                        'unreadCount' => $unreadCount,
                        'messages' => [] // 消息列表将在需要时单独获取
                    ];
                    
                    // 标记该好友已添加
                    $addedFriendIds[] = $friendId;
                }
            }
        }
        
        return $friends;
    }
    
    /**
     * 获取用户好友列表（包含消息信息）
     * @param int $userId 用户ID
     * @return array 好友列表
     */
    public function getFriendsWithMessages($userId) {
        return $this->getFriends($userId);
    }
    
    /**
     * 获取用户收到的好友请求
     * @param int $userId 用户ID
     * @return array 好友请求列表
     */
    public function getFriendRequests($userId) {
        $friendsData = $this->readFile($this->friendsFile);
        $users = $this->getUsers();
        $requests = [];
        
        foreach ($friendsData as $friendship) {
            if ($friendship['friend_id'] == $userId && $friendship['status'] == 0) {
                // 查找发送请求的用户信息
                $user = $this->getUserById($friendship['user_id']);
                if ($user) {
                    $requests[] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'avatar' => $user['avatar'],
                        'status' => $user['status'],
                        'created_at' => $friendship['created_at']
                    ];
                }
            }
        }
        
        return $requests;
    }
    
    /**
     * 发送好友请求
     * @param int $userId 用户ID
     * @param int $friendId 好友ID
     * @return bool 是否发送成功
     */
    public function sendFriendRequest($userId, $friendId) {
        // 检查是否已经是好友
        if ($this->isFriend($userId, $friendId)) {
            return false;
        }
        
        // 检查是否已经发送过请求
        $friendsData = $this->readFile($this->friendsFile);
        foreach ($friendsData as $friendship) {
            if ($friendship['user_id'] == $userId && $friendship['friend_id'] == $friendId) {
                return false; // 已经发送过请求
            }
        }
        
        // 添加好友请求
        $friendsData[] = [
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 0, // 0表示待接受的好友请求
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 保存好友关系
        $this->writeFile($this->friendsFile, $friendsData);
        
        return true;
    }
    
    /**
     * 获取好友请求
     * @param int $userId 用户ID
     * @param int $friendId 好友ID
     * @return array|null 好友请求或null
     */
    public function getFriendRequest($userId, $friendId) {
        $friendsData = $this->readFile($this->friendsFile);
        
        foreach ($friendsData as $friendship) {
            if ($friendship['user_id'] == $userId && $friendship['friend_id'] == $friendId && $friendship['status'] == 0) {
                return $friendship;
            }
        }
        
        return null;
    }
    
    /**
     * 接受好友请求
     * @param int $userId 用户ID
     * @param int $friendId 好友ID
     * @return bool 是否接受成功
     */
    public function acceptFriendRequest($userId, $friendId) {
        $friendsData = $this->readFile($this->friendsFile);
        $updated = false;
        
        // 找到并删除原始好友请求
        $newFriendsData = [];
        foreach ($friendsData as $friendship) {
            if ($friendship['user_id'] == $userId && $friendship['friend_id'] == $friendId && $friendship['status'] == 0) {
                // 不添加这个请求到新数组中（相当于删除）
                $updated = true;
            } else {
                $newFriendsData[] = $friendship;
            }
        }
        
        if ($updated) {
            // 添加双向好友关系
            $newFriendsData[] = [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $newFriendsData[] = [
                'user_id' => $friendId,
                'friend_id' => $userId,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // 保存好友关系
            $this->writeFile($this->friendsFile, $newFriendsData);
        }
        
        return $updated;
    }
    
    /**
     * 拒绝好友请求
     * @param int $userId 用户ID
     * @param int $friendId 好友ID
     * @return bool 是否拒绝成功
     */
    public function rejectFriendRequest($userId, $friendId) {
        $friendsData = $this->readFile($this->friendsFile);
        $updated = false;
        
        // 过滤掉要拒绝的好友请求
        $newFriendsData = array_filter($friendsData, function($friendship) use ($userId, $friendId) {
            return !($friendship['user_id'] == $userId && $friendship['friend_id'] == $friendId && $friendship['status'] == 0);
        });
        
        if (count($newFriendsData) != count($friendsData)) {
            $this->writeFile($this->friendsFile, array_values($newFriendsData));
            $updated = true;
        }
        
        return $updated;
    }
    
    /**
     * 删除用户的所有好友关系
     * @param int $userId 用户ID
     * @return bool 是否删除成功
     */
    public function deleteUserFriendships($userId) {
        $friendsData = $this->readFile($this->friendsFile);
        $originalCount = count($friendsData);
        
        // 过滤掉与该用户相关的所有好友关系
        $newFriendsData = array_filter($friendsData, function($friendship) use ($userId) {
            return $friendship['user_id'] != $userId && $friendship['friend_id'] != $userId;
        });
        
        // 如果好友关系数量减少，说明有删除操作
        if (count($newFriendsData) < $originalCount) {
            $this->writeFile($this->friendsFile, array_values($newFriendsData));
            return true;
        }
        
        return false;
    }
    
    /**
     * 删除好友
     * @param int $userId 用户ID
     * @param int $friendId 好友ID
     * @return bool 是否删除成功
     */
    public function deleteFriend($userId, $friendId) {
        $friendsData = $this->readFile($this->friendsFile);
        $updated = false;
        
        // 过滤掉要删除的好友关系
        $newFriendsData = array_filter($friendsData, function($friendship) use ($userId, $friendId) {
            return !((($friendship['user_id'] == $userId && $friendship['friend_id'] == $friendId) ||
                    ($friendship['user_id'] == $friendId && $friendship['friend_id'] == $userId)) &&
                    $friendship['status'] == 1);
        });
        
        if (count($newFriendsData) != count($friendsData)) {
            $this->writeFile($this->friendsFile, array_values($newFriendsData));
            $updated = true;
        }
        
        return $updated;
    }
    
    /**
     * 检查是否是好友
     * @param int $userId1 用户1ID
     * @param int $userId2 用户2ID
     * @return bool 是否是好友
     */
    public function isFriend($userId1, $userId2) {
        $friendsData = $this->readFile($this->friendsFile);
        
        foreach ($friendsData as $friendship) {
            if (($friendship['user_id'] == $userId1 && $friendship['friend_id'] == $userId2 && $friendship['status'] == 1) ||
                ($friendship['user_id'] == $userId2 && $friendship['friend_id'] == $userId1 && $friendship['status'] == 1)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 添加好友关系
     * @param int $userId 用户ID
     * @param int $friendId 好友ID
     * @return bool 是否添加成功
     */
    public function addFriend($userId, $friendId) {
        // 检查是否已经是好友
        if ($this->isFriend($userId, $friendId)) {
            return false;
        }
        
        $friendsData = $this->readFile($this->friendsFile);
        
        // 添加好友关系
        $friendsData[] = [
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 1, // 1表示已接受的好友关系
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 保存好友关系
        $this->writeFile($this->friendsFile, $friendsData);
        
        return true;
    }
    
    /**
     * 获取两个用户之间的消息
     * @param int $userId1 用户1ID
     * @param int $userId2 用户2ID
     * @return array 消息列表
     */
    public function getMessages($userId1, $userId2) {
        $messages = $this->readFile($this->messagesFile);
        $result = [];
        
        foreach ($messages as $message) {
            if (($message['sender_id'] == $userId1 && $message['receiver_id'] == $userId2) ||
                ($message['sender_id'] == $userId2 && $message['receiver_id'] == $userId1)) {
                $result[] = $message;
            }
        }
        
        // 按时间排序
        usort($result, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return $result;
    }
    
    /**
     * 获取最后一条消息
     * @param int $userId1 用户1ID
     * @param int $userId2 用户2ID
     * @return array|null 最后一条消息或null
     */
    public function getLastMessage($userId1, $userId2) {
        $messages = $this->getMessages($userId1, $userId2);
        
        if (count($messages) > 0) {
            return end($messages);
        }
        
        return null;
    }
    
    /**
     * 获取未读消息数
     * @param int $userId 用户ID
     * @param int $fromId 发送者ID
     * @return int 未读消息数
     */
    public function getUnreadCount($userId, $fromId) {
        $messages = $this->readFile($this->messagesFile);
        $count = 0;
        
        foreach ($messages as $message) {
            if ($message['receiver_id'] == $userId && $message['sender_id'] == $fromId && $message['is_read'] == 0) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * 添加消息
     * @param array $message 消息内容
     * @return int 消息ID
     */
    public function addMessage($message) {
        $messages = $this->readFile($this->messagesFile);
        
        // 生成消息ID
        $message['id'] = count($messages) > 0 ? max(array_column($messages, 'id')) + 1 : 1;
        
        // 设置创建时间
        if (!isset($message['created_at'])) {
            $message['created_at'] = date('Y-m-d H:i:s');
        }
        
        // 设置默认已读状态
        if (!isset($message['is_read'])) {
            $message['is_read'] = 0;
        }
        
        // 添加消息
        $messages[] = $message;
        
        // 保存消息列表
        $this->writeFile($this->messagesFile, $messages);
        
        return $message['id'];
    }
    
    /**
     * 标记消息为已读
     * @param int $userId 用户ID
     * @param int $fromId 发送者ID
     * @return bool 是否标记成功
     */
    public function markMessagesAsRead($userId, $fromId) {
        $messages = $this->readFile($this->messagesFile);
        $updated = false;
        
        foreach ($messages as &$message) {
            if ($message['receiver_id'] == $userId && $message['sender_id'] == $fromId && $message['is_read'] == 0) {
                $message['is_read'] = 1;
                $updated = true;
            }
        }
        
        if ($updated) {
            $this->writeFile($this->messagesFile, $messages);
        }
        
        return $updated;
    }
    
    /**
     * 标记消息为已读
     * @param int $messageId 消息ID
     * @return bool 是否标记成功
     */
    public function markMessageAsRead($messageId) {
        $messages = $this->readFile($this->messagesFile);
        
        foreach ($messages as &$message) {
            if ($message['id'] == $messageId) {
                $message['is_read'] = 1;
                $this->writeFile($this->messagesFile, $messages);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 删除用户的所有消息
     * @param int $userId 用户ID
     * @return bool 是否删除成功
     */
    public function deleteUserMessages($userId) {
        $messages = $this->readFile($this->messagesFile);
        $originalCount = count($messages);
        
        // 过滤掉与该用户相关的所有消息
        $newMessages = array_filter($messages, function($message) use ($userId) {
            return $message['sender_id'] != $userId && $message['receiver_id'] != $userId;
        });
        
        // 如果消息数量减少，说明有删除操作
        if (count($newMessages) < $originalCount) {
            $this->writeFile($this->messagesFile, array_values($newMessages));
            return true;
        }
        
        return false;
    }
    
    /**
     * 删除用户的所有会话
     * @param int $userId 用户ID
     * @return bool 是否删除成功
     */
    public function deleteUserSessions($userId) {
        $sessions = $this->readFile($this->sessionsFile);
        $originalCount = count($sessions);
        
        // 过滤掉与该用户相关的所有会话
        $newSessions = array_filter($sessions, function($session) use ($userId) {
            return $session['user_id'] != $userId;
        });
        
        // 如果会话数量减少，说明有删除操作
        if (count($newSessions) < $originalCount) {
            $this->writeFile($this->sessionsFile, array_values($newSessions));
            return true;
        }
        
        return false;
    }
    
    /**
     * 保存用户会话
     * @param int $userId 用户ID
     * @param string $sessionId 会话ID
     * @return bool 是否保存成功
     */
    public function saveSession($userId, $sessionId) {
        $sessions = $this->readFile($this->sessionsFile);
        
        // 检查是否已存在会话
        foreach ($sessions as &$session) {
            if ($session['user_id'] == $userId) {
                $session['session_id'] = $sessionId;
                $session['updated_at'] = date('Y-m-d H:i:s');
                
                // 保存会话列表
                $this->writeFile($this->sessionsFile, $sessions);
                
                return true;
            }
        }
        
        // 添加新会话
        $sessions[] = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 保存会话列表
        $this->writeFile($this->sessionsFile, $sessions);
        
        return true;
    }
    
    /**
     * 获取用户会话
     * @param int $userId 用户ID
     * @return array|null 会话信息或null
     */
    public function getSession($userId) {
        $sessions = $this->readFile($this->sessionsFile);
        
        foreach ($sessions as $session) {
            if ($session['user_id'] == $userId) {
                return $session;
            }
        }
        
        return null;
    }
    
    /**
     * 删除用户会话
     * @param int $userId 用户ID
     * @return bool 是否删除成功
     */
    public function deleteSession($userId) {
        $sessions = $this->readFile($this->sessionsFile);
        $updated = false;
        
        // 过滤掉要删除的会话
        $newSessions = array_filter($sessions, function($session) use ($userId) {
            return $session['user_id'] != $userId;
        });
        
        if (count($newSessions) != count($sessions)) {
            $this->writeFile($this->sessionsFile, array_values($newSessions));
            $updated = true;
        }
        
        return $updated;
    }
}