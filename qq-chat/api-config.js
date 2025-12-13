// API配置文件 - 集中管理所有API路径
const API_CONFIG = {
    // 用户认证相关API
    LOGIN: 'api/login.php',
    REGISTER: 'api/register.php',
    LOGOUT: 'api/logout.php',
    
    // 用户相关API
    USER_INFO: 'api/user.php',
    CHANGE_PASSWORD: 'api/user.php',
    CHANGE_EMAIL: 'api/user.php',
    DELETE_ACCOUNT: 'api/user.php',
    
    // 好友相关API
    FRIENDS: 'api/friends.php',
    FRIEND_REQUESTS: 'api/friends.php',
    
    // 消息相关API
    MESSAGES: 'api/messages.php',
    MESSAGE_HISTORY: 'api/messages.php'
};

// 导出配置对象（如果需要支持模块化）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = API_CONFIG;
}