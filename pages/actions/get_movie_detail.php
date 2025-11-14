<?php
require_once '../../admin/config/config.php';

header('Content-Type: application/json');

// Debug logging
error_log("get_movie_detail.php called");
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

// Kiểm tra cả GET và POST
$movie_id = 0;
if (isset($_POST['movie_id'])) {
    $movie_id = intval($_POST['movie_id']);
} elseif (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
} elseif (isset($_GET['movie_id'])) {
    $movie_id = intval($_GET['movie_id']);
}

error_log("Movie ID: " . $movie_id);

if ($movie_id > 0) {
    try {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $movie = $result->fetch_assoc();
            error_log("Movie found: " . print_r($movie, true));
            
        echo json_encode([
            'success' => true,
                'data' => $movie
        ]);
    } else {
            error_log("No movie found with ID: " . $movie_id);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phim với ID: ' . $movie_id
            ]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid movie ID");
    echo json_encode([
        'success' => false,
        'message' => 'ID phim không hợp lệ. Nhận được: ' . $movie_id
    ]);
}
?> 