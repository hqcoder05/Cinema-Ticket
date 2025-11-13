<?php 
session_name('CGV_SESSION');
session_start(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/homepages.css">
    <link rel="stylesheet" type="text/css" href="css/footer.css">
    <?php 
    // Load CSS riêng cho từng trang
    if (isset($_GET['quanly'])) {
        switch($_GET['quanly']) {
            case 've':
                echo '<link rel="stylesheet" type="text/css" href="css/tickets.css">';
                break;
            case 'phim':
                echo '<link rel="stylesheet" type="text/css" href="css/movie.css">';
                break;
            case 'rap':
                echo '<link rel="stylesheet" type="text/css" href="css/theater.css">';
                break;
            case 'thanh-toan':
            case 'checkout':
                echo '<link rel="stylesheet" type="text/css" href="css/checkout.css">';
                break;
            case 'chon-combo':
                echo '<link rel="stylesheet" type="text/css" href="css/combo.css">';
                break;
            case 'dangky':
                echo '<link rel="stylesheet" type="text/css" href="css/register-form.css">';
                break;
            case 'dangnhap':
                echo '<link rel="stylesheet" type="text/css" href="css/login-form.css">';
                break;
        }
    }
    ?>
    
    <title>CGV</title>
    <link rel="icon" href="https://www.cgv.vn/media/favicon/default/cgvcinemas-vietnam-favicon.ico" type="image/x-icon">
</head>
<body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <div class="container">
        <?php include 'pages/layout/header.php';
        include 'pages/layout/menu.php';
        include 'pages/layout/content.php';
        include 'pages/layout/footer.php'; ?>
    </div>
    
    <!-- Load jQuery cho tất cả trang -->
    <script src="js/jquery-3.7.1.js"></script>
    
    <!-- Load Auto Logout System -->
    <script src="js/auto_logout.js"></script>
    
    <?php 
    // Load JavaScript riêng cho từng trang
    if (isset($_GET['quanly'])) {
        switch($_GET['quanly']) {
            case 've':
                echo '<script src="js/tickets.js"></script>';
                break;
            case 'phim':
                echo '<script src="js/movie.js"></script>';
                break;
            case 'rap':
                echo '<script src="js/theater.js"></script>';
                break;
            case 'dangky':
                echo '<script src="js/register.js"></script>';
                break;
            case 'dangnhap':
                echo '<script src="js/login.js"></script>';
                break;
        }
    } else {
        // Trang home - load home JS
        echo '<script src="js/home.js"></script>';
    }
    ?>
</body>
</html>