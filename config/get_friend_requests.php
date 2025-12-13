<?php
// 启动会话
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([]);
    exit;
}

// 引入必要的文件
require_once 'db.php';
require_once 'Friend.php';

// 获取当前用户ID
$user_id = $_SESSION['user_id'];

// 创建Friend实例
$friend = new Friend($conn);

// 获取待处理的好友请求
$pending_requests = $friend->getPendingRequests($user_id);

// 返回JSON响应
header('Content-Type: application/json');
echo json_encode($pending_requests);
