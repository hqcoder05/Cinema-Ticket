<?php
// Cấu hình session với bảo mật cao cho admin
if (session_status() == PHP_SESSION_NONE) {
    // Cấu hình session timeout ngắn hơn cho admin
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        ini_set('session.gc_maxlifetime', 600); // 10 phút cho admin
    } else {
        ini_set('session.gc_maxlifetime', 1800); // 30 phút cho user thường
    }
    
    // Chỉ sử dụng cookies (không dùng URL rewriting)
    ini_set('session.use_only_cookies', 1);
    
    // Bảo mật session cao
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set thành 1 nếu dùng HTTPS
    ini_set('session.cookie_samesite', 'Strict'); // Strict cho admin
    
    // Session cookie sẽ bị xóa khi đóng browser (session cookie)
    ini_set('session.cookie_lifetime', 0);
    
    // Regenerate session ID thường xuyên cho admin
    ini_set('session.use_strict_mode', 1);
    
    // Tên session
    session_name('CGV_SESSION');
    
    session_start();
    
    // Regenerate session ID cho admin để tăng bảo mật
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        if (!isset($_SESSION['admin_session_regenerated']) || 
            (time() - $_SESSION['admin_session_regenerated']) > 300) { // 5 phút
            session_regenerate_id(true);
            $_SESSION['admin_session_regenerated'] = time();
        }
    }
    
    // Cấu hình timeout khác nhau cho admin và user
    $timeout_duration = 3600; // 60 phút mặc định (tăng lên)
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        $timeout_duration = 1800; // 30 phút cho admin (tăng lên)
    }
    
    // Chỉ kiểm tra timeout nếu đã có session user_id (đã đăng nhập)
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Session hết hạn, đăng xuất
        session_unset();
        session_destroy();
        // Khởi tạo session mới
        session_start();
        // Chuyển hướng về trang đăng nhập chính nếu đang ở admin area
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
            header('Location: ../index.php?quanly=dangnhap&timeout=1&admin_required=1');
            exit();
        }
    } else {
        // Cập nhật last_activity cho session hợp lệ hoặc session mới
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = time();
        }
    }
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'phimchill');

// Kết nối đến database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if($conn === false){
    die("ERROR: Không thể kết nối. " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');
?> 