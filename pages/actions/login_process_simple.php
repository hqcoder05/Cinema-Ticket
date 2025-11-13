<?php
// Đảm bảo không có output trước khi redirect
ob_start();
session_name('CGV_SESSION');
session_start();
require_once '../../admin/config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    
    // Debug
    error_log("Login attempt for email: " . $email);

    // Query đơn giản
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        error_log("Query failed: " . mysqli_error($conn));
        $_SESSION['login_error'] = "Lỗi hệ thống!";
        ob_end_clean();
        header("Location: ../../index.php?quanly=dangnhap");
        exit();
    }

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Kiểm tra mật khẩu (hỗ trợ cả hash và plain text)
        $password_valid = false;
        
        // Kiểm tra password hash
        if (password_verify($password, $row['password'])) {
            $password_valid = true;
        } 
        // Kiểm tra plain text password  
        elseif ($password === $row['password']) {
            $password_valid = true;
        }
        
        if ($password_valid) {
            // Tạo session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['last_activity'] = time();
            
            error_log("Login successful for user: " . $row['email'] . " (ID: " . $row['id'] . ", Role: " . $row['role'] . ")");
            error_log("Session data set - user_id: " . $_SESSION['user_id'] . ", name: " . $_SESSION['name'] . ", role: " . $_SESSION['role']);
            
            // Redirect dựa trên role
            if ($row['role'] == 'admin') {
                error_log("Admin login detected - redirecting to admin panel");
                $_SESSION['login_success'] = "Đăng nhập admin thành công! Chào mừng đến với Admin Panel.";
                
                // Xóa output buffer và redirect
                ob_end_clean();
                header("Location: ../../admin/index.php");
                exit();
            } else {
                ob_end_clean();
                header("Location: ../../index.php");
                exit();
            }
        } else {
            error_log("Invalid password for user: " . $email);
            $_SESSION['login_error'] = "Mật khẩu không đúng!";
        }
    } else {
        error_log("User not found: " . $email);
        $_SESSION['login_error'] = "Email không tồn tại!";
    }
    
    ob_end_clean();
    header("Location: ../../index.php?quanly=dangnhap");
    exit();
}

// Nếu không phải POST
ob_end_clean();
header("Location: ../../index.php?quanly=dangnhap");
exit();
?> 