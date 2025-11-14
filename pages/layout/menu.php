<div class="menu">
    <div class="menu-left">
        <div class="logo">
            <a href="index.php"><img src="https://www.cgv.vn/skin/frontend/cgv/default/images/cgvlogo.png" alt="logo"></a>
        </div>
    </div>
    <div class="menu-right">
        <ul class="list-menu">
                <li><a href="index.php?quanly=phim">Phim</a></li>
                <li><a href="index.php?quanly=rap">Rạp</a></li>
                <?php
                if (isset($_SESSION['user_id'])) {
                    // Đã đăng nhập
                    echo '<li><a href="index.php?quanly=lich-su-dat-ve">Lịch sử đặt vé</a></li>';
                    
                    // Hiển thị link admin nếu là admin
                    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
                        echo '<li><a href="admin/index.php" style="color: #e71a0f; font-weight: bold;">👑 Admin Panel</a></li>';
                    }
                    
                    echo '<li><a href="pages/actions/logout_process.php">Đăng xuất</a></li>';
                    echo '<li><span style="color:#e71a0f; font-weight: bold;">👤 Xin chào, ' . htmlspecialchars($_SESSION['name'] ?? 'User') . '</span></li>';
                } else {
                    // Chưa đăng nhập
                    echo '<li><a href="index.php?quanly=dangnhap">Đăng nhập</a></li>';
                }
                ?>
        </ul>
    </div>
</div>
<div class="menu-line"></div>
<div class="clear"></div>