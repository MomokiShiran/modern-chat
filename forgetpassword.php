<?php
// 启用错误报告以便调试
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 设置错误日志
ini_set('error_log', 'error.log');

require_once 'db.php';

$error_message = '';
$success_message = '';

// 处理忘记密码申请
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // 验证表单数据
    if (empty($username) || empty($email) || empty($new_password) || empty($confirm_password)) {
        $error_message = '请填写所有必填字段';
    } elseif ($new_password !== $confirm_password) {
        $error_message = '两次输入的密码不一致';
    } else {
        // 检查密码复杂度
        $complexity = 0;
        if (preg_match('/[a-z]/', $new_password)) $complexity++;
        if (preg_match('/[A-Z]/', $new_password)) $complexity++;
        if (preg_match('/\d/', $new_password)) $complexity++;
        if (preg_match('/[^a-zA-Z0-9]/', $new_password)) $complexity++;
        
        if ($complexity < 2) {
            $error_message = '密码不符合安全要求，请包含至少2种字符类型（大小写字母、数字、特殊符号）';
        } else {
            // 检查用户名和邮箱是否匹配
            try {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
                $stmt->execute([$username, $email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $error_message = '用户名和邮箱不匹配';
                } else {
                    // 检查是否已经有待处理的申请
                    $stmt = $conn->prepare("SELECT id FROM forget_password_requests WHERE username = ? AND status = 'pending'");
                    $stmt->execute([$username]);
                    $existing_request = $stmt->fetch();
                    
                    if ($existing_request) {
                        $error_message = '您已经提交了忘记密码申请，请等待管理员审核';
                    } else {
                        // 加密新密码
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // 插入忘记密码申请
                        $stmt = $conn->prepare("INSERT INTO forget_password_requests (username, email, new_password) VALUES (?, ?, ?)");
                        $stmt->execute([$username, $email, $hashed_password]);
                        
                        $success_message = '忘记密码申请已提交，请等待管理员审核';
                    }
                }
            } catch (PDOException $e) {
                error_log("Forgot password request error: " . $e->getMessage());
                $error_message = '提交失败，请稍后重试';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>忘记密码 - Modern Chat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            text-align: center;
            color: #667eea;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee;
            color: #d32f2f;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fcc;
        }
        
        .success-message {
            background: #e8f5e8;
            color: #388e3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #c8e6c9;
        }
        
        .password-requirements {
            margin-top: 8px;
            color: #888;
            font-size: 12px;
            margin-bottom: 20px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>忘记密码</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form action="forgetpassword.php" method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">绑定邮箱</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">新密码</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">确认新密码</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <p class="password-requirements">密码必须包含至少2种字符类型（大小写字母、数字、特殊符号）</p>
            
            <button type="submit" class="btn">提交申请</button>
        </form>
        
        <div class="back-link">
            <a href="login.php">返回登录页面</a>
        </div>
    </div>
</body>
</html>
