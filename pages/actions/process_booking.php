<?php
session_name('CGV_SESSION');
session_start();
require_once '../../admin/config/config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

// Đọc dữ liệu JSON từ request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$showtime_id = isset($input['showtime_id']) ? intval($input['showtime_id']) : 0;
$seats = isset($input['seats']) ? $input['seats'] : [];
$total_amount = isset($input['total_amount']) ? floatval($input['total_amount']) : 0;
$user_id = $_SESSION['user_id'];

// Lấy combo đã chọn từ session (nếu có)
$combo_total = 0;
$selected_combos = isset($_SESSION['selected_combos']) ? $_SESSION['selected_combos'] : [];

if (!empty($selected_combos)) {
    // Lấy giá combo từ database
    $combo_ids = array_keys($selected_combos);
    $combo_ids_str = implode(',', array_map('intval', $combo_ids));
    $combo_sql = "SELECT id, price FROM combos WHERE id IN ($combo_ids_str)";
    $combo_result = $conn->query($combo_sql);
    $combo_prices = [];
    while ($row = $combo_result->fetch_assoc()) {
        $combo_prices[$row['id']] = $row['price'];
    }
    // Tính tổng tiền combo
    foreach ($selected_combos as $combo_id => $qty) {
        if (isset($combo_prices[$combo_id])) {
            $combo_total += $combo_prices[$combo_id] * $qty;
        }
    }
    // Cộng vào tổng tiền
    $total_amount += $combo_total;
}

if (empty($seats) || $showtime_id <= 0 || $total_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thông tin đặt vé không đầy đủ']);
    exit();
}

// Bắt đầu transaction
mysqli_begin_transaction($conn);

try {
    // Tạo mã đặt vé
    $booking_code = 'CGV' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
    
    // Kiểm tra mã đặt vé đã tồn tại chưa
    $check_code_sql = "SELECT id FROM bookings WHERE booking_code = ?";
    $check_stmt = $conn->prepare($check_code_sql);
    $check_stmt->bind_param("s", $booking_code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // Nếu mã đã tồn tại, tạo mã mới
    while ($check_result->num_rows > 0) {
        $booking_code = 'CGV' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
        $check_stmt->bind_param("s", $booking_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    }
    
    // Thêm booking
    $booking_sql = "INSERT INTO bookings (user_id, showtime_id, total_amount, booking_code, booking_status, payment_status) 
                    VALUES (?, ?, ?, ?, 'confirmed', 'paid')";
    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("iids", $user_id, $showtime_id, $total_amount, $booking_code);
    
    if (!$booking_stmt->execute()) {
        throw new Exception('Không thể tạo đặt vé: ' . $booking_stmt->error);
    }
    
    $booking_id = $conn->insert_id;

    // Lưu combo vào bảng booking_combos (nếu có)
    if (!empty($selected_combos)) {
        foreach ($selected_combos as $combo_id => $qty) {
            if (isset($combo_prices[$combo_id]) && $qty > 0) {
                $price = $combo_prices[$combo_id];
                $insert_combo_sql = "INSERT INTO booking_combos (booking_id, combo_id, quantity, price) VALUES (?, ?, ?, ?)";
                $insert_combo_stmt = $conn->prepare($insert_combo_sql);
                $insert_combo_stmt->bind_param("iiid", $booking_id, $combo_id, $qty, $price);
                $insert_combo_stmt->execute();
            }
        }
        // Xóa combo khỏi session sau khi đặt vé thành công
        unset($_SESSION['selected_combos']);
    }
    
    // Lấy screen_id từ showtime
    $screen_sql = "SELECT screen_id FROM showtimes WHERE id = ?";
    $screen_stmt = $conn->prepare($screen_sql);
    $screen_stmt->bind_param("i", $showtime_id);
    $screen_stmt->execute();
    $screen_result = $screen_stmt->get_result();
    
    if ($screen_result->num_rows == 0) {
        throw new Exception('Không tìm thấy thông tin suất chiếu');
    }
    
    $screen_data = $screen_result->fetch_assoc();
    $screen_id = $screen_data['screen_id'];
    
    // Thêm từng ghế đã đặt
    foreach ($seats as $seat) {
        // Kiểm tra xem ghế có tồn tại trong database không, nếu không thì tạo mới
        $seat_check_sql = "SELECT id FROM seats WHERE screen_id = ? AND seat_row = ? AND seat_number = ?";
        $seat_check_stmt = $conn->prepare($seat_check_sql);
        $seat_check_stmt->bind_param("isi", $screen_id, $seat['row'], $seat['number']);
        $seat_check_stmt->execute();
        $seat_check_result = $seat_check_stmt->get_result();
        
        if ($seat_check_result->num_rows > 0) {
            $seat_data = $seat_check_result->fetch_assoc();
            $seat_id = $seat_data['id'];
        } else {
            // Tạo ghế mới
            $seat_type = $seat['type'];
            $create_seat_sql = "INSERT INTO seats (screen_id, seat_row, seat_number, seat_type) VALUES (?, ?, ?, ?)";
            $create_seat_stmt = $conn->prepare($create_seat_sql);
            $create_seat_stmt->bind_param("isis", $screen_id, $seat['row'], $seat['number'], $seat_type);
            
            if (!$create_seat_stmt->execute()) {
                throw new Exception('Không thể tạo ghế: ' . $create_seat_stmt->error);
            }
            
            $seat_id = $conn->insert_id;
        }
        
        // Kiểm tra ghế đã được đặt chưa
        $booked_check_sql = "SELECT bs.id FROM booking_seats bs 
                            INNER JOIN bookings b ON bs.booking_id = b.id 
                            WHERE bs.seat_id = ? AND b.showtime_id = ? AND b.booking_status != 'cancelled'";
        $booked_check_stmt = $conn->prepare($booked_check_sql);
        $booked_check_stmt->bind_param("ii", $seat_id, $showtime_id);
        $booked_check_stmt->execute();
        $booked_check_result = $booked_check_stmt->get_result();
        
        if ($booked_check_result->num_rows > 0) {
            throw new Exception('Ghế ' . $seat['id'] . ' đã được đặt');
        }
        
        // Thêm ghế vào booking_seats
        $booking_seat_sql = "INSERT INTO booking_seats (booking_id, seat_id, price) VALUES (?, ?, ?)";
        $booking_seat_stmt = $conn->prepare($booking_seat_sql);
        $booking_seat_stmt->bind_param("iid", $booking_id, $seat_id, $seat['price']);
        
        if (!$booking_seat_stmt->execute()) {
            throw new Exception('Không thể đặt ghế: ' . $booking_seat_stmt->error);
        }
    }
    
    // Cập nhật số ghế có sẵn
    $update_seats_sql = "UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?";
    $update_seats_stmt = $conn->prepare($update_seats_sql);
    $seat_count = count($seats);
    $update_seats_stmt->bind_param("ii", $seat_count, $showtime_id);
    
    if (!$update_seats_stmt->execute()) {
        throw new Exception('Không thể cập nhật số ghế: ' . $update_seats_stmt->error);
    }
    
    // Thêm thông tin thanh toán
    $payment_sql = "INSERT INTO payments (booking_id, payment_method, amount, transaction_id) 
                    VALUES (?, 'online', ?, ?)";
    $transaction_id = 'TXN' . $booking_code . time();
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("ids", $booking_id, $total_amount, $transaction_id);
    
    if (!$payment_stmt->execute()) {
        throw new Exception('Không thể ghi nhận thanh toán: ' . $payment_stmt->error);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đặt vé thành công',
        'booking_code' => $booking_code,
        'booking_id' => $booking_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 