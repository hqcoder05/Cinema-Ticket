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

if (!$input || !isset($input['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$booking_id = intval($input['booking_id']);
$user_id = $_SESSION['user_id'];

// Bắt đầu transaction
mysqli_begin_transaction($conn);

try {
    // Kiểm tra booking có thuộc về user này không
    $check_sql = "SELECT b.*, st.show_date, st.show_time 
                  FROM bookings b 
                  INNER JOIN showtimes st ON b.showtime_id = st.id
                  WHERE b.id = ? AND b.user_id = ? AND b.booking_status = 'confirmed'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        throw new Exception('Không tìm thấy đặt vé hoặc bạn không có quyền hủy vé này');
    }
    
    $booking = $check_result->fetch_assoc();
    
    // Kiểm tra thời gian (chỉ cho phép hủy trước 2 giờ)
    $show_datetime = strtotime($booking['show_date'] . ' ' . $booking['show_time']);
    $current_time = time();
    $time_diff = $show_datetime - $current_time;
    
    if ($time_diff < 2 * 3600) { // 2 giờ = 2 * 3600 giây
        throw new Exception('Chỉ có thể hủy vé trước 2 giờ chiếu phim');
    }
    
    // Đếm số ghế để cộng lại vào available_seats
    $seat_count_sql = "SELECT COUNT(*) as seat_count FROM booking_seats WHERE booking_id = ?";
    $seat_count_stmt = $conn->prepare($seat_count_sql);
    $seat_count_stmt->bind_param("i", $booking_id);
    $seat_count_stmt->execute();
    $seat_count_result = $seat_count_stmt->get_result();
    $seat_count_data = $seat_count_result->fetch_assoc();
    $seat_count = $seat_count_data['seat_count'];
    
    // Cập nhật trạng thái booking
    $update_booking_sql = "UPDATE bookings SET booking_status = 'cancelled', payment_status = 'refunded' WHERE id = ?";
    $update_booking_stmt = $conn->prepare($update_booking_sql);
    $update_booking_stmt->bind_param("i", $booking_id);
    
    if (!$update_booking_stmt->execute()) {
        throw new Exception('Không thể cập nhật trạng thái đặt vé');
    }
    
    // Cộng lại số ghế có sẵn
    $update_seats_sql = "UPDATE showtimes SET available_seats = available_seats + ? WHERE id = ?";
    $update_seats_stmt = $conn->prepare($update_seats_sql);
    $update_seats_stmt->bind_param("ii", $seat_count, $booking['showtime_id']);
    
    if (!$update_seats_stmt->execute()) {
        throw new Exception('Không thể cập nhật số ghế có sẵn');
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Hủy vé thành công. Tiền sẽ được hoàn trả trong 3-5 ngày làm việc.'
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