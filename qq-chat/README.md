# QQ风格聊天网站

一个轻量级的即时通讯平台，类似QQ的聊天网站，支持用户注册、登录、添加好友和实时聊天功能。

## 功能特点

- 用户注册（单IP限一个账号）
- 用户登录与个人资料管理
- 好友添加、聊天消息发送与接收
- 实时消息推送
- 在线状态显示
- 消息已读未读状态

## 技术栈

- 前端：HTML5, CSS3, JavaScript, Tailwind CSS, Font Awesome
- 后端：PHP 8.0+, PDO
- 存储：默认JSON文件存储，可选MySQL 8.0+

## 项目结构

```
qq-chat/
├── api/                # API接口目录
│   ├── auth.php        # 认证相关功能
│   ├── config.php      # 配置文件
│   ├── db.php          # 数据库连接（兼容MySQL和文件存储）
│   ├── db_init.php     # 数据库初始化脚本
│   ├── file_storage.php # 文件存储实现
│   ├── friends.php     # 好友相关接口
│   ├── login.php       # 登录接口
│   ├── logout.php      # 登出接口
│   ├── messages.php    # 消息相关接口
│   ├── register.php    # 注册接口
│   └── user.php        # 用户相关接口
├── storage/            # 数据存储目录
│   ├── friends.json    # 好友数据
│   ├── messages.json   # 消息数据
│   ├── user_sessions.json # 用户会话数据
│   └── users.json      # 用户数据
├── index.html          # 登录/注册页面
├── chat.html           # 聊天主页面
├── settings.html       # 设置页面
├── 404.html            # 404错误页面
├── api-config.js       # API配置文件
├── health_check.php    # 健康检查脚本
├── README.md           # 项目说明文档
├── DEPLOYMENT.md       # 部署指南
├── .user.ini           # PHP配置文件
└── 测试文件（以test_开头） # API和功能测试文件
```

## 快速开始

### 环境要求

- PHP 8.0+

### 安装步骤

1. 克隆或下载项目到本地
2. 在项目根目录启动PHP内置服务器：
   ```bash
   php -S localhost:8000
   ```
3. 在浏览器中访问：`http://localhost:8000/index.html`

### 使用说明

1. 注册新账号（单IP限一个账号）
2. 使用注册的账号登录
3. 添加好友（搜索用户名）
4. 开始聊天

## 存储方式

项目默认使用JSON文件存储数据，无需额外安装数据库。存储文件位于`storage/`目录下。

如果需要使用MySQL存储，请修改`api/config.php`文件中的配置：

```php
define('USE_FILE_STORAGE', false); // 改为false使用MySQL
```

## 开发说明

### API接口

所有API接口都位于`api/`目录下，遵循RESTful设计原则。API配置集中管理在`api-config.js`文件中。

### 前端技术

- 使用Tailwind CSS进行样式设计
- 使用Font Awesome提供图标支持
- 使用原生JavaScript实现交互功能

### 健康检查

项目提供了健康检查脚本`health_check.php`，用于验证服务器和API状态：

```bash
php health_check.php
```

### 测试文件

项目包含多个测试文件，用于测试API功能：

- `test_api_complete.php` - 完整API测试
- `test_api_core.php` - 核心API测试
- `test_api_endpoints.php` - 端点测试
- `test_register.html` - 注册功能测试
- `test_server_api.html` - 服务器API测试

## 贡献

欢迎提交Issue和Pull Request来改进项目！

## 许可证

MIT License
