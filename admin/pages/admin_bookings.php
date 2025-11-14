<?php
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Xử lý cập nhật trạng thái booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'update_status') {
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    
    $sql = "UPDATE bookings SET booking_status = ?, payment_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $payment_status, $booking_id);
    
    if ($stmt->execute()) {
        echo '<script>alert("Cập nhật trạng thái thành công!"); window.location.href = "?page=bookings";</script>';
    } else {
        echo '<script>alert("Có lỗi xảy ra!");</script>';
    }
}

if ($action == 'detail' && $booking_id > 0) {
    // Lấy thông tin chi tiết booking
    $sql = "SELECT b.*, u.name as user_name, u.email, u.phone,
                   m.title as movie_title, m.poster_url,
                   t.name as theater_name, s.screen_name,
                   st.show_date, st.show_time, st.price
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.id
            INNER JOIN showtimes st ON b.showtime_id = st.id
            INNER JOIN movies m ON st.movie_id = m.id
            INNER JOIN screens s ON st.screen_id = s.id
            INNER JOIN theaters t ON s.theater_id = t.id
            WHERE b.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking_result = $stmt->get_result();
    $booking = $booking_result->fetch_assoc();
    
    if (!$booking) {
        echo '<div style="text-align: center; padding: 50px; color: #666;">Không tìm thấy đặt vé này.</div>';
        return;
    }
    
    // Lấy danh sách ghế đã đặt
    $seats_sql = "SELECT s.seat_row, s.seat_number
                  FROM booking_seats bs
                  INNER JOIN seats s ON bs.seat_id = s.id
                  WHERE bs.booking_id = ?
                  ORDER BY s.seat_row, s.seat_number";
    $seats_stmt = $conn->prepare($seats_sql);
    $seats_stmt->bind_param("i", $booking_id);
    $seats_stmt->execute();
    $seats_result = $seats_stmt->get_result();
    
    $seats = [];
    while($seat = $seats_result->fetch_assoc()) {
        $seats[] = $seat['seat_row'] . $seat['seat_number'];
    }
    
    // Lấy thông tin combo bắp nước
    $combo_sql = "SELECT bc.quantity, bc.price as combo_price, c.name, c.description, c.image_url
                  FROM booking_combos bc
                  INNER JOIN combos c ON bc.combo_id = c.id
                  WHERE bc.booking_id = ?";
    $combo_stmt = $conn->prepare($combo_sql);
    $combo_stmt->bind_param("i", $booking_id);
    $combo_stmt->execute();
    $combo_result = $combo_stmt->get_result();
    
    $combos = [];
    while($combo = $combo_result->fetch_assoc()) {
        $combos[] = $combo;
    }
?>

<div class="content-header">
    <h1 class="content-title">🎫 Admin - Chi tiết đặt vé #<?php echo $booking['booking_code']; ?></h1>
    <div class="breadcrumb">Admin / Đặt vé / Chi tiết</div>
</div>

<div style="margin-bottom: 20px;">
    <a href="?page=bookings" class="btn btn-secondary">← Quay lại danh sách</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <!-- Thông tin đặt vé -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Thông tin đặt vé</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #666;">Mã đặt vé:</label>
                    <p style="font-size: 18px; font-weight: bold; color: #e50914;">🎫 <?php echo $booking['booking_code']; ?></p>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #666;">Ngày đặt:</label>
                    <p>📅 <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #666;">Khách hàng:</label>
                    <p style="font-weight: bold;">👤 <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p style="color: #666; font-size: 14px;">📧 <?php echo htmlspecialchars($booking['email']); ?></p>
                    <p style="color: #666; font-size: 14px;">📞 <?php echo htmlspecialchars($booking['phone']); ?></p>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #666;">Tổng tiền:</label>
                    <p style="font-size: 24px; font-weight: bold; color: #e50914;">
                        💰 <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ
                    </p>
                </div>
            </div>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            
            <h4 style="color: #333; margin-bottom: 15px;">🎬 Thông tin phim</h4>
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" 
                     alt="<?php echo htmlspecialchars($booking['movie_title']); ?>"
                     style="width: 100px; height: 150px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div>
                    <h3 style="color: #333; margin-bottom: 10px;">🎬 <?php echo htmlspecialchars($booking['movie_title']); ?></h3>
                    <p><strong>🏢 Rạp:</strong> <?php echo htmlspecialchars($booking['theater_name']); ?></p>
                    <p><strong>🏠 Phòng:</strong> <?php echo htmlspecialchars($booking['screen_name']); ?></p>
                    <p><strong>📅 Ngày chiếu:</strong> <?php echo date('d/m/Y', strtotime($booking['show_date'])); ?></p>
                    <p><strong>🕐 Giờ chiếu:</strong> <?php echo date('H:i', strtotime($booking['show_time'])); ?></p>
                    <p><strong>💺 Ghế:</strong> <span style="background: #f8f9fa; padding: 4px 8px; border-radius: 12px;"><?php echo implode(', ', $seats); ?></span></p>
                    
                    <?php if (!empty($combos)): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                            <p><strong>🍿 Combo bắp nước:</strong></p>
                            <div style="margin-left: 20px;">
                                <?php foreach($combos as $combo): ?>
                                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 6px;">
                                        <span style="color: #333; font-weight: 500; min-width: 150px;">
                                            <?php echo htmlspecialchars($combo['name']); ?>
                                        </span>
                                        <span style="color: #666; font-size: 14px; min-width: 40px;">
                                            x<?php echo $combo['quantity']; ?>
                                        </span>
                                        <span style="color: #e50914; font-weight: bold; font-size: 14px;">
                                            <?php echo number_format($combo['combo_price'] * $combo['quantity'], 0, ',', '.'); ?> VNĐ
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cập nhật trạng thái -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚙️ Cập nhật trạng thái</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="?page=bookings&action=update_status&id=<?php echo $booking_id; ?>">
                <div class="form-group">
                    <label class="form-label">Trạng thái đặt vé:</label>
                    <select name="status" class="form-control">
                        <option value="pending" <?php echo $booking['booking_status'] == 'pending' ? 'selected' : ''; ?>>⏳ Chờ xác nhận</option>
                        <option value="confirmed" <?php echo $booking['booking_status'] == 'confirmed' ? 'selected' : ''; ?>>✅ Đã xác nhận</option>
                        <option value="cancelled" <?php echo $booking['booking_status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Đã hủy</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Trạng thái thanh toán:</label>
                    <select name="payment_status" class="form-control">
                        <option value="pending" <?php echo $booking['payment_status'] == 'pending' ? 'selected' : ''; ?>>⏳ Chờ thanh toán</option>
                        <option value="paid" <?php echo $booking['payment_status'] == 'paid' ? 'selected' : ''; ?>>💰 Đã thanh toán</option>
                        <option value="refunded" <?php echo $booking['payment_status'] == 'refunded' ? 'selected' : ''; ?>>💸 Đã hoàn tiền</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Cập nhật</button>
            </form>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            
            <div style="text-align: center;">
                <h4 style="color: #333; margin-bottom: 15px;">Trạng thái hiện tại</h4>
                
                <?php
                $status_class = '';
                $status_text = '';
                $status_icon = '';
                switch($booking['booking_status']) {
                    case 'confirmed':
                        $status_class = 'status-confirmed';
                        $status_text = 'Đã xác nhận';
                        $status_icon = '✅';
                        break;
                    case 'pending':
                        $status_class = 'status-pending';
                        $status_text = 'Chờ xác nhận';
                        $status_icon = '⏳';
                        break;
                    case 'cancelled':
                        $status_class = 'status-cancelled';
                        $status_text = 'Đã hủy';
                        $status_icon = '❌';
                        break;
                }
                ?>
                
                <div style="margin-bottom: 10px;">
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_icon . ' ' . $status_text; ?></span>
                </div>
                
                <?php
                $payment_class = '';
                $payment_text = '';
                $payment_icon = '';
                switch($booking['payment_status']) {
                    case 'paid':
                        $payment_class = 'status-confirmed';
                        $payment_text = 'Đã thanh toán';
                        $payment_icon = '💰';
                        break;
                    case 'pending':
                        $payment_class = 'status-pending';
                        $payment_text = 'Chờ thanh toán';
                        $payment_icon = '⏳';
                        break;
                    case 'refunded':
                        $payment_class = 'status-cancelled';
                        $payment_text = 'Đã hoàn tiền';
                        $payment_icon = '💸';
                        break;
                }
                ?>
                
                <div>
                    <span class="status-badge <?php echo $payment_class; ?>"><?php echo $payment_icon . ' ' . $payment_text; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } else { ?>

<div class="content-header">
    <h1 class="content-title">🎫 Admin - Quản lý đặt vé</h1>
    <div class="breadcrumb">Admin / Đặt vé / Danh sách</div>
</div>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" placeholder="🔍 Tìm kiếm đặt vé..." 
               style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 250px;"
               onkeyup="searchTable(this, 'bookings-table')">
        
        <select id="filter-status" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterBookings()">
            <option value="">Tất cả trạng thái</option>
            <option value="pending">Chờ xác nhận</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="cancelled">Đã hủy</option>
        </select>
        
        <select id="filter-payment" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterBookings()">
            <option value="">Tất cả thanh toán</option>
            <option value="pending">Chờ thanh toán</option>
            <option value="paid">Đã thanh toán</option>
            <option value="refunded">Đã hoàn tiền</option>
        </select>
        
        <input type="date" id="filter-date" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterBookings()">
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="exportBookings()" class="btn btn-secondary">📊 Xuất Excel</button>
        <button onclick="printBookings()" class="btn btn-secondary">🖨️ In báo cáo</button>
    </div>
</div>

<div class="card">
    <table class="table" id="bookings-table">
        <thead>
            <tr>
                <th>Mã đặt vé</th>
                <th>Khách hàng</th>
                <th>Phim</th>
                <th>Rạp</th>
                <th>Ngày chiếu</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Thanh toán</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT b.*, u.name as user_name, m.title as movie_title,
                           t.name as theater_name, st.show_date, st.show_time
                    FROM bookings b
                    INNER JOIN users u ON b.user_id = u.id
                    INNER JOIN showtimes st ON b.showtime_id = st.id
                    INNER JOIN movies m ON st.movie_id = m.id
                    INNER JOIN screens s ON st.screen_id = s.id
                    INNER JOIN theaters t ON s.theater_id = t.id
                    ORDER BY b.created_at DESC";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($booking = mysqli_fetch_assoc($result)) {
                    echo '<tr data-status="' . $booking['booking_status'] . '" data-payment="' . $booking['payment_status'] . '" data-date="' . $booking['show_date'] . '">';
                    echo '<td><strong>🎫 ' . htmlspecialchars($booking['booking_code']) . '</strong></td>';
                    echo '<td>👤 ' . htmlspecialchars($booking['user_name']) . '</td>';
                    echo '<td>🎬 ' . htmlspecialchars($booking['movie_title']) . '</td>';
                    echo '<td>🏢 ' . htmlspecialchars($booking['theater_name']) . '</td>';
                    echo '<td>';
                    echo '<strong>📅 ' . date('d/m/Y', strtotime($booking['show_date'])) . '</strong><br>';
                    echo '<small style="color: #666;">🕐 ' . date('H:i', strtotime($booking['show_time'])) . '</small>';
                    echo '</td>';
                    echo '<td><strong style="color: #e50914;">💰 ' . number_format($booking['total_amount'], 0, ',', '.') . ' VNĐ</strong></td>';
                    
                    // Trạng thái đặt vé
                    $status_class = '';
                    $status_text = '';
                    $status_icon = '';
                    switch($booking['booking_status']) {
                        case 'confirmed':
                            $status_class = 'status-confirmed';
                            $status_text = 'Đã xác nhận';
                            $status_icon = '✅';
                            break;
                        case 'pending':
                            $status_class = 'status-pending';
                            $status_text = 'Chờ xác nhận';
                            $status_icon = '⏳';
                            break;
                        case 'cancelled':
                            $status_class = 'status-cancelled';
                            $status_text = 'Đã hủy';
                            $status_icon = '❌';
                            break;
                    }
                    echo '<td><span class="status-badge ' . $status_class . '">' . $status_icon . ' ' . $status_text . '</span></td>';
                    
                    // Trạng thái thanh toán
                    $payment_class = '';
                    $payment_text = '';
                    $payment_icon = '';
                    switch($booking['payment_status']) {
                        case 'paid':
                            $payment_class = 'status-confirmed';
                            $payment_text = 'Đã thanh toán';
                            $payment_icon = '💰';
                            break;
                        case 'pending':
                            $payment_class = 'status-pending';
                            $payment_text = 'Chờ thanh toán';
                            $payment_icon = '⏳';
                            break;
                        case 'refunded':
                            $payment_class = 'status-cancelled';
                            $payment_text = 'Đã hoàn tiền';
                            $payment_icon = '💸';
                            break;
                    }
                    echo '<td><span class="status-badge ' . $payment_class . '">' . $payment_icon . ' ' . $payment_text . '</span></td>';
                    
                    echo '<td>';
                    echo '<a href="?page=bookings&action=detail&id=' . $booking['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" title="Xem chi tiết">👁️</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9" style="text-align: center; padding: 60px; color: #666;">';
                echo '<div style="font-size: 64px; margin-bottom: 20px;">🎫</div>';
                echo '<h3 style="margin-bottom: 10px;">Chưa có đặt vé nào</h3>';
                echo '<p>Chưa có khách hàng nào đặt vé.</p>';
                echo '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php } ?>

<script>
function searchTable(input, tableId) {
    const searchTerm = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function filterBookings() {
    const statusFilter = document.getElementById('filter-status').value;
    const paymentFilter = document.getElementById('filter-payment').value;
    const dateFilter = document.getElementById('filter-date').value;
    const table = document.getElementById('bookings-table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        let showRow = true;
        
        if (statusFilter && row.getAttribute('data-status') !== statusFilter) {
            showRow = false;
        }
        
        if (paymentFilter && row.getAttribute('data-payment') !== paymentFilter) {
            showRow = false;
        }
        
        if (dateFilter && row.getAttribute('data-date') !== dateFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function exportBookings() {
    // Xuất dữ liệu ra CSV
    exportTableToCSV('bookings-table', 'danh-sach-dat-ve');
}

function printBookings() {
    // In báo cáo
    printTable('bookings-table');
}
</script> 