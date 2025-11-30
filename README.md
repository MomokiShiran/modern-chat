# Modern Chat - 现代化聊天系统

一个基于 PHP + MySQL + HTML 的现代化聊天系统，具有现代化的 UI 设计和丰富的功能。

---

   ### 已部署好的体验网站

  - https://chat.hyacine.com.cn/chat

---
## 功能特性

- 📱 **现代化 UI 设计** - 响应式设计，适配各种设备
- 🔐 **用户认证** - 安全的注册和登录系统
- 👥 **好友管理** - 添加好友、接受请求、查看好友列表
- 💬 **实时聊天** - 发送文本消息和文件
- 📎 **文件传输** - 支持发送小于 150MB 的文件
- 🟢 **在线状态** - 显示好友是否在线
- 🔍 **好友搜索** - 快速查找好友
- 📱 **响应式设计** - 适配移动端和桌面端

## 技术栈

- **前端**: HTML5, CSS3, JavaScript
- **后端**: PHP 7.4+
- **数据库**: MySQL 5.7+
- **数据库驱动**: PDO

## 安装步骤

### 1. 克隆或下载项目

将项目文件下载到您的 web 服务器目录中。

### 2. 创建数据库

使用 MySQL 客户端执行 `db.sql` 文件来创建数据库和表：

```bash
mysql -u root -p < db.sql
```

或者在 phpMyAdmin 中导入 `db.sql` 文件。

### 3. 配置数据库连接

编辑 `config.php` 文件，配置您的数据库连接信息：

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'chat');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### 4. 配置文件上传

确保 `uploads/` 目录具有写入权限：

```bash
chmod 777 uploads/
```

### 5. 启动 web 服务器

使用 Apache 或 Nginx 启动您的 web 服务器，然后访问项目目录。

## 使用说明

### 1. 注册账户

访问 `register.php` 页面，填写用户名、邮箱和密码进行注册。

### 2. 登录系统

访问 `login.php` 页面，使用注册的邮箱和密码登录。

### 3. 添加好友

在聊天页面的搜索栏中输入好友的用户名，发送好友请求。

### 4. 开始聊天

在好友列表中选择一个好友，开始发送消息和文件。

## 项目结构

```
chat/
├── config.php          # 配置文件
├── db.php              # 数据库连接
├── db.sql              # 数据库初始化脚本
├── User.php            # 用户功能类
├── Friend.php          # 好友功能类
├── Message.php         # 消息功能类
├── FileUpload.php      # 文件上传类
├── register.php        # 注册页面
├── register_process.php # 注册处理
├── login.php           # 登录页面
├── login_process.php   # 登录处理
├── chat.php            # 聊天主页面
├── send_message.php    # 发送消息
├── get_new_messages.php # 获取新消息
├── logout.php          # 登出
├── update_status.php   # 更新状态
├── accept_request.php  # 接受好友请求
├── uploads/            # 文件上传目录
└── README.md           # 项目说明
```

## 安全说明

- 密码使用 PHP 内置的 `password_hash()` 函数进行哈希存储
- 使用 PDO 预处理语句防止 SQL 注入
- 文件上传经过严格的类型和大小验证
- 错误信息不包含敏感内容

## 浏览器支持

- Chrome (推荐)
- Firefox
- Safari
- Edge

## 许可证

MIT License


