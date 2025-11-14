<!-- Session Data cho JavaScript -->
<div id="session-data" style="display: none;" 
     data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>"
     data-user-id="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
</div>

<!-- Banner Section -->
<div class="banner-section">
    <div class="container">
        <div class="banner-content">
            <h1>Chào mừng đến với CGV</h1>
            <p>Trải nghiệm điện ảnh tuyệt vời cùng chúng tôi</p>
            <a href="index.php?quanly=phim" class="cta-button">Đặt vé ngay</a>
        </div>
    </div>
</div>

<!-- Movies Section -->
<div class="section">
    <div class="container">
        <div class="section-header">
            <h2>Phim Đang Chiếu</h2>
            <p>Những bộ phim hot nhất hiện tại</p>
        </div>
        
        <div class="movies-grid">
            <?php
            require_once 'admin/config/config.php';
            
            // Lấy danh sách phim từ database hoặc dùng dữ liệu mặc định
            $sql = "SELECT * FROM movies WHERE status = 'showing' ORDER BY created_at DESC LIMIT 6";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($movie = mysqli_fetch_assoc($result)) {
                    echo '<div class="movie-card">';
                    echo '<img src="' . $movie['poster_url'] . '" alt="' . htmlspecialchars($movie['title']) . '">';
                    echo '<div class="movie-info">';
                    echo '<h3>' . htmlspecialchars($movie['title']) . '</h3>';
                    echo '<p>' . htmlspecialchars($movie['genre']) . '</p>';
                    echo '<button class="movie-btn" onclick="bookMovie(' . $movie['id'] . ')">Đặt vé</button>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // Dữ liệu mặc định nếu không có trong database
            ?>
            <div class="movie-card">
                <img src="img/Phim/latmat8.jpg" alt="Lật Mặt 8">
                <div class="movie-info">
                    <h3>Lật Mặt 8</h3>
                    <p>Thể loại: Hành động, Hài</p>
                    <button class="movie-btn" onclick="bookMovie(1)">Đặt vé</button>
                </div>
            </div>
            
            <div class="movie-card">
                <img src="img/Phim/thamtukien.jpg" alt="Thám Tử Kiên">
                <div class="movie-info">
                    <h3>Thám Tử Kiên</h3>
                    <p>Thể loại: Trinh thám, Hành động</p>
                    <button class="movie-btn" onclick="bookMovie(2)">Đặt vé</button>
                </div>
            </div>
            
            <div class="movie-card">
                <img src="img/Phim/diadao.jpg" alt="Địa Đạo">
                <div class="movie-info">
                    <h3>Địa Đạo</h3>
                    <p>Thể loại: Chiến tranh, Hành động</p>
                    <button class="movie-btn" onclick="bookMovie(3)">Đặt vé</button>
                </div>
            </div>
            
            <div class="movie-card">
                <img src="img/Phim/doisanquy.jpg" alt="Đội Săn Quỷ">
                <div class="movie-info">
                    <h3>Đội Săn Quỷ</h3>
                    <p>Thể loại: Kinh dị, Hành động</p>
                    <button class="movie-btn" onclick="bookMovie(4)">Đặt vé</button>
                </div>
            </div>
            
            <div class="movie-card">
                <img src="img/Phim/shin.jpg" alt="Shin Cậu Bé Bút Chì">
                <div class="movie-info">
                    <h3>Shin Cậu Bé Bút Chì</h3>
                    <p>Thể loại: Hoạt hình, Gia đình</p>
                    <button class="movie-btn" onclick="bookMovie(5)">Đặt vé</button>
                </div>
            </div>
            
            <div class="movie-card">
                <img src="img/Phim/doraemon_movie44.jpg" alt="Doraemon Movie 44">
                <div class="movie-info">
                    <h3>Doraemon Movie 44</h3>
                    <p>Thể loại: Hoạt hình, Phiêu lưu</p>
                    <button class="movie-btn" onclick="bookMovie(6)">Đặt vé</button>
                </div>
            </div>
            <?php } ?>
        </div>
        
        <div class="view-all">
            <a href="index.php?quanly=phim" class="view-all-btn">Xem tất cả phim</a>
        </div>
    </div>
</div>

<!-- Promotions Section -->
<div class="section promotions-section">
    <div class="container">
        <div class="section-header">
            <h2>Ưu Đãi Đặc Biệt</h2>
            <p>Những chương trình khuyến mãi hấp dẫn</p>
        </div>
        
        <div class="promotions-grid">
            <div class="promotion-card">
                <img src="img/UuDai/sale_t4.jpg" alt="Sale Thứ 4">
                <div class="promotion-info">
                    <h3>Sale Thứ 4</h3>
                    <p>Giảm giá đặc biệt mỗi thứ 4</p>
                </div>
            </div>
            
            <div class="promotion-card">
                <img src="img/UuDai/QuaTangCGV.png" alt="Quà Tặng CGV">
                <div class="promotion-info">
                    <h3>Quà Tặng CGV</h3>
                    <p>Nhận quà khi đặt vé online</p>
                </div>
            </div>
            
            <div class="promotion-card">
                <img src="img/UuDai/UuDaiSinhNhat.png" alt="Ưu Đãi Sinh Nhật">
                <div class="promotion-info">
                    <h3>Ưu Đãi Sinh Nhật</h3>
                    <p>Khuyến mãi đặc biệt sinh nhật</p>
                </div>
            </div>
            
            <div class="promotion-card">
                <img src="img/UuDai/UuDaiTreEm.png" alt="Ưu Đãi Trẻ Em">
                <div class="promotion-info">
                    <h3>Ưu Đãi Trẻ Em</h3>
                    <p>Giá vé ưu đãi cho trẻ em</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<div class="section services-section">
    <div class="container">
        <div class="section-header">
            <h2>Dịch Vụ Của Chúng Tôi</h2>
            <p>Trải nghiệm điện ảnh toàn diện</p>
        </div>
        
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">🎬</div>
                <h3>Đặt Vé Online</h3>
                <p>Dễ dàng đặt vé trực tuyến, chọn chỗ ngồi yêu thích</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">🍿</div>
                <h3>Bắp Rang & Nước Uống</h3>
                <p>Thưởng thức bắp rang và nước uống tại rạp</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">🎫</div>
                <h3>Ưu Đãi Thành Viên</h3>
                <p>Nhiều ưu đãi hấp dẫn cho thành viên thân thiết</p>
            </div>
        </div>
    </div>
</div>

