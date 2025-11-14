<?php
header('Content-Type: application/json');
session_start();

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra quyền admin (nếu cần)
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Access denied']);
//     exit;
// }

// Lấy dữ liệu JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['combo_id']) || !is_numeric($input['combo_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid combo ID']);
    exit;
}

$combo_id = intval($input['combo_id']);

// Kết nối database
require_once __DIR__ . '/../../admin/config/config.php';

try {
    // Lấy trạng thái hiện tại
    $sql = "SELECT status FROM combos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $combo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Combo không tồn tại']);
        exit;
    }
    
    $combo = $result->fetch_assoc();
    $current_status = $combo['status'];
    
    // Đổi trạng thái
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    
    // Cập nhật database
    $update_sql = "UPDATE combos SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $combo_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Cập nhật trạng thái thành công',
            'new_status' => $new_status,
            'combo_id' => $combo_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật database: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

$conn->close();
?> 