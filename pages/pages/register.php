<link rel="stylesheet" type="text/css" href="/BookingsTickets/css/style.css">
<link rel="stylesheet" type="text/css" href="/BookingsTickets/css/register-form.css">

<?php
if (isset($_SESSION['register_error'])) {
    echo '<div class="error">' . htmlspecialchars($_SESSION['register_error']) . '</div>';
    unset($_SESSION['register_error']);
}
?>

<div class="register">
    <form class="register-form" id="registerForm" action="/BookingsTickets/pages/actions/register_process.php" method="post">
        <span>Đăng ký CGV</span>
        
        <div class="form-group">
            <input type="text" name="name" id="name" placeholder="Họ và tên" required autocomplete="name">
        </div>
        
        <div class="form-group">
            <input type="email" name="email" id="email" placeholder="Email của bạn" required autocomplete="email">
        </div>
        
        <div class="form-group">
            <input type="password" name="password" id="password" placeholder="Mật khẩu" required autocomplete="new-password">
            <div class="password-strength" id="passwordStrength">
                <div class="strength-bar">
                    <div class="strength-fill"></div>
                </div>
                <span class="strength-text"></span>
            </div>
        </div>
        
        <div class="form-group">
            <input type="password" name="confirm_password" id="confirmPassword" placeholder="Xác nhận mật khẩu" required autocomplete="new-password">
        </div>
        
        <button type="submit" id="registerBtn">
            <span class="btn-text">Đăng ký</span>
        </button>
        
        <p>Đã có tài khoản? <a href="index.php?quanly=dangnhap">Đăng nhập ngay</a></p>
    </form>
</div>



