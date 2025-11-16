<?php
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$showtime_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Xử lý action delete qua GET request
if ($action == 'delete' && $showtime_id > 0) {
    $sql = "DELETE FROM showtimes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '"); window.location.href = "?page=showtimes";</script>';
        exit;
    }
    $stmt->bind_param("i", $showtime_id);
    
    if ($stmt->execute()) {
        echo '<script>alert("Xóa lịch chiếu thành công!"); window.location.href = "?page=showtimes";</script>';
    } else {
        echo '<script>alert("❌ Lỗi thực thi: ' . addslashes($stmt->error) . '"); window.location.href = "?page=showtimes";</script>';
    }
    $stmt->close();
    exit;
}

// Xử lý các action khác qua POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $movie_id = intval($_POST['movie_id']);
        $screen_id = intval($_POST['screen_id']);
        $show_date = $_POST['show_date'];
        $show_time = $_POST['show_time'];
        $price = floatval($_POST['price']);
        
        if ($action == 'add') {
            $sql = "INSERT INTO showtimes (movie_id, screen_id, show_date, show_time, price) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissd", $movie_id, $screen_id, $show_date, $show_time, $price);
            
            if ($stmt->execute()) {
                echo '<script>alert("Thêm lịch chiếu thành công!"); window.location.href = "?page=showtimes";</script>';
            } else {
                echo '<script>alert("Có lỗi xảy ra!");</script>';
            }
        } else {
            $sql = "UPDATE showtimes SET movie_id = ?, screen_id = ?, show_date = ?, show_time = ?, price = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissdi", $movie_id, $screen_id, $show_date, $show_time, $price, $showtime_id);
            
            if ($stmt->execute()) {
                echo '<script>alert("Cập nhật lịch chiếu thành công!"); window.location.href = "?page=showtimes";</script>';
            } else {
                echo '<script>alert("Có lỗi xảy ra!");</script>';
            }
        }
    }
}

