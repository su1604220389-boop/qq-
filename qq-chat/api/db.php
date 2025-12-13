<?php
/**
 * 数据存储连接文件
 * 支持数据库和文件存储两种模式
 */

// 包含配置文件
require_once 'config.php';

// 根据配置决定使用哪种存储方式
if (USE_FILE_STORAGE) {
    // 使用文件存储
    require_once 'file_storage.php';
    $storage = new FileStorage();
} else {
    // 使用数据库存储
    class Database {
        private $connection;
        
        public function __construct() {
            try {
                // 创建PDO连接
                $this->connection = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASSWORD,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                // 记录错误并返回错误信息
                error_log("数据库连接失败: " . $e->getMessage());
                die(json_encode(['status' => 'error', 'message' => '数据库连接失败']));
            }
        }
        
        /**
         * 获取数据库连接
         * @return PDO
         */
        public function getConnection() {
            return $this->connection;
        }
        
        /**
         * 执行查询
         * @param string $query SQL查询语句
         * @param array $params 参数数组
         * @return PDOStatement
         */
        public function query($query, $params = []) {
            try {
                $stmt = $this->connection->prepare($query);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                error_log("查询执行失败: " . $e->getMessage());
                die(json_encode(['status' => 'error', 'message' => '查询执行失败']));
            }
        }
        
        /**
         * 获取最后插入的ID
         * @return int
         */
        public function lastInsertId() {
            return $this->connection->lastInsertId();
        }
        
        /**
         * 关闭连接
         */
        public function close() {
            $this->connection = null;
        }
    }
    
    // 创建数据库实例
    $db = new Database();
}