# QQ聊天网站部署指南

本指南将帮助您将QQ聊天网站部署为可实际使用的应用，支持真实用户注册、登录和聊天功能。

## 1. 服务器要求

- PHP 8.0+
- Web服务器（Apache/Nginx/PHP内置服务器）
- 已配置的域名和SSL证书（推荐用于生产环境）

## 2. 快速部署（推荐）

项目默认使用JSON文件存储数据，无需安装额外数据库，部署非常简单：

### 2.1 PHP内置服务器（开发环境）

1. 在项目根目录启动PHP内置服务器：
   ```bash
   php -S localhost:8000
   ```

2. 在浏览器中访问：`http://localhost:8000/index.html`

### 2.2 Apache/Nginx（生产环境）

1. 将项目文件部署到Web服务器的根目录或子目录

2. 确保Web服务器支持PHP 8.0+

3. 确保`storage/`目录具有写入权限：
   ```bash
   chmod 775 storage/
   ```

4. 访问网站：`http://your-domain.com/index.html`

## 3. 存储配置

### 3.1 文件存储（默认）

项目默认使用JSON文件存储数据，所有数据文件位于`storage/`目录下：
- `users.json` - 用户数据
- `messages.json` - 消息数据
- `friends.json` - 好友数据
- `user_sessions.json` - 用户会话数据

这种方式无需额外配置，简单易用，适合小规模部署。

### 3.2 MySQL存储（可选）

如果需要使用MySQL存储，请按照以下步骤配置：

#### 3.2.1 创建数据库

```sql
CREATE DATABASE qq_chat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'qq_chat_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON qq_chat.* TO 'qq_chat_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3.2.2 修改配置

修改`api/config.php`文件中的配置：

```php
// 将文件存储改为false
define('USE_FILE_STORAGE', false);

// 设置MySQL数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'qq_chat_user');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'qq_chat');
```

#### 3.2.3 初始化数据库

运行数据库初始化脚本：

```bash
php api/db_init.php
```

或者通过浏览器访问：`http://your-domain.com/api/db_init.php`

## 4. 应用配置

### 4.1 基础配置

修改`api/config.php`文件中的应用配置：

```php
// 应用名称
define('APP_NAME', 'QQ聊天');

// 应用URL - 确保使用正确的域名和端口
define('APP_URL', 'http://your-domain.com');

// 应用密钥 - 用于生成令牌和加密，务必更换为安全的随机字符串
define('APP_SECRET', 'your_secret_key_here');

// 文件上传配置
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB 最大上传大小
define('UPLOAD_DIR', '../assets/images/uploads/'); // 上传目录
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']); // 允许的文件类型
```

### 4.2 邮箱配置（可选）

如果需要实现邮箱验证功能，请配置SMTP设置：

```php
define('EMAIL_HOST', 'smtp.example.com');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'noreply@example.com');
define('EMAIL_PASSWORD', 'your_email_password');
define('EMAIL_FROM', 'noreply@example.com');
define('EMAIL_FROM_NAME', 'QQ聊天');
```

## 5. 安全设置

### 5.1 文件权限

设置正确的文件权限：

```bash
# 设置目录权限
find /path/to/qq-chat -type d -exec chmod 755 {} \;

# 设置文件权限
find /path/to/qq-chat -type f -exec chmod 644 {} \;

# 设置存储目录权限
chmod 775 /path/to/qq-chat/storage/
```

### 5.2 PHP配置

根据部署环境，可能需要调整PHP配置。项目包含一个`.user.ini`文件，用于设置用户级别的PHP配置：

```ini
open_basedir=/www/wwwroot/qq-chat/:/tmp/
```

根据您的服务器配置，可能需要修改此文件中的`open_basedir`路径，确保它指向正确的项目目录。

### 5.3 禁用目录浏览

确保Web服务器禁用了目录浏览功能。

#### 5.3.1 Apache

创建或修改`.htaccess`文件：

```apache
Options -Indexes

# 设置安全头
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

#### 5.3.2 Nginx

在Nginx配置文件中添加：

```nginx
location ~ ^/(api|storage)/ {
    autoindex off;
}
```

## 6. 服务器配置示例

### 6.1 Apache配置

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/qq-chat
    
    <Directory /path/to/qq-chat>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### 6.2 Nginx配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    root /path/to/qq-chat;
    index index.html index.php;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
    }
    
    location ~ ^/(api|storage)/ {
        autoindex off;
    }
    
    # 设置安全头
    add_header X-Content-Type-Options "nosniff";
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
}
```

## 7. 部署完成

完成以上步骤后，您的QQ聊天网站应该已经可以正常使用了。用户可以通过以下方式访问：

- 登录/注册：`http://your-domain.com/index.html`
- 聊天界面：登录后自动跳转
- 设置页面：登录后通过菜单访问

## 8. 功能测试

部署完成后，建议进行以下功能测试：

1. **注册功能**：尝试注册新账号
2. **登录功能**：使用注册的账号登录
3. **添加好友**：搜索用户并发送好友请求
4. **聊天功能**：发送和接收消息
5. **登出功能**：退出登录

### 8.1 API测试

项目提供了多个测试文件，可以帮助您测试API功能：

- `test_api_complete.php` - 完整API测试
- `test_api_core.php` - 核心API测试
- `test_api_endpoints.php` - 端点测试
- `test_register.html` - 注册功能测试
- `test_server_api.html` - 服务器API测试

### 8.2 健康检查

项目提供了健康检查脚本`health_check.php`，用于验证服务器和API状态：

#### 通过CLI运行：
```bash
php health_check.php
```

#### 通过Web浏览器访问：
```
http://your-domain.com/health_check.php
```

健康检查会返回服务器信息、PHP配置、文件和目录权限以及API可用性等详细信息，帮助您快速诊断部署问题。

## 9. 后续维护

### 9.1 数据备份

#### 9.1.1 文件存储备份

定期备份`storage/`目录下的所有JSON文件：

```bash
cp -r storage/ storage_backup_$(date +%Y%m%d)/
```

#### 9.1.2 MySQL存储备份

定期备份MySQL数据库：

```bash
mysqldump -u qq_chat_user -p qq_chat > qq_chat_backup_$(date +%Y%m%d).sql
```

### 9.2 日志监控

监控PHP错误日志和Web服务器日志，及时发现和解决问题。

### 9.3 性能优化

- 对于大规模部署，推荐使用MySQL存储
- 考虑使用缓存机制（如Redis）缓存频繁访问的数据
- 对于高并发场景，考虑使用WebSocket实现真正的实时消息推送

## 10. 常见问题

### 10.1 注册失败

- 检查是否已达到单IP注册限制
- 确保`storage/`目录具有写入权限
- 检查PHP错误日志

### 10.2 登录失败

- 检查邮箱和密码是否正确
- 确保会话Cookie已启用

### 10.3 消息发送失败

- 确保您和对方是好友关系
- 检查网络连接
- 检查PHP错误日志

## 11. 技术支持

如果您在部署过程中遇到问题，可以：

1. 检查PHP错误日志
2. 查看浏览器控制台的JavaScript错误
3. 确保所有文件权限设置正确
4. 确保PHP版本符合要求