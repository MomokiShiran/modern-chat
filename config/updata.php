<?php
// updata.php - 用于更新数据库，插入必要的数据

// 启用错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 包含配置文件
require_once 'config.php';
require_once 'db.php';

// 开始时间
$start_time = microtime(true);

// 打印开始信息
function printStartMessage($message) {
    echo "<div style='margin: 10px 0; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;'>";
    echo "<strong>▶ {$message}</strong>"; 
    echo "</div>";
    // 刷新输出缓冲
    ob_flush();
    flush();
}

// 打印成功信息
function printSuccessMessage($message) {
    echo "<div style='margin: 5px 0; padding: 8px 12px; background: #e8f5e8; border-left: 4px solid #4caf50; border-radius: 4px;'>";
    echo "✓ {$message}";
    echo "</div>";
    // 刷新输出缓冲
    ob_flush();
    flush();
}

// 打印错误信息
function printErrorMessage($message) {
    echo "<div style='margin: 5px 0; padding: 8px 12px; background: #ffebee; border-left: 4px solid #f44336; border-radius: 4px;'>";
    echo "✗ {$message}";
    echo "</div>";
    // 刷新输出缓冲
    ob_flush();
    flush();
}

// 打印调试信息
function printDebugMessage($message) {
    echo "<div style='margin: 5px 0; padding: 8px 12px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px; font-family: monospace; font-size: 12px;'>";
    echo "🔍 {$message}";
    echo "</div>";
    // 刷新输出缓冲
    ob_flush();
    flush();
}

