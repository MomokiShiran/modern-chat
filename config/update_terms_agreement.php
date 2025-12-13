<?php
// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 设置响应头为JSON
header('Content-Type: application/json');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效的请求方法']);
    exit;
}

// 检查是否有agreed参数
if (!isset($_POST['agreed'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要参数']);
    exit;
}

// 包含数据库连接和User类
require_once 'db.php';
require_once 'User.php';

// 创建User实例
$user = new User($conn);

// 获取当前用户ID
$user_id = $_SESSION['user_id'];

try {
    // 更新用户的协议同意状态
    // 检查$_POST['agreed']值，仅当为'1'或true时更新
    $agreed = ($_POST['agreed'] === '1' || $_POST['agreed'] === true) ? 1 : 0;
    $sql = "UPDATE users SET agreed_to_terms = :agreed, terms_agreed_at = NOW() WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':agreed' => $agreed, ':user_id' => $user_id]);
    $result = ($stmt->rowCount() > 0);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '协议同意状态更新成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失败，请稍后重试']);
    }
} catch (PDOException $e) {
    // 数据库错误
    error_log('更新协议同意状态失败: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '服务器内部错误']);
}
?>