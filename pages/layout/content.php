<div id="content">
        <?php if(isset($_GET['quanly'])
        ) {
           $tam = $_GET['quanly'];
        }else{
            $tam = '';
        }if($tam == 'dangnhap'){
            include 'pages/pages/login.php';
        }elseif($tam == 'dangky'){
            include 'pages/pages/register.php';
        }elseif($tam == 'phim'){
            include 'pages/pages/movie.php';
        }elseif($tam == 'rap'){
            include 'pages/pages/theater.php';
        }elseif($tam == 've' || $tam == 'dat-ve'){
            include 'pages/Tickets.php';
        }elseif($tam == 'lich-su-dat-ve'){
            include 'pages/pages/booking_history.php';
        }elseif($tam == 'thanh-toan' || $tam == 'checkout'){
            include 'pages/pages/checkout.php';
        }elseif($tam == 'chon-combo'){
            include 'pages/pages/select_combo.php';
        }elseif($tam == 'admin'){
            include 'admin/index.php';
        }else{
            include 'pages/homePage.php';
        } ?>
</div>
<div class="clear"></div>