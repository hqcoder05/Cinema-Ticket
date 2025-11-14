<link rel="stylesheet" type="text/css" href="/BookingsTickets/css/style.css">
<link rel="stylesheet" type="text/css" href="/BookingsTickets/css/login-form.css">

<?php
if (isset($_SESSION['login_error'])) {
    echo '<div class="error">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
    unset($_SESSION['login_error']);
}
if (isset($_SESSION['register_success'])) {
    echo '<div class="success">' . htmlspecialchars($_SESSION['register_success']) . '</div>';
    unset($_SESSION['register_success']);
}

// Hiển thị thông báo đặc biệt cho admin
if (isset($_GET['admin_required'])) {
    echo '<div class="info" style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #bee5eb;">';
    echo '<i class="fas fa-info-circle"></i> <strong>Yêu cầu đăng nhập Admin:</strong> Vui lòng đăng nhập bằng tài khoản admin để truy cập Admin Panel.';
    echo '</div>';
}

if (isset($_GET['timeout'])) {
    echo '<div class="warning" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #ffeaa7;">';
    echo '<i class="fas fa-clock"></i> <strong>Phiên đăng nhập đã hết hạn!</strong> Vui lòng đăng nhập lại.';
    echo '</div>';
}
?>

<div class="login">
    <form class="login-form" action="pages/actions/login_process_simple.php" method="post">
        <span>Đăng nhập CGV</span>
        
        <div class="form-group">
            <input type="email" name="email" placeholder="Email của bạn" required>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Mật khẩu" required>
        </div>
        
        <button type="submit">Đăng nhập</button>
        
        <p>Bạn chưa có tài khoản? <a href="index.php?quanly=dangky">Đăng ký ngay</a></p>
    </form>
</div>
