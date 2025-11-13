<?php 
// Đảm bảo session được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_name('CGV_SESSION');
    session_start();
}

// Include config với đường dẫn đúng từ root (Tickets.php trong folder pages)
require_once __DIR__ . '/../admin/config/config.php';

// Kiểm tra kết nối database
if (!$conn) {
    echo '<div style="color: red; padding: 20px; text-align: center;">Lỗi kết nối database. Vui lòng thử lại sau.</div>';
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo '<script>alert("Vui lòng đăng nhập để đặt vé!"); window.location.href = "index.php?quanly=dangnhap";</script>';
    exit();
}

$showtime_id = isset($_GET['showtime_id']) ? intval($_GET['showtime_id']) : 0;
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$theater_name = isset($_GET['theater']) ? urldecode($_GET['theater']) : '';

// Nếu có theater_name, hiển thị lịch chiếu của rạp đó
if ($theater_name && !$showtime_id && !$movie_id) {
    $sql = "SELECT st.*, s.screen_name, t.name as theater_name, m.title as movie_title, m.poster_url
            FROM showtimes st
            INNER JOIN screens s ON st.screen_id = s.id
            INNER JOIN theaters t ON s.theater_id = t.id  
            INNER JOIN movies m ON st.movie_id = m.id
            WHERE t.name LIKE ? AND st.show_date >= CURDATE()
            ORDER BY m.title, st.show_date, st.show_time";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo '<div style="color: red; padding: 20px; text-align: center;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
        exit();
    }
    
    $theater_search = '%' . $theater_name . '%';
    $stmt->bind_param("s", $theater_search);
    $stmt->execute();
    $showtimes = $stmt->get_result();
?>

<div class="main-content">
    <div class="booking-container">
        <h2 style="color: #fff; text-align: center; margin: 20px 0;">
            🎬 LỊCH CHIẾU - <?php echo htmlspecialchars($theater_name); ?>
        </h2>
        
        <div class="theater-showtimes">
            <?php if ($showtimes->num_rows > 0): ?>
                <?php 
                $current_movie = '';
                while($showtime = $showtimes->fetch_assoc()): 
                    if ($current_movie != $showtime['movie_title']) {
                        if ($current_movie != '') echo '</div></div>'; // Đóng movie block trước
                        $current_movie = $showtime['movie_title'];
                ?>
                <div class="movie-showtimes-block">
                    <div class="movie-header">
                        <img src="<?php echo $showtime['poster_url']; ?>" alt="<?php echo htmlspecialchars($showtime['movie_title']); ?>" class="movie-poster-small">
                        <div class="movie-title">
                            <h3><?php echo htmlspecialchars($showtime['movie_title']); ?></h3>
                            <p class="theater-info">📍 <?php echo htmlspecialchars($showtime['theater_name']); ?></p>
                        </div>
                    </div>
                    <div class="showtimes-grid">
                <?php } ?>
                        <div class="showtime-card">
                            <div class="showtime-info">
                                <p><strong>📅 Ngày:</strong> <?php echo date('d/m/Y', strtotime($showtime['show_date'])); ?></p>
                                <p><strong>🕐 Giờ:</strong> <?php echo date('H:i', strtotime($showtime['show_time'])); ?></p>
                                <p><strong>🏠 Phòng:</strong> <?php echo htmlspecialchars($showtime['screen_name']); ?></p>
                                <p><strong>💰 Giá:</strong> <?php echo number_format($showtime['price'], 0, ',', '.'); ?> VNĐ</p>
                            </div>
                            <button class="btn-select-showtime" onclick="selectShowtime(<?php echo $showtime['id']; ?>)">
                                🎫 Đặt vé ngay
                            </button>
                        </div>
                <?php endwhile; ?>
                <?php if ($current_movie != '') echo '</div></div>'; // Đóng movie block cuối ?>
            <?php else: ?>
                <div style="color: #fff; text-align: center; padding: 40px;">
                    <h3>😔 Không có lịch chiếu</h3>
                    <p>Hiện tại rạp <strong><?php echo htmlspecialchars($theater_name); ?></strong> chưa có lịch chiếu nào từ hôm nay.</p>
                    <p><a href="index.php?quanly=rap" style="color: #e71a0f;">← Quay lại trang rạp</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php } elseif ($movie_id && !$showtime_id) {
    $sql = "SELECT st.*, s.screen_name, t.name as theater_name, m.title as movie_title
            FROM showtimes st
            INNER JOIN screens s ON st.screen_id = s.id
            INNER JOIN theaters t ON s.theater_id = t.id  
            INNER JOIN movies m ON st.movie_id = m.id
            WHERE st.movie_id = ? AND st.show_date >= CURDATE()
            ORDER BY st.show_date, st.show_time";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo '<div style="color: red; padding: 20px; text-align: center;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
        exit();
    }
    
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $showtimes = $stmt->get_result();
?>

