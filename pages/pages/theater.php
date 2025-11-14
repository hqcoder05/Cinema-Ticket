<?php 
// Đảm bảo session được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_name('CGV_SESSION');
    session_start();
}
require_once 'admin/config/config.php'; 

// AJAX handler để lấy theaters theo city
if (isset($_GET['action']) && $_GET['action'] == 'get_theaters') {
    $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;
    
    if ($city_id > 0) {
        $sql = "SELECT t.*, c.name as city_name 
                FROM theaters t 
                LEFT JOIN cities c ON t.city_id = c.id 
                WHERE t.city_id = ? AND t.status = 'active' 
                ORDER BY t.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $city_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $theaters = [];
        while ($theater = $result->fetch_assoc()) {
            $theaters[] = $theater;
        }
        
        header('Content-Type: application/json');
        echo json_encode($theaters);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Lấy danh sách tất cả cities có theaters
$cities_sql = "SELECT c.*, COUNT(t.id) as theater_count 
               FROM cities c 
               LEFT JOIN theaters t ON c.id = t.city_id AND t.status = 'active'
               WHERE c.status = 'active' 
               GROUP BY c.id, c.code, c.name, c.status, c.display_order 
               HAVING theater_count > 0 
               ORDER BY c.display_order";
$cities_result = mysqli_query($conn, $cities_sql);

// Lấy city đầu tiên để hiển thị mặc định
$first_city = null;
$cities = [];
if ($cities_result && mysqli_num_rows($cities_result) > 0) {
    while ($city = mysqli_fetch_assoc($cities_result)) {
        $cities[] = $city;
        if (!$first_city) {
            $first_city = $city;
        }
    }
}

// Lấy theaters của city đầu tiên
$default_theaters = [];
if ($first_city) {
    $theaters_sql = "SELECT t.*, c.name as city_name 
                     FROM theaters t 
                     LEFT JOIN cities c ON t.city_id = c.id 
                     WHERE t.city_id = ? AND t.status = 'active' 
                     ORDER BY t.name";
    $stmt = $conn->prepare($theaters_sql);
    $stmt->bind_param("i", $first_city['id']);
    $stmt->execute();
    $theaters_result = $stmt->get_result();
    
    while ($theater = $theaters_result->fetch_assoc()) {
        $default_theaters[] = $theater;
    }
}
?>

<!-- CSS và JavaScript đã được tách ra file riêng: css/theater.css và js/theater.js -->

<div class="cgv-container">
    <div class="cgv-title">CGV CINEMAS</div>
    <hr class="cgv-divider">
    
    <!-- Loading indicator -->
    <div id="loading-indicator" style="display: none; text-align: center; color: #e71a0f; margin: 20px 0;">
        <div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #e71a0f; border-top: 3px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        <span style="margin-left: 10px;">Đang tải danh sách rạp...</span>
    </div>
    
    <!-- Cities Grid -->
    <div class="cgv-cities">
        <?php
        // Chia cities thành 5 cột
        $total_cities = count($cities);
        $cities_per_col = ceil($total_cities / 5);
        
        for ($col = 0; $col < 5; $col++) {
            echo '<div class="cgv-city-col"><ul>';
            
            $start_index = $col * $cities_per_col;
            $end_index = min($start_index + $cities_per_col, $total_cities);
            
            for ($i = $start_index; $i < $end_index; $i++) {
                if (isset($cities[$i])) {
                    $city = $cities[$i];
                    $active_class = ($city['id'] == $first_city['id']) ? 'active' : '';
                    echo '<li onclick="showTheaters(' . $city['id'] . ')" 
                             id="city-' . $city['id'] . '" 
                             class="' . $active_class . '" 
                             data-city-code="' . htmlspecialchars($city['code']) . '">';
                    echo htmlspecialchars($city['name']);
                    echo '<small style="display: block; color: #999; font-size: 11px;">(' . $city['theater_count'] . ' rạp)</small>';
                    echo '</li>';
                }
            }
            
            echo '</ul></div>';
        }
        ?>
    </div>
    
    <hr class="cgv-divider">
    
    <!-- Theaters Container -->
    <div id="theaters-container" class="cgv-theaters active">
        <div id="theaters-content">
            <?php if (!empty($default_theaters)): ?>
                <div class="theaters-header">
                    <h3 style="color: #e71a0f; text-align: center; margin-bottom: 20px;">
                        📍 DANH SÁCH RẬP CGV - <?php echo strtoupper(htmlspecialchars($first_city['name'])); ?>
                    </h3>
                </div>
                <div class="theaters-grid">
                    <?php
                    // Tạo một theater card cho mỗi theater
                    foreach ($default_theaters as $theater) {
                        $phone = $theater['phone'] ?: 'Chưa cập nhật';
                        echo '<div class="cgv-theater-list">';
                        echo '<ul>';
                        echo '<li onclick="showTheaterInfo(\'' . addslashes($theater['name']) . '\', \'' . addslashes($theater['location']) . '\', \'' . addslashes($phone) . '\')">';
                        echo '<strong>' . htmlspecialchars($theater['name']) . '</strong>';
                        echo '<br><small style="color: #aaa;">' . htmlspecialchars($theater['location']) . '</small>';
                        echo '</li>';
                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div>🏢</div>
                    <h3>Chưa có rạp nào</h3>
                    <p>Khu vực này hiện chưa có rạp CGV.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer-note">
            Click vào tên rạp để xem thông tin chi tiết
        </div>
    </div>
</div>

<!-- Theater Info Modal -->
<div id="theater-modal" style="display: none;">
    <div>
        <div class="close-btn" onclick="closeTheaterModal()">✕</div>
        
        <div id="theater-modal-content">
            <!-- Content will be inserted here -->
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button onclick="closeTheaterModal()" style="background: #e71a0f; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                Đóng
            </button>
        </div>
    </div>
</div>

<style>
/* Loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Theater grid responsive */
.theaters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.theaters-header {
    grid-column: 1 / -1;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .theaters-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .cgv-cities {
        grid-template-columns: repeat(2, 1fr);
    }
    
    #theater-modal > div {
        margin: 20px auto;
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .cgv-cities {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Dữ liệu cities để JavaScript sử dụng
window.citiesData = <?php echo json_encode($cities); ?>;
</script>