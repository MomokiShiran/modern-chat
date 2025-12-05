<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - Modern Chat</title>
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
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* 注册链接样式 - 与helper-links保持一致 */
        .register-link {
            color: #666;
            font-size: 14px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
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
        
        /* 登录选项切换 */
        .login-options {
            display: flex;
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }
        
        .login-option {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            background: #fafafa;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #666;
        }
        
        .login-option.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        /* 登录方式容器 */
        .login-method {
            display: none;
        }
        
        .login-method.active {
            display: block;
        }
        
        /* 二维码样式 */
        .qr-container {
            text-align: center;
            margin: 20px 0;
        }
        
        #qr-code {
            display: inline-block;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .qr-info {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .countdown {
            font-weight: 600;
            color: #667eea;
        }
        
        /* 状态提示 */
        .status-message {
            margin: 15px 0;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }
        
        .status-pending {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        
        .status-scanning {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffe0b2;
        }
        
        .status-success {
            background: #e8f5e8;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }
        
        .helper-links {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #666;
        }
        
        .helper-links a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .helper-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        @media (max-width: 500px) {
            .container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .helper-links {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>登录</h1>
        
        <?php
        // 连接数据库
        require_once 'db.php';
        
        if (isset($_GET['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        if (isset($_GET['success'])) {
            echo '<div class="success-message">' . htmlspecialchars($_GET['success']) . '</div>';
        }
        
        // 忘记密码申请状态提示
        $password_request_message = '';
        
        // 检查是否有邮箱参数，用于显示忘记密码申请状态
        if (isset($_GET['email'])) {
            $email = urldecode($_GET['email']);
            
            // 获取用户的忘记密码申请状态
            try {
                // 先通过邮箱获取用户名
                $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $username = $user['username'];
                    
                    // 查询最新的忘记密码申请
                    $stmt = $conn->prepare("SELECT status FROM forget_password_requests WHERE username = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$username]);
                    $request = $stmt->fetch();
                    
                    if ($request) {
                        switch ($request['status']) {
                            case 'approved':
                                $password_request_message = '您的修改密码申请已通过，请使用新密码登录';
                                $message_type = 'success';
                                break;
                            case 'rejected':
                                $password_request_message = '您的修改密码申请无法通过';
                                $message_type = 'error';
                                break;
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Check password request status error: " . $e->getMessage());
            }
        }
        
        // 显示忘记密码申请状态提示
        if (!empty($password_request_message)) {
            $message_class = $message_type === 'error' ? 'error-message' : 'success-message';
            echo '<div class="' . $message_class . '">' . $password_request_message . '</div>';
        }
        ?>
        
        <?php
        // 检测设备类型
        function isMobileDevice() {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $mobileAgents = array('Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone', 'Mobile', 'Opera Mini', 'Fennec', 'IEMobile');
            foreach ($mobileAgents as $agent) {
                if (stripos($userAgent, $agent) !== false) {
                    return true;
                }
            }
            return false;
        }
        
        $is_mobile = isMobileDevice();
        ?>
        
        <!-- 登录选项 -->
        <div class="login-options">
            <div class="login-option active" data-method="password">密码登录</div>
            <?php if (!$is_mobile) { ?>
            <div class="login-option" data-method="scan">扫码登录</div>
            <?php } ?>
        </div>
        
        <!-- 密码登录 -->
        <div class="login-method active" id="password-login">
            <form action="login_process.php" method="POST">
                <div class="form-group">
                    <label for="email">邮箱</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">登录</button>
            </form>
            
            <div class="helper-links">
                <div class="forget-password">
                    忘记密码？ <a href="forgetpassword.php">点击这里</a>
                </div>
                
                <div class="register-link">
                    还没有账户？ <a href="register.php">立即注册</a>
                </div>
            </div>
        </div>
        
        <!-- 扫码登录（仅在PC端显示） -->
        <?php if (!$is_mobile) { ?>
        <div class="login-method" id="scan-login">
            <div class="qr-container">
                <div id="qr-code"></div>
                <div class="qr-info">
                    <p>使用手机APP扫描二维码登录</p>
                    <p>有效期 <span class="countdown" id="countdown">5:00</span></p>
                </div>
                <div class="status-message status-pending" id="status-message">
                    等待扫描...
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
        <script>
        // 登录选项切换
        document.querySelectorAll('.login-option').forEach(option => {
            option.addEventListener('click', () => {
                const method = option.dataset.method;
                
                // 更新选项状态
                document.querySelectorAll('.login-option').forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                
                // 更新登录方式显示
                document.querySelectorAll('.login-method').forEach(methodEl => methodEl.classList.remove('active'));
                document.getElementById(method + '-login').classList.add('active');
                
                // 如果切换到扫码登录，初始化二维码
                if (method === 'scan') {
                    initScanLogin();
                }
            });
        });
        
        // 扫码登录初始化
        let checkInterval;
        let countdownInterval;
        let currentQid;
        
        function initScanLogin() {
            // 清除之前的定时器
            if (checkInterval) clearInterval(checkInterval);
            if (countdownInterval) clearInterval(countdownInterval);
            
            // 获取二维码
            fetch('scan_login.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentQid = data.qid;
                        
                        // 生成二维码（使用qrcode.js库，无需外部API）
                        const qrCode = document.getElementById('qr-code');
                        qrCode.innerHTML = '<canvas id="qr-canvas"></canvas>';
                        const canvas = document.getElementById('qr-canvas');
                        
                        QRCode.toCanvas(canvas, data.qr_content, {
                            width: 200,
                            margin: 1,
                            color: {
                                dark: '#000000',
                                light: '#ffffff'
                            }
                        }, function(error) {
                            if (error) {
                                console.error('生成二维码失败:', error);
                                qrCode.innerHTML = '<p style="color: #ff4757;">生成二维码失败，请重试</p>';
                            }
                        });
                        
                        // 初始化倒计时
                        startCountdown();
                        
                        // 开始检查登录状态
                        checkLoginStatus();
                    }
                })
                .catch(error => {
                    console.error('获取二维码失败:', error);
                    document.getElementById('status-message').textContent = '生成二维码失败，请重试';
                    document.getElementById('status-message').className = 'status-message error-message';
                });
        }
        
        // 倒计时
        function startCountdown() {
            let seconds = 300; // 5分钟
            const countdownEl = document.getElementById('countdown');
            
            countdownInterval = setInterval(() => {
                seconds--;
                const minutes = Math.floor(seconds / 60);
                const secs = seconds % 60;
                countdownEl.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    // 刷新二维码
                    initScanLogin();
                }
            }, 1000);
        }
        
        // 检查登录状态
        function checkLoginStatus() {
            checkInterval = setInterval(() => {
                fetch(`scan_login.php?check_status=true&qid=${currentQid}`)
                    .then(response => response.json())
                    .then(data => {
                        const statusMsg = document.getElementById('status-message');
                        
                        // 调试信息
                        console.log('登录状态检查结果:', data);
                        
                        if (data.status === 'success') {
                            // 登录成功，跳转到login_process.php，使用token而不是直接使用user_id
                            console.log('登录成功，跳转到:', `login_process.php?scan_login=true&token=${data.token}`);
                            window.location.href = `login_process.php?scan_login=true&token=${data.token}`;
                        } else if (data.status === 'expired') {
                            // 二维码过期，刷新
                            clearInterval(checkInterval);
                            statusMsg.textContent = '二维码已过期，正在刷新...';
                            statusMsg.className = 'status-message status-pending';
                            setTimeout(initScanLogin, 1000);
                        } else if (data.status === 'error') {
                            // 错误状态
                            statusMsg.textContent = '检查登录状态失败: ' + (data.message || '未知错误');
                            statusMsg.className = 'status-message status-error';
                        } else {
                            // 等待扫描
                            statusMsg.textContent = '等待扫描...';
                            statusMsg.className = 'status-message status-pending';
                        }
                    })
                    .catch(error => {
                        console.error('检查登录状态失败:', error);
                        const statusMsg = document.getElementById('status-message');
                        statusMsg.textContent = '网络错误，检查登录状态失败';
                        statusMsg.className = 'status-message status-error';
                    });
            }, 1000); // 每1秒检查一次，提高响应速度
        }
        
        // 页面加载完成后，如果扫码登录是默认选项，初始化二维码
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('scan-login').classList.contains('active')) {
                initScanLogin();
            }
        });
    </script>
</body>
</html>