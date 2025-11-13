<?php
session_name('CGV_SESSION');
session_start();
require_once '../../admin/config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['register_error'] = "Vui lòng điền đầy đủ thông tin!";
        header("Location: ../../index.php?quanly=dangky");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Mật khẩu không khớp!";
        header("Location: ../../index.php?quanly=dangky");
        exit();
    }
    
    if (strlen($password) < 6) {
        $_SESSION['register_error'] = "Mật khẩu phải có ít nhất 6 ký tự!";
        header("Location: ../../index.php?quanly=dangky");
        exit();
    }
    
    // Kiểm tra email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['register_error'] = "Email đã được sử dụng!";
        header("Location: ../../index.php?quanly=dangky");
        exit();
    }
    
    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm người dùng mới với mật khẩu đã mã hóa
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['register_success'] = "Đăng ký thành công! Bạn có thể đăng nhập.";
        header("Location: ../../index.php?quanly=dangnhap");
        exit();
    } else {
        $_SESSION['register_error'] = "Đăng ký thất bại: " . $conn->error;
        header("Location: ../../index.php?quanly=dangky");
        exit();
    }
}
?>