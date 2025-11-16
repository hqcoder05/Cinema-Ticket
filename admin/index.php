<?php
require_once 'config/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php?quanly=dangnhap&admin_required=1');
    exit();
}

// Kiểm tra session timeout (chỉ áp dụng nếu không có "remember me")
if (!isset($_SESSION['remember_me']) || $_SESSION['remember_me'] !== true) {
    $timeout_duration = 7200; // 2 giờ (tăng từ 1 giờ lên 2 giờ cho admin)
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout_duration) {
            // Session hết hạn, đăng xuất
            session_unset();
            session_destroy();
            header('Location: login.php?timeout=1');
            exit();
        }
    }
}

// Cập nhật last activity
$_SESSION['last_activity'] = time();

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGV Admin - Quản trị hệ thống</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-left">
            <h1><i class="fas fa-film"></i> CGV Admin</h1>
        </div>
        <div class="header-right">
            <div class="admin-info">
                <i class="fas fa-user-shield"></i>
                <span>Xin chào, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <small style="display: block; font-size: 11px; opacity: 0.8;">
                    🔒 Session timeout: 2 giờ | 🔄 Auto refresh activity
                </small>
            </div>
            <a href="../pages/actions/logout_process.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item <?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                        <a href="?page=dashboard" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'movies' ? 'active' : ''; ?>">
                        <a href="?page=movies" class="nav-link">
                            <i class="fas fa-film"></i>
                            <span>Quản lý Phim</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'combos' ? 'active' : ''; ?>">
                        <a href="?page=combos" class="nav-link">
                            <i class="fas fa-cocktail"></i>
                            <span>Quản lý Combo</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'theaters' ? 'active' : ''; ?>">
                        <a href="?page=theaters" class="nav-link">
                            <i class="fas fa-building"></i>
                            <span>Quản lý Rạp</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'showtimes' ? 'active' : ''; ?>">
                        <a href="?page=showtimes" class="nav-link">
                            <i class="fas fa-clock"></i>
                            <span>Lịch Chiếu</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'bookings' ? 'active' : ''; ?>">
                        <a href="?page=bookings" class="nav-link">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Đặt Vé</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'users' ? 'active' : ''; ?>">
                        <a href="?page=users" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Người Dùng</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $page == 'admins' ? 'active' : ''; ?>">
                        <a href="?page=admins" class="nav-link">
                            <i class="fas fa-user-shield"></i>
                            <span>Quản Trị Viên</span>
                        </a>
                    </li>
            </ul>
                
                <div class="sidebar-footer">
                    <a href="../index.php" class="back-to-site">
                        <i class="fas fa-home"></i>
                        <span>Về trang chủ</span>
                    </a>
        </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="content-wrapper">
            <?php
            switch($page) {
                case 'movies':
                        include 'pages/admin_movies.php';
                    break;
                        
                case 'theaters':
                        include 'pages/admin_theaters.php';
                    break;
                        
                case 'showtimes':
                        include 'pages/admin_showtimes.php';
                    break;
                        
                case 'bookings':
                        include 'pages/admin_bookings.php';
                    break;
                        
                case 'users':
                        include 'pages/admin_users.php';
                    break;
                        
                case 'combos':
                        include 'pages/admin_combos.php';
                    break;
                        
                    case 'admins':
                        include 'pages/admin_admins.php';
                    break;
                        
                default:
                    // Dashboard
                        echo '<div class="page-header">';
                        echo '<h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>';
                        echo '<p class="page-subtitle">Tổng quan hệ thống quản lý CGV</p>';
                    echo '</div>';
                    
                    // Hiển thị thông báo đăng nhập thành công
                    if (isset($_SESSION['login_success'])) {
                        echo '<div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">';
                        echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['login_success']);
                        echo '</div>';
                        unset($_SESSION['login_success']); // Xóa thông báo sau khi hiển thị
                    }
                    
                    // Thông báo chào mừng admin
                    echo '<div class="welcome-admin" style="background: linear-gradient(135deg, #e50914, #ff6b6b); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center;">';
                    echo '<h3 style="margin: 0 0 10px 0;"><i class="fas fa-crown"></i> Chào mừng Admin ' . htmlspecialchars($_SESSION['name']) . '!</h3>';
                    echo '<p style="margin: 0; opacity: 0.9;">Bạn đang trong hệ thống quản trị CGV Cinema</p>';
                    echo '</div>';
                    
                        // Stats Cards
                        echo '<div class="stats-grid">';
                        
                        // Đếm phim
                        $movies_result = $conn->query("SELECT COUNT(*) as count FROM movies WHERE status = 'showing'");
                        $movies_count = $movies_result ? $movies_result->fetch_assoc()['count'] : 0;
                        
                    echo '<div class="stat-card">';
                        echo '<div class="stat-icon"><i class="fas fa-film"></i></div>';
                        echo '<div class="stat-details">';
                        echo '<h3>' . $movies_count . '</h3>';
                        echo '<p>Phim đang chiếu</p>';
                        echo '</div>';
                    echo '</div>';
                        
                        // Đếm rạp
                        $theaters_result = $conn->query("SELECT COUNT(*) as count FROM theaters");
                        $theaters_count = $theaters_result ? $theaters_result->fetch_assoc()['count'] : 0;
                    
                    echo '<div class="stat-card">';
                        echo '<div class="stat-icon"><i class="fas fa-building"></i></div>';
                        echo '<div class="stat-details">';
                        echo '<h3>' . $theaters_count . '</h3>';
                        echo '<p>Rạp chiếu</p>';
                        echo '</div>';
                    echo '</div>';
                        
                        // Đếm người dùng 
                        $users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
                        $users_count = $users_result ? $users_result->fetch_assoc()['count'] : 0;
                    
                    echo '<div class="stat-card">';
                        echo '<div class="stat-icon"><i class="fas fa-users"></i></div>';
                        echo '<div class="stat-details">';
                        echo '<h3>' . $users_count . '</h3>';
                        echo '<p>Người dùng</p>';
                        echo '</div>';
                    echo '</div>';
                        
                        // Đếm đặt vé
                        $bookings_result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'");
                        $bookings_count = $bookings_result ? $bookings_result->fetch_assoc()['count'] : 0;
                    
                    echo '<div class="stat-card">';
                        echo '<div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>';
                        echo '<div class="stat-details">';
                        echo '<h3>' . $bookings_count . '</h3>';
                        echo '<p>Vé đã bán</p>';
                    echo '</div>';
                    echo '</div>';
                    
                        echo '</div>';
                        
                        // Recent Activities
                        echo '<div class="dashboard-section">';
                        echo '<h3><i class="fas fa-clock"></i> Hoạt động gần đây</h3>';
                        echo '<div class="activity-list">';
                        
                        $recent_sql = "SELECT 
                                        b.booking_code, 
                                        b.created_at, 
                                        u.name as user_name, 
                                        m.title as movie_title,
                                        b.booking_status
                                            FROM bookings b
                                       JOIN users u ON b.user_id = u.id
                                       JOIN showtimes st ON b.showtime_id = st.id
                                       JOIN movies m ON st.movie_id = m.id
                                            ORDER BY b.created_at DESC
                                            LIMIT 10";
                        
                        $recent_result = $conn->query($recent_sql);
                        
                        if ($recent_result && $recent_result->num_rows > 0) {
                            while ($activity = $recent_result->fetch_assoc()) {
                                $status_class = '';
                                $status_icon = '';
                                switch ($activity['booking_status']) {
                                    case 'confirmed':
                                        $status_class = 'success';
                                        $status_icon = 'check-circle';
                                        break;
                                    case 'pending':
                                        $status_class = 'warning';
                                        $status_icon = 'clock';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'danger';
                                        $status_icon = 'times-circle';
                                        break;
                                }
                                
                                echo '<div class="activity-item">';
                                echo '<div class="activity-icon ' . $status_class . '">';
                                echo '<i class="fas fa-' . $status_icon . '"></i>';
                                echo '</div>';
                                echo '<div class="activity-content">';
                                echo '<p><strong>' . htmlspecialchars($activity['user_name']) . '</strong> đặt vé phim <strong>' . htmlspecialchars($activity['movie_title']) . '</strong></p>';
                                echo '<small>' . date('d/m/Y H:i', strtotime($activity['created_at'])) . ' - Mã: ' . htmlspecialchars($activity['booking_code']) . '</small>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-data">Chưa có hoạt động nào gần đây.</p>';
                        }
                        
                        echo '</div>';
                    echo '</div>';
                    
                    break;
            }
            ?>
        </div>
        </main>
    </div>

    <script src="js/admin.js"></script>
    
    <!-- Admin Auto Logout Script - Đăng xuất khi đóng tab -->
    <script>
        (function() {
            'use strict';
            
            let isInternalNavigation = false;
            
            // Detect internal navigation trong admin
            document.addEventListener('click', function(event) {
                const target = event.target.closest('a');
                if (target && target.href) {
                    const currentDomain = window.location.hostname;
                    const linkDomain = new URL(target.href).hostname;
                    
                    // Nếu là link nội bộ admin, đánh dấu internal navigation
                    if (linkDomain === currentDomain || linkDomain === '') {
                        isInternalNavigation = true;
                        console.log('🔗 Admin internal navigation:', target.href);
                        
                        // Reset flag sau 3000ms để đảm bảo không conflict với admin operations
                        setTimeout(() => {
                            isInternalNavigation = false;
                        }, 3000);
                    }
                }
            });
            
            // Detect form submit trong admin
            document.addEventListener('submit', function(event) {
                isInternalNavigation = true;
                console.log('📝 Admin form submit detected');
                setTimeout(() => {
                    isInternalNavigation = false;
                }, 3000);
            });
            
            // Function để logout
            function performAdminLogout() {
                console.log('🚪 Admin tab closing - performing logout');
                
                // Sử dụng sendBeacon để đảm bảo logout request được gửi
                const logoutUrl = '../pages/actions/logout_process.php';
                
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(logoutUrl);
                } else {
                    // Fallback cho browser cũ
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', logoutUrl, false); // Synchronous
                    xhr.send();
                }
            }
            
            // Detect khi đóng tab admin (chỉ khi KHÔNG phải internal navigation)
            window.addEventListener('beforeunload', function(event) {
                if (!isInternalNavigation) {
                    console.log('🔐 Admin tab/window closing - logging out');
                    performAdminLogout();
                } else {
                    console.log('🔗 Admin internal navigation - NOT logging out');
                }
            });
            
            // Detect khi admin inactive quá lâu (10 phút)
            let adminInactiveTimer;
            const ADMIN_INACTIVE_TIME = 10 * 60 * 1000; // 10 phút
            
            function resetAdminTimer() {
                clearTimeout(adminInactiveTimer);
                adminInactiveTimer = setTimeout(function() {
                    alert('⚠️ Admin session đã hết hạn do không hoạt động!');
                    window.location.href = '../pages/actions/logout_process.php';
                }, ADMIN_INACTIVE_TIME);
            }
            
            // Track admin activity
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
                document.addEventListener(event, resetAdminTimer, true);
            });
            
            // Khởi tạo admin security
            document.addEventListener('DOMContentLoaded', function() {
                resetAdminTimer();
                console.log('🔐 Admin security system activated');
                console.log('📍 Admin page:', window.location.href);
                
                // Thêm warning khi admin cố gắng navigate away
                window.addEventListener('beforeunload', function(event) {
                    if (!isInternalNavigation) {
                        event.preventDefault();
                        event.returnValue = 'Bạn đang thoát khỏi Admin Panel. Session sẽ bị đăng xuất!';
                        return 'Bạn đang thoát khỏi Admin Panel. Session sẽ bị đăng xuất!';
                    }
                });
            });
            
            // Detect khi tab admin mất focus quá lâu (30 phút)
            let adminFocusTime = Date.now();
            
            window.addEventListener('blur', function() {
                adminFocusTime = Date.now();
            });
            
            window.addEventListener('focus', function() {
                const timeDiff = Date.now() - adminFocusTime;
                // Nếu admin tab mất focus quá 30 phút, logout
                if (timeDiff > 30 * 60 * 1000) {
                    alert('⚠️ Admin session đã hết hạn do tab mất focus quá lâu!');
                    window.location.href = '../pages/actions/logout_process.php';
                }
            });
            
        })();
    </script>
</body>
</html> 