if ($action == 'add' || $action == 'edit') {
    $showtime = null;
    if ($action == 'edit' && $showtime_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM showtimes WHERE id = ?");
        $stmt->bind_param("i", $showtime_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $showtime = $result->fetch_assoc();
    }
    
    // Lấy danh sách phim
    $movies_result = mysqli_query($conn, "SELECT id, title FROM movies WHERE status = 'showing' ORDER BY title");
    
    // Lấy danh sách rạp và phòng
    $theaters_result = mysqli_query($conn, "SELECT t.id, t.name, s.id as screen_id, s.screen_name 
                                            FROM theaters t 
                                            INNER JOIN screens s ON t.id = s.theater_id 
                                            WHERE t.status = 'active'
                                            ORDER BY t.name, s.screen_name");
?>

<div class="content-header">
    <h1 class="content-title">⏰ Admin - <?php echo $action == 'add' ? 'Thêm lịch chiếu mới' : 'Chỉnh sửa lịch chiếu'; ?></h1>
    <div class="breadcrumb">Admin / Lịch chiếu / <?php echo $action == 'add' ? 'Thêm mới' : 'Chỉnh sửa'; ?></div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Phim *</label>
                    <select name="movie_id" required class="form-control">
                        <option value="">-- Chọn phim --</option>
                        <?php while($movie = mysqli_fetch_assoc($movies_result)): ?>
                            <option value="<?php echo $movie['id']; ?>" 
                                    <?php echo ($showtime && $showtime['movie_id'] == $movie['id']) ? 'selected' : ''; ?>>
                                🎬 <?php echo htmlspecialchars($movie['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Phòng chiếu *</label>
                    <select name="screen_id" required class="form-control">
                        <option value="">-- Chọn phòng chiếu --</option>
                        <?php 
                        mysqli_data_seek($theaters_result, 0); // Reset result pointer
                        $current_theater = '';
                        while($theater = mysqli_fetch_assoc($theaters_result)): 
                            if ($current_theater != $theater['name']) {
                                if ($current_theater != '') echo '</optgroup>';
                                echo '<optgroup label="🏢 ' . htmlspecialchars($theater['name']) . '">';
                                $current_theater = $theater['name'];
                            }
                        ?>
                            <option value="<?php echo $theater['screen_id']; ?>" 
                                    <?php echo ($showtime && $showtime['screen_id'] == $theater['screen_id']) ? 'selected' : ''; ?>>
                                🏠 <?php echo htmlspecialchars($theater['screen_name']); ?>
                            </option>
                        <?php endwhile; ?>
                        <?php if ($current_theater != '') echo '</optgroup>'; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Ngày chiếu *</label>
                    <input type="date" name="show_date" class="form-control"
                           value="<?php echo $showtime ? $showtime['show_date'] : date('Y-m-d'); ?>" 
                           required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Giờ chiếu *</label>
                    <select name="show_time" required class="form-control">
                        <option value="">-- Chọn giờ --</option>
                        <?php
                        $times = ['08:00', '10:30', '13:00', '15:30', '18:00', '20:30', '23:00'];
                        foreach($times as $time) {
                            $selected = ($showtime && $showtime['show_time'] == $time.':00') ? 'selected' : '';
                            echo '<option value="' . $time . ':00" ' . $selected . '>🕐 ' . $time . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Giá vé (VNĐ) *</label>
                    <select name="price" required class="form-control">
                        <option value="">-- Chọn giá --</option>
                        <?php
                        $prices = [
                            '50000' => '50,000 VNĐ (Giá ưu đãi)',
                            '70000' => '70,000 VNĐ (Thường)',
                            '90000' => '90,000 VNĐ (Cuối tuần)',
                            '120000' => '120,000 VNĐ (Lễ tết)'
                        ];
                        foreach($prices as $value => $label) {
                            $selected = ($showtime && $showtime['price'] == $value) ? 'selected' : '';
                            echo '<option value="' . $value . '" ' . $selected . '>💰 ' . $label . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action == 'add' ? '⏰ Thêm lịch chiếu' : '💾 Cập nhật'; ?>
                </button>
                <a href="?page=showtimes" class="btn btn-secondary">❌ Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php } else { ?>

<div class="content-header">
    <h1 class="content-title">⏰ Admin - Quản lý lịch chiếu</h1>
    <div class="breadcrumb">Admin / Lịch chiếu / Danh sách</div>
</div>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="?page=showtimes&action=add" class="btn btn-primary">⏰ + Thêm lịch chiếu mới</a>
    
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" placeholder="🔍 Tìm kiếm..." 
               style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 200px;"
               onkeyup="searchTable(this, 'showtimes-table')">
        <select style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterByDate(this.value)">
            <option value="">Tất cả ngày</option>
            <option value="<?php echo date('Y-m-d'); ?>">Hôm nay</option>
            <option value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">Ngày mai</option>
            <option value="<?php echo date('Y-m-d', strtotime('+2 day')); ?>">Ngày kia</option>
        </select>
    </div>
</div>

<div class="card">
    <table class="table" id="showtimes-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Phim</th>
                <th>Rạp & Phòng</th>
                <th>Ngày chiếu</th>
                <th>Giờ chiếu</th>
                <th>Giá vé</th>
                <th>Đã bán</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT st.*, m.title as movie_title, m.poster_url,
                           t.name as theater_name, s.screen_name,
                           (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = st.id AND b.booking_status = 'confirmed') as sold_tickets
                    FROM showtimes st
                    INNER JOIN movies m ON st.movie_id = m.id
                    INNER JOIN screens s ON st.screen_id = s.id
                    INNER JOIN theaters t ON s.theater_id = t.id
                    WHERE st.show_date >= CURDATE()
                    ORDER BY st.show_date ASC, st.show_time ASC";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($showtime = mysqli_fetch_assoc($result)) {
                    $show_datetime = strtotime($showtime['show_date'] . ' ' . $showtime['show_time']);
                    $is_past = $show_datetime < time();
                    $row_style = $is_past ? 'opacity: 0.6; background-color: #f8f9fa;' : '';
                    
                    echo '<tr style="' . $row_style . '" data-date="' . $showtime['show_date'] . '">';
                    echo '<td><strong>#' . $showtime['id'] . '</strong></td>';
                    echo '<td>';
                    echo '<div style="display: flex; align-items: center; gap: 10px;">';
                    if ($showtime['poster_url']) {
                        echo '<img src="' . htmlspecialchars($showtime['poster_url']) . '" alt="poster" style="width: 40px; height: 60px; object-fit: cover; border-radius: 5px;">';
                    }
                    echo '<strong style="color: #333;">🎬 ' . htmlspecialchars($showtime['movie_title']) . '</strong>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td>';
                    echo '<div style="font-weight: bold; color: #333;">🏢 ' . htmlspecialchars($showtime['theater_name']) . '</div>';
                    echo '<small style="color: #666;">🏠 ' . htmlspecialchars($showtime['screen_name']) . '</small>';
                    echo '</td>';
                    echo '<td>';
                    echo '<strong style="color: #e50914;">📅 ' . date('d/m/Y', strtotime($showtime['show_date'])) . '</strong>';
                    $day_name = date('l', strtotime($showtime['show_date']));
                    $day_vn = [
                        'Monday' => 'Thứ 2', 'Tuesday' => 'Thứ 3', 'Wednesday' => 'Thứ 4',
                        'Thursday' => 'Thứ 5', 'Friday' => 'Thứ 6', 'Saturday' => 'Thứ 7', 'Sunday' => 'CN'
                    ];
                    echo '<br><small style="color: #666;">' . $day_vn[$day_name] . '</small>';
                    echo '</td>';
                    echo '<td><strong style="font-size: 16px; color: #e50914;">🕐 ' . date('H:i', strtotime($showtime['show_time'])) . '</strong></td>';
                    echo '<td><strong style="color: #28a745;">💰 ' . number_format($showtime['price'], 0, ',', '.') . ' VNĐ</strong></td>';
                    echo '<td>';
                    if ($showtime['sold_tickets'] > 0) {
                        echo '<span style="background: #e50914; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">🎫 ' . $showtime['sold_tickets'] . ' vé</span>';
                    } else {
                        echo '<span style="background: #f8f9fa; color: #666; padding: 4px 8px; border-radius: 12px; font-size: 12px;">Chưa bán</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    if (!$is_past) {
                        echo '<div style="display: flex; gap: 5px;">';
                        echo '<a href="?page=showtimes&action=edit&id=' . $showtime['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" title="Chỉnh sửa">✏️</a>';
                        echo '<a href="?page=showtimes&action=delete&id=' . $showtime['id'] . '" class="btn" style="background-color: #dc3545; color: white; padding: 5px 10px; font-size: 12px;" onclick="return confirm(\'⚠️ Bạn có chắc muốn xóa lịch chiếu này?\')" title="Xóa">🗑️</a>';
                        echo '</div>';
                    } else {
                        echo '<span style="color: #666; font-size: 12px;">⏰ Đã qua</span>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8" style="text-align: center; padding: 60px; color: #666;">';
                echo '<div style="font-size: 64px; margin-bottom: 20px;">⏰</div>';
                echo '<h3 style="margin-bottom: 10px;">Chưa có lịch chiếu nào</h3>';
                echo '<p>Hãy thêm lịch chiếu đầu tiên.</p>';
                echo '<a href="?page=showtimes&action=add" class="btn btn-primary" style="margin-top: 15px;">⏰ Thêm lịch chiếu đầu tiên</a>';
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

function filterByDate(date) {
    const table = document.getElementById('showtimes-table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (date === '' || row.getAttribute('data-date') === date) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script> 