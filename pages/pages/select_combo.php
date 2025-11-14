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
        // Xóa cookie sau khi lấy xong (tránh lặp lại)
        setcookie('pendingBooking', '', time() - 3600, '/');
    }
}
// Kết nối database
require_once __DIR__ . '/../../admin/config/config.php';

// Lấy danh sách combo active
$sql = "SELECT * FROM combos WHERE status = 'active' ORDER BY id DESC";
$result = $conn->query($sql);
if (!$result) {
    die("Lỗi SQL: " . $conn->error);
}
$combos = [];
while ($row = $result->fetch_assoc()) {
    $combos[] = $row;
}
?>

<div class="container combo-container">
    <h2 class="combo-title">Chọn combo bắp nước (tùy chọn)</h2>
    <form method="post" action="pages/actions/process_select_combo.php">
        <div class="row justify-content-center">
            <?php foreach ($combos as $combo): ?>
                <div class="col-md-6 col-sm-12 mb-4 d-flex justify-content-center">
                    <div class="combo-card">
                        <?php if ($combo['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($combo['image_url']); ?>" alt="Combo Image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($combo['name']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($combo['description'])); ?></p>
                            <div class="price text-danger font-weight-bold mb-2"><?php echo number_format($combo['price']); ?> VNĐ</div>
                            <div class="input-group">
                                <button type="button" onclick="changeQty(<?php echo $combo['id']; ?>, -1)">-</button>
                                <input type="number" name="combo_qty[<?php echo $combo['id']; ?>]" id="qty_<?php echo $combo['id']; ?>" value="0" min="0">
                                <button type="button" onclick="changeQty(<?php echo $combo['id']; ?>, 1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="combo-actions text-center">
            <button type="submit" name="action" value="continue" class="btn btn-primary btn-lg mx-2 px-5">Tiếp tục</button>
            <button type="submit" name="action" value="skip" class="btn btn-secondary btn-lg mx-2 px-5">Bỏ qua</button>
        </div>
    </form>
</div>
<script>
    function changeQty(id, delta) {
        var input = document.getElementById('qty_' + id);
        var val = parseInt(input.value) || 0;
        val += delta;
        if (val < 0) val = 0;
        input.value = val;
    }
</script>
<script>
if (localStorage.getItem("pendingBooking")) {
    document.cookie = "pendingBooking=" + encodeURIComponent(localStorage.getItem("pendingBooking")) + ";path=/";
    localStorage.removeItem("pendingBooking");
}
</script>