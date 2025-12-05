<?php
require_once 'db.php';

// 获取用户IP地址
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// 生成唯一的qid
function generateQid() {
    return uniqid('scan_', true) . rand(1000, 9999);
}

// 主处理逻辑
// 首先检查是否是检查登录状态的请求
if (isset($_GET['check_status'])) {
    // 检查登录状态
    $qid = isset($_GET['qid']) ? $_GET['qid'] : '';
    
    if (empty($qid)) {
        echo json_encode(['success' => false, 'message' => '参数错误']);
        exit;
    }
    
    try {
        // 检查登录状态
        $sql = "SELECT * FROM scan_login WHERE qid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$qid]);
        $scan_record = $stmt->fetch();
        
        if (!$scan_record) {
            echo json_encode(['status' => 'expired', 'message' => '二维码已过期']);
            exit;
        }
        
        // 检查是否过期
        if (strtotime($scan_record['expire_at']) < time()) {
            // 更新为过期状态
            $sql = "UPDATE scan_login SET status = 'expired' WHERE qid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$qid]);
            echo json_encode(['status' => 'expired', 'message' => '二维码已过期']);
        } elseif ($scan_record['status'] === 'success') {
            // 登录成功，生成临时token
            $user_id = $scan_record['user_id'];
            // 生成唯一的token，有效期5分钟
            $token = bin2hex(random_bytes(32));
            $token_expire_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // 更新记录状态为success并存储token
            $sql = "UPDATE scan_login SET status = 'success', token = ?, token_expire_at = ? WHERE qid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$token, $token_expire_at, $qid]);
            
            // 返回token而不是直接返回user_id
            echo json_encode(['status' => 'success', 'token' => $token, 'message' => '登录成功']);
            
            // 注意：这里只更新状态，不立即删除记录
            // 实际删除在PC端登录成功后进行，确保PC端能获取到token
        } else {
            // 等待扫描
            echo json_encode(['status' => 'pending', 'message' => '等待扫描', 'debug' => '当前状态: ' . $scan_record['status']]);
        }
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => '检查登录状态失败: ' . $e->getMessage()]);
    }
} 
// 然后检查是否是POST请求（处理手机端登录）
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理手机端登录请求
    $qid = isset($_POST['qid']) ? $_POST['qid'] : '';
    $user = isset($_POST['user']) ? $_POST['user'] : '';
    $source = isset($_POST['source']) ? $_POST['source'] : '';
    
    // 验证参数
    if (empty($qid) || empty($user) || empty($source)) {
        echo json_encode(['success' => false, 'message' => '参数错误: ' . $qid . ' ' . $user . ' ' . $source]);
        exit;
    }
    
    // 验证来源是否为mobilechat.php
    if ($source !== 'mobilechat.php') {
        echo json_encode(['success' => false, 'message' => '非法请求来源: ' . $source]);
        exit;
    }
    
    // 使用user参数作为用户名
    $username = $user;
    
    try {
        // 检查二维码是否存在且未过期
        $sql = "SELECT * FROM scan_login WHERE qid = ? AND status = 'pending' AND expire_at > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$qid]);
        $scan_record = $stmt->fetch();
        
        if (!$scan_record) {
            echo json_encode(['success' => false, 'message' => '二维码已过期或无效']);
            exit;
        }
        
        // 获取用户ID
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $user_data = $stmt->fetch();
        
        if (!$user_data) {
            echo json_encode(['success' => false, 'message' => '用户不存在: ' . $username]);
            exit;
        }
        
        $user_id = $user_data['id'];
        
        // 更新扫码登录状态
            $sql = "UPDATE scan_login SET status = 'success', user_id = ?, login_source = ? WHERE qid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $source, $qid]);
            
            echo json_encode(['success' => true, 'message' => '登录成功', 'debug' => 'qid: ' . $qid . ', user: ' . $user . ', user_id: ' . $user_id]);
            
            // 移动端确认登录后删除数据库记录，避免重复使用
            // 注意：这里只更新状态，实际删除在PC端登录成功后进行
            // 这样确保PC端能获取到token
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => '登录失败: ' . $e->getMessage()]);
    }
} 
// 最后检查是否是生成二维码的GET请求
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 生成二维码和qid
    $qid = generateQid();
    $ip_address = getUserIP();
    $expire_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    try {
        // 插入数据库
        $sql = "INSERT INTO scan_login (qid, expire_at, ip_address, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$qid, $expire_at, $ip_address]);
        
        // 生成二维码内容（临时链接）
        $domain = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $qr_content = "$protocol://$domain/chat/scan_login.php?qid=$qid";
        
        // 返回qid和二维码内容
        $response = [
            'success' => true,
            'qid' => $qid,
            'qr_content' => $qr_content,
            'expire_at' => $expire_at
        ];
        echo json_encode($response);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => '生成登录二维码失败']);
    }
} else {
    // 其他请求返回错误
    echo json_encode(['success' => false, 'message' => '非法访问']);
}
?>