<div class="main-content">
    <div class="booking-container">
        <h2 style="color: #fff; text-align: center; margin: 20px 0;">CHỌN LỊCH CHIẾU</h2>
        
        <div class="showtimes-selection">
            <?php if ($showtimes->num_rows > 0): ?>
                <?php while($showtime = $showtimes->fetch_assoc()): ?>
                    <div class="showtime-option">
                        <div class="showtime-info">
                            <h3><?php echo htmlspecialchars($showtime['movie_title']); ?></h3>
                            <p><strong>Rạp:</strong> <?php echo htmlspecialchars($showtime['theater_name']); ?></p>
                            <p><strong>Phòng:</strong> <?php echo htmlspecialchars($showtime['screen_name']); ?></p>
                            <p><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($showtime['show_date'])); ?></p>
                            <p><strong>Giờ:</strong> <?php echo date('H:i', strtotime($showtime['show_time'])); ?></p>
                            <p><strong>Giá:</strong> <?php echo number_format($showtime['price'], 0, ',', '.'); ?> VNĐ</p>
                        </div>
                        <button class="btn-select-showtime" onclick="selectShowtime(<?php echo $showtime['id']; ?>)">
                            Chọn suất chiếu này
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="color: #fff; text-align: center; padding: 20px;">
                    <p>Không có lịch chiếu nào cho phim này từ hôm nay.</p>
                    <p><a href="index.php?quanly=phim" style="color: #e71a0f;">← Quay lại trang phim</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php } elseif ($showtime_id) {
    // Lấy thông tin suất chiếu
    $sql = "SELECT st.*, s.screen_name, s.id as screen_id, t.name as theater_name, m.title as movie_title, m.poster_url
            FROM showtimes st
            INNER JOIN screens s ON st.screen_id = s.id
            INNER JOIN theaters t ON s.theater_id = t.id  
            INNER JOIN movies m ON st.movie_id = m.id
            WHERE st.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $showtime_result = $stmt->get_result();
    
    if ($showtime_result->num_rows > 0) {
        $showtime = $showtime_result->fetch_assoc();
        
        // Lấy ghế đã được đặt
        $booked_seats_sql = "SELECT s.seat_row, s.seat_number 
                             FROM booking_seats bs
                             INNER JOIN bookings b ON bs.booking_id = b.id
                             INNER JOIN seats s ON bs.seat_id = s.id
                             WHERE b.showtime_id = ? AND b.booking_status != 'cancelled'";
        $booked_stmt = $conn->prepare($booked_seats_sql);
        $booked_stmt->bind_param("i", $showtime_id);
        $booked_stmt->execute();
        $booked_result = $booked_stmt->get_result();
        
        $booked_seats = [];
        while($booked = $booked_result->fetch_assoc()) {
            $booked_seats[] = $booked['seat_row'] . $booked['seat_number'];
        }
?>

<div class="main-content">
    <div class="booking-container">
        <h2 style="color: #fff; text-align: center; margin: 20px 0;">ĐẶT VÉ XEM PHIM</h2>
        
        <div class="movie-booking-info">
            <img src="<?php echo $showtime['poster_url']; ?>" alt="<?php echo htmlspecialchars($showtime['movie_title']); ?>">
            <div class="booking-details">
                <h3><?php echo htmlspecialchars($showtime['movie_title']); ?></h3>
                <p><strong>Rạp:</strong> <?php echo htmlspecialchars($showtime['theater_name']); ?></p>
                <p><strong>Phòng:</strong> <?php echo htmlspecialchars($showtime['screen_name']); ?></p>
                <p><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($showtime['show_date'])); ?></p>
                <p><strong>Giờ:</strong> <?php echo date('H:i', strtotime($showtime['show_time'])); ?></p>
                <p><strong>Giá:</strong> <span id="ticket-price"><?php echo number_format($showtime['price'], 0, ',', '.'); ?></span> VNĐ/vé</p>
            </div>
        </div>
        
        <div class="seat-selection">
            <h3 style="color: #fff; margin: 20px 0;">Chọn ghế</h3>
            
            <div class="screen">MÀN HÌNH</div>
            
            <div class="seats-container">
                <?php
                // Tạo sơ đồ ghế (giả lập 10 hàng, mỗi hàng 10 ghế)
                $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                foreach($rows as $row) {
                    echo '<div class="seat-row">';
                    echo '<span class="row-label">' . $row . '</span>';
                    for($i = 1; $i <= 10; $i++) {
                        $seat_id = $row . $i;
                        $is_booked = in_array($seat_id, $booked_seats);
                        $seat_class = $is_booked ? 'seat booked' : 'seat available';
                        $seat_type = in_array($row, ['E', 'F', 'G']) ? 'vip' : 'standard';
                        
                        echo '<button class="' . $seat_class . ' ' . $seat_type . '" 
                              data-seat="' . $seat_id . '" 
                              data-row="' . $row . '" 
                              data-number="' . $i . '"
                              onclick="selectSeat(this)" ' . ($is_booked ? 'disabled' : '') . '>';
                        echo $i;
                        echo '</button>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
            
            <div class="seat-legend">
                <div class="legend-item">
                    <div class="seat available standard"></div>
                    <span>Ghế thường - Trống</span>
                </div>
                <div class="legend-item">
                    <div class="seat available vip"></div>
                    <span>Ghế VIP - Trống</span>
                </div>
                <div class="legend-item">
                    <div class="seat selected"></div>
                    <span>Ghế đã chọn</span>
                </div>
                <div class="legend-item">
                    <div class="seat booked"></div>
                    <span>Ghế đã đặt</span>
                </div>
            </div>
        </div>
        
        <div class="booking-summary">
            <h3 style="color: #fff;">Tóm tắt đặt vé</h3>
            <div class="summary-content">
                <p>Ghế đã chọn: <span id="selected-seats">Chưa chọn ghế</span></p>
                <p>Số lượng vé: <span id="ticket-count">0</span></p>
                <p>Tổng tiền: <span id="total-amount">0</span> VNĐ</p>
            </div>
            <button id="btn-book-tickets" class="btn-book-tickets" onclick="bookTickets()" disabled>
                ĐẶT VÉ
            </button>
        </div>
    </div>
</div>

<?php } else { ?>
<div class="main-content">
    <div class="booking-container">
        <h2 style="color: #fff; text-align: center; margin: 20px 0;">TRANG ĐẶT VÉ</h2>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div style="color: #fff; text-align: center; padding: 40px;">
                <p style="font-size: 18px; margin-bottom: 20px;">Vui lòng đăng nhập để đặt vé xem phim</p>
                <a href="index.php?quanly=dangnhap" class="btn-back" style="margin-right: 15px;">Đăng nhập</a>
                <a href="index.php?quanly=dangky" class="btn-back">Đăng ký</a>
            </div>
        <?php else: ?>
            <div style="color: #fff; text-align: center; padding: 40px;">
                <p style="font-size: 18px; margin-bottom: 20px;">Chào mừng <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                <p style="margin-bottom: 30px;">Vui lòng chọn phim để bắt đầu đặt vé</p>
                
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #e71a0f; margin-bottom: 20px;">PHIM ĐANG CHIẾU</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; max-width: 800px; margin: 0 auto;">
                        <?php
                        // Hiển thị danh sách phim
                        $movies_sql = "SELECT * FROM movies WHERE status = 'showing' LIMIT 6";
                        $movies_result = $conn->query($movies_sql);
                        
                        if ($movies_result && $movies_result->num_rows > 0) {
                            while($movie = $movies_result->fetch_assoc()) {
                                echo '<div style="background: #1a1a1a; border-radius: 10px; padding: 15px; border: 1px solid #333;">';
                                echo '<img src="' . $movie['poster_url'] . '" alt="' . htmlspecialchars($movie['title']) . '" style="width: 100%; height: 250px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">';
                                echo '<h4 style="color: #fff; margin-bottom: 8px;">' . htmlspecialchars($movie['title']) . '</h4>';
                                echo '<p style="color: #ccc; font-size: 14px; margin-bottom: 15px;">' . htmlspecialchars($movie['genre']) . '</p>';
                                echo '<a href="index.php?quanly=ve&movie_id=' . $movie['id'] . '" style="display: inline-block; background: #e71a0f; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; font-size: 14px;">Đặt vé</a>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="color: #ccc; grid-column: 1 / -1;">Hiện tại chưa có phim nào đang chiếu.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="index.php?quanly=phim" class="btn-back">Xem tất cả phim</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php } } ?>

<!-- Truyền dữ liệu từ PHP sang JavaScript -->
<div id="tickets-data" 
     data-showtime-id="<?php echo $showtime_id; ?>" 
     data-ticket-price="<?php echo isset($showtime['price']) ? $showtime['price'] : 0; ?>" 
     style="display: none;"></div>

<script src="js/jquery-3.7.1.js"></script>
<script src="js/tickets.js"></script>