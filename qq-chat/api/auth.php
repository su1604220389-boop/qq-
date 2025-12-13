<?php
/**
 * 认证相关功能
 */

// 包含配置文件和数据库连接文件
require_once 'config.php';
require_once 'db.php';

class Auth {
    private $storage;
    private $useFileStorage;
    
    public function __construct() {
        // 根据配置选择存储方式
        $this->useFileStorage = defined('USE_FILE_STORAGE') && USE_FILE_STORAGE;
        
        if ($this->useFileStorage) {
            // 使用文件存储
            require_once 'file_storage.php';
            $this->storage = new FileStorage();
        } else {
            // 使用数据库存储
            require_once 'db.php';
            $this->storage = $GLOBALS['db'];
        }
    }
    
    /**
     * 检查用户是否已登录
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * 获取当前登录用户
     * @return array|bool
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        
        if ($this->useFileStorage) {
            // 使用文件存储
            $user = $this->storage->getUserById($userId);
        } else {
            // 使用数据库存储
            $stmt = $this->storage->query("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
            $user = $stmt->fetch();
        }
        
        if (!$user) {
            return false;
        }
        
        return $user;
    }
    
    /**
     * 登录
     * @param string $identifier 邮箱或用户名
     * @param string $password 密码
     * @return array|bool
     */
    public function login($identifier, $password) {
        // 处理反斜杠转义（兼容可能的魔术引号设置）
        $password = stripslashes($password);
        // 查找用户
        if ($this->useFileStorage) {
            // 使用文件存储
            // 先尝试用邮箱查找
            $user = $this->storage->getUserByEmail($identifier);
            // 如果没找到，再尝试用用户名查找
            if (!$user) {
                $user = $this->storage->getUserByUsername($identifier);
            }
        } else {
            // 使用数据库存储
            // 先尝试用邮箱查找
            $stmt = $this->storage->query("SELECT * FROM users WHERE email = :identifier", ['identifier' => $identifier]);
            $user = $stmt->fetch();
            // 如果没找到，再尝试用用户名查找
            if (!$user) {
                $stmt = $this->storage->query("SELECT * FROM users WHERE username = :identifier", ['identifier' => $identifier]);
                $user = $stmt->fetch();
            }
        }
        
        if (!$user) {
            return ['status' => 'error', 'message' => '邮箱或密码错误'];
        }
        
        // 验证密码
        if (!password_verify($password, $user['password'])) {
            return ['status' => 'error', 'message' => '邮箱或密码错误'];
        }
        
        // 更新用户状态
        if ($this->useFileStorage) {
            // 使用文件存储
            $this->storage->updateUser($user['id'], ['status' => 1, 'last_active' => date('Y-m-d H:i:s')]);
            // 保存会话
            $this->storage->saveSession($user['id'], session_id());
        } else {
            // 使用数据库存储
            $this->storage->query(
                "UPDATE users SET last_active = NOW(), status = 1 WHERE id = :id",
                ['id' => $user['id']]
            );
        }
        
        // 设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        return [
            'status' => 'success',
            'message' => '登录成功',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'status' => 1
            ]
        ];
    }
    
    /**
     * 注册
     * @param string $username 用户名
     * @param string $email 邮箱
     * @param string $password 密码
     * @param string $ip 注册IP
     * @return array
     */
    public function register($username, $email, $password, $ip) {
        // 检查邮箱是否已存在
        if ($this->useFileStorage) {
            // 使用文件存储
            $existingUser = $this->storage->getUserByEmail($email);
            if ($existingUser) {
                return ['status' => 'error', 'message' => '该邮箱已被注册'];
            }
            
            // 检查用户名是否已存在
            $existingUser = $this->storage->getUserByUsername($username);
            if ($existingUser) {
                return ['status' => 'error', 'message' => '该用户名已被使用'];
            }
            
            // 检查IP是否已注册（在文件存储中，我们需要遍历所有用户来检查IP）
            // 注释掉IP限制以便测试
            // $users = $this->storage->getUsers();
            // foreach ($users as $user) {
            //     if (isset($user['register_ip']) && $user['register_ip'] == $ip) {
            //         return ['status' => 'error', 'message' => '该IP地址已注册过账号'];
            //     }
            // }
        } else {
            // 使用数据库存储
            $stmt = $this->storage->query("SELECT * FROM users WHERE email = :email", ['email' => $email]);
            if ($stmt->fetch()) {
                return ['status' => 'error', 'message' => '该邮箱已被注册'];
            }
            
            // 检查IP是否已注册
            // 注释掉IP限制以便测试
            // $stmt = $this->storage->query("SELECT * FROM users WHERE register_ip = :ip", ['ip' => $ip]);
            // if ($stmt->fetch()) {
            //     return ['status' => 'error', 'message' => '该IP地址已注册过账号'];
            // }
        }
        
        // 哈希密码
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 创建用户数据
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'register_ip' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s'),
            'status' => 1,
            'avatar' => 'https://p3-flow-imagex-sign.byteimg.com/tos-cn-i-a9rns2rl98/rc/pc/super_tool/c5c1cefd210649449e5654488b536610~tplv-a9rns2rl98-image.image?rcl=20251211013204EB34B3447BDC28D5F481&rk3s=8e244e95&rrcfp=f06b921b&x-expires=1767980014&x-signature=vtVRI6%2BteRoXMgyzR8opnDwyN2A%3D'
        ];
        
        // 添加用户
        if ($this->useFileStorage) {
            // 使用文件存储
            $userId = $this->storage->addUser($userData);
            // 保存会话
            $this->storage->saveSession($userId, session_id());
        } else {
            // 使用数据库存储
            $this->storage->query(
                "INSERT INTO users (username, email, password, register_ip, created_at, last_active, status, avatar) 
                 VALUES (:username, :email, :password, :ip, NOW(), NOW(), 1, :avatar)",
                [
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'ip' => $ip,
                    'avatar' => $userData['avatar']
                ]
            );
            $userId = $this->storage->lastInsertId();
        }
        
        // 设置会话
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        
        return [
            'status' => 'success',
            'message' => '注册成功',
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'avatar' => $userData['avatar'],
                'status' => 1
            ]
        ];
    }
    
    /**
     * 登出
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            
            // 更新用户状态为离线
            if ($this->useFileStorage) {
                // 使用文件存储
                $this->storage->updateUser($userId, ['status' => 0]);
                // 删除会话
                $this->storage->deleteSession($userId);
            } else {
                // 使用数据库存储
                $this->storage->query(
                    "UPDATE users SET status = 0 WHERE id = :id",
                    ['id' => $userId]
                );
            }
            
            // 销毁会话
            session_destroy();
        }
    }
    
    /**
     * 更新用户资料
     * @param int $userId 用户ID
     * @param array $data 更新数据
     * @return array
     */
    public function updateProfile($userId, $data) {
        if ($this->useFileStorage) {
            // 使用文件存储
            $result = $this->storage->updateUser($userId, $data);
            return $result ? ['status' => 'success', 'message' => '资料更新成功'] : ['status' => 'error', 'message' => '资料更新失败'];
        } else {
            // 使用数据库存储
            $allowedFields = ['username', 'avatar', 'status'];
            $updateFields = [];
            $params = ['id' => $userId];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }
            
            if (empty($updateFields)) {
                return ['status' => 'error', 'message' => '没有要更新的字段'];
            }
            
            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $this->storage->query($query, $params);
            
            return ['status' => 'success', 'message' => '资料更新成功'];
        }
    }
    
    /**
     * 获取存储实例
     * @return mixed
     */
    public function getStorage() {
        return $this->storage;
    }
    
    /**
     * 是否使用文件存储
     * @return bool
     */
    public function isUsingFileStorage() {
        return $this->useFileStorage;
    }
    
    /**
     * 更改密码
     * @param int $userId 用户ID
     * @param string $oldPassword 旧密码
     * @param string $newPassword 新密码
     * @return array
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // 获取用户
        if ($this->useFileStorage) {
            // 使用文件存储
            $user = $this->storage->getUserById($userId);
        } else {
            // 使用数据库存储
            $stmt = $this->storage->query("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
            $user = $stmt->fetch();
        }
        
        if (!$user) {
            return ['status' => 'error', 'message' => '用户不存在'];
        }
        
        // 验证旧密码
        if (!password_verify($oldPassword, $user['password'])) {
            return ['status' => 'error', 'message' => '旧密码错误'];
        }
        
        // 哈希新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // 更新密码
        if ($this->useFileStorage) {
            // 使用文件存储
            $result = $this->storage->updateUser($userId, ['password' => $hashedPassword]);
            return $result ? ['status' => 'success', 'message' => '密码修改成功'] : ['status' => 'error', 'message' => '密码修改失败'];
        } else {
            // 使用数据库存储
            $this->storage->query(
                "UPDATE users SET password = :password WHERE id = :id",
                ['password' => $hashedPassword, 'id' => $userId]
            );
            
            return ['status' => 'success', 'message' => '密码修改成功'];
        }
    }
}

// 注意：不再创建全局$auth实例，请在需要使用的文件中自行实例化