// 确保必要的表存在
function ensureTablesExist() {
    global $conn;
    
    printStartMessage("开始创建/检查数据库表...");
    
    try {
        // 检查表的创建顺序，确保依赖关系正确
        $tables = [
            'browser_fingerprints' => "
                CREATE TABLE IF NOT EXISTS browser_fingerprints (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fingerprint VARCHAR(64) NOT NULL, -- 浏览器指纹哈希值
                    ip_address VARCHAR(45) NOT NULL, -- 关联的IP地址
                    user_agent TEXT NOT NULL, -- 用户代理信息
                    screen_resolution VARCHAR(20) DEFAULT NULL, -- 屏幕分辨率
                    time_zone VARCHAR(100) DEFAULT NULL, -- 时区信息
                    language VARCHAR(50) DEFAULT NULL, -- 浏览器语言
                    plugins_count INT DEFAULT NULL, -- 插件数量
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_fingerprint (fingerprint),
                    INDEX idx_ip_address (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'browser_bans' => "
                CREATE TABLE IF NOT EXISTS browser_bans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fingerprint VARCHAR(64) NOT NULL, -- 浏览器指纹哈希值
                    ban_reason VARCHAR(255) NOT NULL DEFAULT '多次登录失败',
                    ban_duration INT NOT NULL, -- 封禁时长（秒）
                    ban_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ban_end TIMESTAMP NULL,
                    status ENUM('active', 'expired') DEFAULT 'active',
                    last_ban_id INT DEFAULT NULL,
                    UNIQUE KEY unique_active_browser_ban (fingerprint, status),
                    INDEX idx_fingerprint_status (fingerprint, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'ip_bans' => "
                CREATE TABLE IF NOT EXISTS ip_bans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    ban_reason VARCHAR(255) NOT NULL DEFAULT '多次登录失败',
                    ban_duration INT NOT NULL, -- 封禁时长（秒）
                    ban_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ban_end TIMESTAMP NULL,
                    status ENUM('active', 'expired') DEFAULT 'active',
                    last_ban_id INT DEFAULT NULL,
                    UNIQUE KEY unique_active_ban (ip_address, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'ip_login_attempts' => "
                CREATE TABLE IF NOT EXISTS ip_login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_successful BOOLEAN DEFAULT FALSE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'scan_login' => "
                CREATE TABLE IF NOT EXISTS scan_login (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    qid VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expire_at TIMESTAMP NOT NULL,
                    token_expire_at TIMESTAMP NOT NULL,
                    qr_content TEXT NOT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_scan_login_qid (qid),
                    INDEX idx_scan_login_status (status),
                    INDEX idx_scan_login_expire (expire_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'forget_password_requests' => "
                CREATE TABLE IF NOT EXISTS forget_password_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    new_password VARCHAR(255) NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    approved_at TIMESTAMP NULL,
                    admin_id INT DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'feedback' => "
                CREATE TABLE IF NOT EXISTS feedback (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    content TEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_feedback_user (user_id),
                    INDEX idx_feedback_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'bans' => "
                CREATE TABLE IF NOT EXISTS bans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    banned_by INT NOT NULL,
                    reason TEXT NOT NULL,
                    ban_duration INT DEFAULT NULL,
                    ban_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ban_end TIMESTAMP NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    INDEX idx_bans_user (user_id),
                    INDEX idx_bans_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'group_bans' => "
                CREATE TABLE IF NOT EXISTS group_bans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    banned_by INT NOT NULL,
                    reason TEXT NOT NULL,
                    ban_duration INT DEFAULT NULL,
                    ban_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ban_end TIMESTAMP NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    INDEX idx_group_bans_group (group_id),
                    INDEX idx_group_bans_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'group_ban_logs' => "
                CREATE TABLE IF NOT EXISTS group_ban_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ban_id INT NOT NULL,
                    action VARCHAR(20) NOT NULL,
                    action_by INT NOT NULL,
                    action_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_group_ban_logs_ban (ban_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'encryption_keys' => "
                CREATE TABLE IF NOT EXISTS encryption_keys (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    public_key TEXT NOT NULL,
                    private_key TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_encryption_keys_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'bans_log' => "
                CREATE TABLE IF NOT EXISTS bans_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ban_id INT NOT NULL,
                    action VARCHAR(20) NOT NULL,
                    action_by INT NOT NULL,
                    action_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_bans_log_ban (ban_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'group_invitations' => "
                CREATE TABLE IF NOT EXISTS group_invitations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    inviter_id INT NOT NULL,
                    invitee_id INT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_group_invitations_group (group_id),
                    INDEX idx_group_invitations_invitee (invitee_id),
                    INDEX idx_group_invitations_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'join_requests' => "
                CREATE TABLE IF NOT EXISTS join_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    user_id INT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_join_requests_group (group_id),
                    INDEX idx_join_requests_user (user_id),
                    INDEX idx_join_requests_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'files' => "
                CREATE TABLE IF NOT EXISTS files (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    file_size INT NOT NULL,
                    file_type VARCHAR(50) NOT NULL,
                    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_files_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'session_keys' => "
                CREATE TABLE IF NOT EXISTS session_keys (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    session_key VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    INDEX idx_session_keys_user (user_id),
                    INDEX idx_session_keys_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'recall_messages' => "
                CREATE TABLE IF NOT EXISTS recall_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    message_id INT NOT NULL,
                    message_type VARCHAR(20) NOT NULL,
                    recalled_by INT NOT NULL,
                    recalled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_recall_messages_message (message_id),
                    INDEX idx_recall_messages_type (message_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'reports' => "
                CREATE TABLE IF NOT EXISTS reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reporter_id INT NOT NULL,
                    reported_user_id INT NOT NULL,
                    report_type VARCHAR(20) NOT NULL,
                    reason TEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_reports_reporter (reporter_id),
                    INDEX idx_reports_reported (reported_user_id),
                    INDEX idx_reports_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'notices' => "
                CREATE TABLE IF NOT EXISTS notices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE,
                    INDEX idx_notices_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'activity_logs' => "
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    target_type VARCHAR(50) DEFAULT NULL,
                    target_id INT DEFAULT NULL,
                    target_name VARCHAR(255) DEFAULT NULL,
                    ip_address VARCHAR(45) DEFAULT NULL,
                    browser_info TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_activity_logs_user (user_id),
                    INDEX idx_activity_logs_action (action),
                    INDEX idx_activity_logs_target (target_type, target_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'friends' => "
                CREATE TABLE IF NOT EXISTS friends (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    friend_id INT NOT NULL,
                    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_friends_user (user_id),
                    INDEX idx_friends_friend (friend_id),
                    INDEX idx_friends_status (status),
                    UNIQUE KEY unique_friendship (user_id, friend_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'messages' => "
                CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sender_id INT NOT NULL,
                    receiver_id INT NOT NULL,
                    content TEXT,
                    file_path VARCHAR(255),
                    file_name VARCHAR(255),
                    file_size INT,
                    file_type VARCHAR(50),
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_messages_sender (sender_id),
                    INDEX idx_messages_receiver (receiver_id),
                    INDEX idx_messages_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'sessions' => "
                CREATE TABLE IF NOT EXISTS sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    friend_id INT NOT NULL,
                    last_message_id INT DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_sessions_user (user_id),
                    INDEX idx_sessions_friend (friend_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            "
        ];
        
        // 逐个创建表
        foreach ($tables as $table_name => $create_sql) {
            $table_start_time = microtime(true);
            
            try {
                $conn->exec($create_sql);
                $table_end_time = microtime(true);
                $table_time = number_format(($table_end_time - $table_start_time) * 1000, 2);
                printSuccessMessage("表 {$table_name} 创建/检查成功！ ({$table_time} ms)");
            } catch (PDOException $e) {
                printErrorMessage("表 {$table_name} 创建/检查失败: " . $e->getMessage());
                return false;
            }
        }
        
        // 创建索引（使用兼容旧版MySQL的方式）
        printDebugMessage("开始创建索引...");
        
        // 索引定义
        $indexes = [
            ["idx_ip_login_attempts_ip", "ip_login_attempts", "ip_address"],
            ["idx_ip_login_attempts_time", "ip_login_attempts", "attempt_time"]
        ];
        
        foreach ($indexes as $index_info) {
            list($index_name, $table_name, $column_name) = $index_info;
            
            try {
                // 检查索引是否存在
                $stmt = $conn->prepare("SHOW INDEX FROM {$table_name} WHERE Key_name = ?");
                $stmt->execute([$index_name]);
                $index_exists = $stmt->fetch();
                
                if (!$index_exists) {
                    // 索引不存在，创建索引
                    $conn->exec("CREATE INDEX {$index_name} ON {$table_name}({$column_name})");
                    printSuccessMessage("索引 {$index_name} 创建成功！");
                } else {
                    printSuccessMessage("索引 {$index_name} 已存在，跳过创建");
                }
            } catch (PDOException $e) {
                printErrorMessage("索引 {$index_name} 创建/检查失败: " . $e->getMessage());
                // 索引创建失败不影响整体流程，继续执行
            }
        }
        
        printSuccessMessage("所有必要的表已创建或已存在！");
        return true;
        
    } catch (PDOException $e) {
        printErrorMessage("创建表失败: " . $e->getMessage());
        printErrorMessage("完整错误信息: " . $e->getFile() . " (" . $e->getLine() . "): " . $e->getMessage());
        return false;
    }
}

// 插入示例数据
function insertSampleData() {
    global $conn;
    
    printStartMessage("开始插入示例数据...");
    
    try {
        // 检查是否需要插入示例数据
        printDebugMessage("检查管理员用户是否存在...");
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'Admin'");
        $stmt->execute();
        $admin_count = $stmt->fetch()['count'];
        
        if ($admin_count === 0) {
            // 插入管理员用户
            $admin_password = password_hash('cf211396ab9363ad', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin, is_deleted, agreed_to_terms) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['Admin', 'admin@example.com', $admin_password, 1, 0, 1]);
            printSuccessMessage("已插入管理员用户！用户名: Admin, 密码: cf211396ab9363ad");
        } else {
            printSuccessMessage("管理员用户已存在，跳过插入");
        }
        
        // 检查配置文件
        printDebugMessage("检查配置文件...");
        $config_file = 'config/config.json';
        if (file_exists($config_file)) {
            $config_data = json_decode(file_get_contents($config_file), true);
            
            // 确保必要的配置项存在
            $required_configs = [
                'Number_of_incorrect_password_attempts' => 10,
                'Limit_login_duration' => 24
            ];
            
            $config_updated = false;
            foreach ($required_configs as $key => $default_value) {
                if (!isset($config_data[$key])) {
                    $config_data[$key] = $default_value;
                    $config_updated = true;
                    printDebugMessage("添加配置项: {$key} = {$default_value}");
                }
            }
            
            if ($config_updated) {
                file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT));
                printSuccessMessage("已更新配置文件，添加了必要的配置项");
            } else {
                printSuccessMessage("配置文件已包含所有必要的配置项");
            }
        } else {
            // 创建配置文件
            $default_config = [
                'Create_a_group_chat_for_all_members' => true,
                'Restrict_registration' => true,
                'Restrict_registration_ip' => 3,
                'ban_system' => true,
                'user_name_max' => 12,
                'upload_files_max' => 150,
                'Session_Duration' => 1,
                'Number_of_incorrect_password_attempts' => 10,
                'Limit_login_duration' => 24,
                'email_verify' => false,
                'email_verify_api' => 'https://api.nbhao.org/v1/email/verify',
                'email_verify_api_Request' => 'POST',
                'email_verify_api_Verify_parameters' => 'result'
            ];
            
            // 确保config目录存在
            if (!is_dir('config')) {
                mkdir('config', 0755, true);
                printSuccessMessage("已创建config目录");
            }
            
            file_put_contents($config_file, json_encode($default_config, JSON_PRETTY_PRINT));
            printSuccessMessage("已创建配置文件，添加了默认配置");
        }
        
        printSuccessMessage("示例数据插入完成！");
        return true;
        
    } catch (PDOException $e) {
        printErrorMessage("插入示例数据失败: " . $e->getMessage());
        printErrorMessage("完整错误信息: " . $e->getFile() . " (" . $e->getLine() . "): " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        printErrorMessage("配置文件操作失败: " . $e->getMessage());
        printErrorMessage("完整错误信息: " . $e->getFile() . " (" . $e->getLine() . "): " . $e->getMessage());
        return false;
    }
}

// 主函数
function main() {
    // 设置HTML头
    echo "<!DOCTYPE html>";
    echo "<html lang='zh-CN'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>数据库更新脚本</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; color: #333; }";
    echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }";
    echo "h1, h2 { color: #2196f3; }";
    echo ".header { background: #2196f3; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }";
    echo ".footer { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; text-align: center; font-size: 14px; color: #666; }";
    echo ".time-info { font-size: 14px; color: #666; margin: 10px 0; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container'>";
    echo "<div class='header'>";
    echo "<h1>数据库更新脚本</h1>";
    echo "<p>执行时间: " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
    
    echo "<h2>执行日志</h2>";
    
    // 确保必要的表存在
    if (ensureTablesExist()) {
        // 插入示例数据
        if (insertSampleData()) {
            echo "<div style='margin: 20px 0; padding: 15px; background: #e8f5e8; border-left: 4px solid #4caf50; border-radius: 4px;'>";
            echo "<h3 style='color: #4caf50; margin-top: 0;'>✅ 数据库更新成功！</h3>";
            echo "<p>所有必要的表已创建或已存在。</p>";
            echo "<p>示例数据已插入或已存在。</p>";
            echo "<p>配置文件已更新或已包含所有必要的配置项。</p>";
            echo "<p><strong>您可以关闭此页面并继续使用聊天系统。</strong></p>";
            echo "</div>";
        } else {
            echo "<div style='margin: 20px 0; padding: 15px; background: #ffebee; border-left: 4px solid #f44336; border-radius: 4px;'>";
            echo "<h3 style='color: #f44336; margin-top: 0;'>❌ 数据库更新失败！</h3>";
            echo "<p>示例数据插入失败，请检查错误信息并尝试修复。</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='margin: 20px 0; padding: 15px; background: #ffebee; border-left: 4px solid #f44336; border-radius: 4px;'>";
        echo "<h3 style='color: #f44336; margin-top: 0;'>❌ 数据库表创建失败！</h3>";
        echo "<p>表创建失败，请检查错误信息并尝试修复。</p>";
        echo "</div>";
    }
    
    // 计算执行时间
    global $start_time;
    $end_time = microtime(true);
    $total_time = number_format(($end_time - $start_time), 2);
    
    echo "<div class='footer'>";
    echo "<p class='time-info'>执行完成！总执行时间: {$total_time} 秒</p>";
    echo "<p>脚本版本: 1.0.0</p>";
    echo "</div>";
    
    echo "</div>";
    echo "</body>";
    echo "</html>";
}

// 执行主函数
main();
?>