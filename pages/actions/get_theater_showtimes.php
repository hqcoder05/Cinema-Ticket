<?php
// Đảm bảo session được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_name('CGV_SESSION');
    session_start();
}

require_once '../../admin/config/config.php';

// Xử lý AJAX request để lấy theaters theo city
if (isset($_GET['action']) && $_GET['action'] == 'get_theaters') {
    $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;
    
    if ($city_id > 0) {
        $sql = "SELECT t.*, c.name as city_name 
                FROM theaters t 
                LEFT JOIN cities c ON t.city_id = c.id 
                WHERE t.city_id = ? AND t.status = 'active' 
                ORDER BY t.name";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $city_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $theaters = [];
            while ($theater = $result->fetch_assoc()) {
                $theaters[] = $theater;
            }
            
            header('Content-Type: application/json');
            echo json_encode($theaters);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database error']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([]);
    }
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['theater_id'])) {
    $theater_id = intval($_GET['theater_id']);
    
    // Lấy thông tin rạp
    $theater_stmt = $conn->prepare("SELECT name FROM theaters WHERE id = ?");
    $theater_stmt->bind_param("i", $theater_id);
    $theater_stmt->execute();
    $theater_result = $theater_stmt->get_result();
    
    if ($theater_result && $theater_result->num_rows > 0) {
        $theater = $theater_result->fetch_assoc();
        
        // Lấy lịch chiếu cho ngày hôm nay và các ngày tới
        $today = date('Y-m-d');
        $sql = "SELECT m.id as movie_id, m.title, m.genre, m.duration, m.poster_url,
                       st.id as showtime_id, st.show_time, st.price, st.available_seats
                FROM movies m
                INNER JOIN showtimes st ON m.id = st.movie_id
                INNER JOIN screens s ON st.screen_id = s.id
                WHERE s.theater_id = ? AND st.show_date >= ?
                ORDER BY m.title, st.show_time";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $theater_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $movies = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $movie_id = $row['movie_id'];
                
                if (!isset($movies[$movie_id])) {
                    $movies[$movie_id] = [
                        'id' => $movie_id,
                        'title' => $row['title'],
                        'genre' => $row['genre'],
                        'duration' => $row['duration'],
                        'poster_url' => $row['poster_url'],
                        'showtimes' => []
                    ];
                }
                
                $movies[$movie_id]['showtimes'][] = [
                    'id' => $row['showtime_id'],
                    'show_time' => date('H:i', strtotime($row['show_time'])),
                    'price' => $row['price'],
                    'available_seats' => $row['available_seats']
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'theater_name' => $theater['name'],
            'movies' => array_values($movies)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy rạp'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID rạp không hợp lệ'
    ]);
}
?> 