<?php
// Kiểm tra session trước khi khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_name('CGV_SESSION');
    session_start();
}

// Kiểm tra localStorage data và chuyển vào session nếu cần
if (isset($_COOKIE['pendingBooking'])) {
    $bookingData = json_decode($_COOKIE['pendingBooking'], true);
    if ($bookingData && isset($bookingData['seats'])) {
        $_SESSION['selected_seats'] = $bookingData['seats'];
        if (isset($bookingData['showtime_id'])) {
            $_SESSION['showtime_id'] = $bookingData['showtime_id'];
        }
        // Xóa cookie sau khi lấy xong
        setcookie('pendingBooking', '', time() - 3600, '/');
    }
}

require_once __DIR__ . '/../../admin/config/config.php';

// Lấy ghế đã chọn
$selected_seats = $_SESSION['selected_seats'] ?? [];

// Lấy giá vé từ showtime trong database
$showtime_id = $_SESSION['showtime_id'] ?? 0;
$base_ticket_price = 75000; // Giá mặc định
if ($showtime_id > 0) {
    $price_sql = "SELECT price FROM showtimes WHERE id = ?";
    $price_stmt = $conn->prepare($price_sql);
    $price_stmt->bind_param("i", $showtime_id);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    if ($price_result->num_rows > 0) {
        $price_data = $price_result->fetch_assoc();
        $base_ticket_price = $price_data['price'];
    }
}

// Tính tổng tiền vé (đã bao gồm giá VIP)
$total_ticket = 0;
foreach ($selected_seats as $seat) {
    if (is_array($seat) && isset($seat['price'])) {
        $total_ticket += $seat['price'];
    } else {
        // Fallback: tính theo ghế thường
        $total_ticket += $base_ticket_price;
    }
}

// Lấy combo đã chọn
$selected_combos = $_SESSION['selected_combos'] ?? [];
$combo_details = [];
$total_combo = 0;

if (!empty($selected_combos)) {
    // Lấy thông tin combo từ DB
    $ids = implode(',', array_map('intval', array_keys($selected_combos)));
    $sql = "SELECT * FROM combos WHERE id IN ($ids)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $cid = $row['id'];
        $qty = $selected_combos[$cid];
        $combo_total = $qty * $row['price'];
        $combo_details[] = [
            'name' => $row['name'],
            'quantity' => $qty,
            'price' => $row['price'],
            'total' => $combo_total
        ];
        $total_combo += $combo_total;
    }
}

$total = $total_ticket + $total_combo;
?>

<div class="checkout-container">
  <div class="checkout-header">
    <span class="checkout-icon">🧾</span>
    <h2>Xác nhận thanh toán</h2>
  </div>
  
  <!-- Hiển thị thông báo nếu bỏ qua combo -->
  <div id="skip-combo-notice" style="display: none; background: #ffeaa7; border: 2px solid #fdcb6e; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #2d3436;">
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 24px;">ℹ️</span>
      <div>
        <strong>Bạn đã bỏ qua chọn combo</strong>
                 <p style="margin: 5px 0 0 0; font-size: 14px;">Bạn vẫn có thể <a href="index.php?quanly=chon-combo" style="color: #e71a0f; text-decoration: underline;">quay lại chọn combo</a> trước khi thanh toán.</p>
      </div>
    </div>
  </div>
  <div class="checkout-box">
    <h4>Thông tin đặt vé</h4>
    <p><b>Ghế đã chọn:</b> 
    <?php 
    $seat_name = array_map(function($seat) {
        return is_array($seat) ? ($seat['id'] ?? '??') : $seat;
    }, $selected_seats);
    echo implode(', ', $seat_name); 
    ?>
    </p>
    <p><b>Ghế và giá vé:</b></p>
    <div style="margin-left: 20px;">
        <?php foreach ($selected_seats as $seat): ?>
            <?php 
            $seat_id = is_array($seat) ? ($seat['id'] ?? '??') : $seat;
            $seat_price = is_array($seat) && isset($seat['price']) ? $seat['price'] : $base_ticket_price;
            $seat_type = is_array($seat) && isset($seat['type']) && $seat['type'] === 'vip' ? ' (VIP)' : '';
            ?>
            <p style="margin: 5px 0;">
                • Ghế <?php echo htmlspecialchars($seat_id); ?><?php echo $seat_type; ?>: 
                <span class="text-danger fw-bold"><?php echo number_format($seat_price); ?> VNĐ</span>
            </p>
        <?php endforeach; ?>
        <p style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
            <strong>Tổng vé (<?php echo count($selected_seats); ?> ghế): 
            <span class="text-danger fw-bold"><?php echo number_format($total_ticket); ?> VNĐ</span></strong>
        </p>
    </div>
    <?php if (!empty($combo_details)): ?>
        <?php foreach ($combo_details as $combo): ?>
            <p>
                <b>Combo:</b> <?php echo htmlspecialchars($combo['name']); ?> x <?php echo $combo['quantity']; ?> = 
                <span class="text-danger fw-bold"><?php echo number_format($combo['total']); ?> VNĐ</span>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>
    <hr>
    <p class="checkout-total">Tổng cộng: <span><?php echo number_format($total); ?> VNĐ</span></p>
  </div>
  <form id="checkout-form" onsubmit="return submitBooking(event);">
    <div class="checkout-actions">
      <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check"></i> Xác nhận thanh toán</button>
      <a href="index.php?quanly=chon-combo" class="btn btn-secondary btn-lg ms-2"><i class="fas fa-arrow-left"></i> Quay lại chọn combo</a>
    </div>
  </form>
</div>

<script>
function submitBooking(e) {
    e.preventDefault();

    // Lấy dữ liệu từ PHP (render ra JS)
    var selectedSeats = <?php echo json_encode($selected_seats); ?>;
    var showtimeId = <?php echo isset($_SESSION['showtime_id']) ? intval($_SESSION['showtime_id']) : 0; ?>;
    var totalAmount = <?php echo json_encode($total); ?>;

    // Lấy combo từ session
    var selectedCombos = <?php echo json_encode($_SESSION['selected_combos'] ?? []); ?>;

    if (!selectedSeats || selectedSeats.length === 0) {
        alert("Bạn chưa chọn ghế!");
        return false;
    }

    var bookingData = {
        showtime_id: showtimeId,
        seats: selectedSeats,
        total_amount: totalAmount,
        combos: selectedCombos
    };

    // Gửi AJAX
    fetch("pages/actions/process_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(bookingData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Đặt vé thành công! Mã đặt vé: " + data.booking_code);
            window.location.href = "index.php?quanly=lich-su-dat-ve";
        } else {
            alert("Lỗi: " + data.message);
        }
    })
    .catch(err => {
        alert("Có lỗi xảy ra khi gửi dữ liệu!");
        console.error(err);
    });

    return false;
}
</script>

<script>
// Kiểm tra localStorage và chuyển thành cookie nếu cần
if (localStorage.getItem("pendingBooking")) {
    document.cookie = "pendingBooking=" + encodeURIComponent(localStorage.getItem("pendingBooking")) + ";path=/";
    localStorage.removeItem("pendingBooking");
    // Reload trang để PHP có thể đọc cookie
    window.location.reload();
}

// Kiểm tra xem có bỏ qua combo không
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('skipCombo') === 'true') {
        // Hiển thị thông báo
        document.getElementById('skip-combo-notice').style.display = 'block';
        
        // Xóa flag sau khi hiển thị
        localStorage.removeItem('skipCombo');
    }
});
</script>