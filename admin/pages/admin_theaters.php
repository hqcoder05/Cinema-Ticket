<?php
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$theater_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $name = trim($_POST['name']);
        $location = trim($_POST['address']); // Đổi từ address thành location
        $phone = trim($_POST['phone']) ?: '';
        $city_id = isset($_POST['city_id']) ? (int)$_POST['city_id'] : 1; // Mặc định city_id = 1 (HCM)
        $status = $_POST['status'] ?: 'active';
        
        if ($action == 'add') {
            $sql = "INSERT INTO theaters (name, location, city_id, phone, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiss", $name, $location, $city_id, $phone, $status);
            
            if ($stmt->execute()) {
                $theater_id = $conn->insert_id;
                
                // Tạo screens mặc định cho rạp mới (5 phòng chiếu)
                for ($i = 1; $i <= 5; $i++) {
                    $screen_name = "Phòng " . $i;
                    $total_seats = 100; // Sức chứa mặc định
                    $screen_sql = "INSERT INTO screens (theater_id, screen_name, total_seats) VALUES (?, ?, ?)";
                    $screen_stmt = $conn->prepare($screen_sql);
                    $screen_stmt->bind_param("isi", $theater_id, $screen_name, $total_seats);
                    $screen_stmt->execute();
                }
                
                echo '<script>alert("Thêm rạp thành công!"); window.location.href = "?page=theaters";</script>';
            } else {
                echo '<script>alert("Có lỗi xảy ra: ' . $conn->error . '");</script>';
            }
        } else {
            $sql = "UPDATE theaters SET name = ?, location = ?, city_id = ?, phone = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissi", $name, $location, $city_id, $phone, $status, $theater_id);
            
            if ($stmt->execute()) {
                echo '<script>alert("Cập nhật rạp thành công!"); window.location.href = "?page=theaters";</script>';
            } else {
                echo '<script>alert("Có lỗi xảy ra: ' . $conn->error . '");</script>';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit_screen') {
        // Xử lý cập nhật phòng chiếu
        $screen_id = (int)$_POST['screen_id'];
        $screen_name = trim($_POST['screen_name']);
        $total_seats = (int)$_POST['capacity']; // Frontend gửi capacity nhưng DB dùng total_seats
        
        $sql = "UPDATE screens SET screen_name = ?, total_seats = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $screen_name, $total_seats, $screen_id);
        
        if ($stmt->execute()) {
            echo '<script>alert("Cập nhật phòng chiếu thành công!"); window.location.reload();</script>';
        } else {
            echo '<script>alert("Có lỗi xảy ra: ' . $conn->error . '");</script>';
        }
    }
}

// Xử lý xóa rạp qua GET request
if ($action == 'delete' && $theater_id > 0) {
    // Kiểm tra ràng buộc foreign key trước khi xóa
    $check_showtimes = "SELECT COUNT(*) as count FROM showtimes s 
                       INNER JOIN screens sc ON s.screen_id = sc.id 
                       WHERE sc.theater_id = ?";
    $stmt_check = $conn->prepare($check_showtimes);
    if ($stmt_check === false) {
        echo '<script>alert("❌ Lỗi prepare check showtimes: ' . addslashes($conn->error) . '"); window.location.href = "?page=theaters";</script>';
        exit;
    }
    $stmt_check->bind_param("i", $theater_id);
    $stmt_check->execute();
    $showtime_result = $stmt_check->get_result();
    $showtime_count = $showtime_result->fetch_assoc()['count'];
    $stmt_check->close();
    
    // Kiểm tra bookings
    $check_bookings = "SELECT COUNT(*) as count FROM bookings b 
                      INNER JOIN showtimes s ON b.showtime_id = s.id 
                      INNER JOIN screens sc ON s.screen_id = sc.id
                      WHERE sc.theater_id = ?";
    $stmt_bookings = $conn->prepare($check_bookings);
    if ($stmt_bookings === false) {
        echo '<script>alert("❌ Lỗi prepare check bookings: ' . addslashes($conn->error) . '"); window.location.href = "?page=theaters";</script>';
        exit;
    }
    $stmt_bookings->bind_param("i", $theater_id);
    $stmt_bookings->execute();
    $booking_result = $stmt_bookings->get_result();
    $booking_count = $booking_result->fetch_assoc()['count'];
    $stmt_bookings->close();
    
    if ($showtime_count > 0 || $booking_count > 0) {
        // Không thể xóa trực tiếp
        echo '<script>
            alert("⚠️ KHÔNG THỂ XÓA RẠP NÀY!\\n\\n" +
                  "Rạp có:\\n" +
                  "• ' . $showtime_count . ' lịch chiếu\\n" +
                  "• ' . $booking_count . ' vé đã bán\\n\\n" +
                  "Hãy chuyển trạng thái thành \'Tạm ngừng\' thay vì xóa.");
            window.location.href = "?page=theaters";
        </script>';
    } else {
        // Có thể xóa an toàn
        // Xóa tất cả screens của rạp trước
        $delete_screens_sql = "DELETE FROM screens WHERE theater_id = ?";
        $delete_screens_stmt = $conn->prepare($delete_screens_sql);
        if ($delete_screens_stmt === false) {
            echo '<script>alert("❌ Lỗi prepare delete screens: ' . addslashes($conn->error) . '"); window.location.href = "?page=theaters";</script>';
            exit;
        }
        $delete_screens_stmt->bind_param("i", $theater_id);
        $delete_screens_stmt->execute();
        $delete_screens_stmt->close();
        
        // Xóa rạp
        $sql = "DELETE FROM theaters WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo '<script>alert("❌ Lỗi prepare delete theater: ' . addslashes($conn->error) . '"); window.location.href = "?page=theaters";</script>';
            exit;
        }
        $stmt->bind_param("i", $theater_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo '<script>alert("✅ Xóa rạp thành công!"); window.location.href = "?page=theaters";</script>';
            } else {
                echo '<script>alert("❌ Không tìm thấy rạp để xóa!"); window.location.href = "?page=theaters";</script>';
            }
        } else {
            echo '<script>alert("❌ Lỗi thực thi: ' . addslashes($stmt->error) . '"); window.location.href = "?page=theaters";</script>';
        }
        $stmt->close();
    }
    exit;
}

if ($action == 'add' || $action == 'edit') {
    $theater = null;
    if ($action == 'edit' && $theater_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM theaters WHERE id = ?");
        $stmt->bind_param("i", $theater_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $theater = $result->fetch_assoc();
    }
?>

<div class="content-header">
    <h1 class="content-title">🏢 Admin - <?php echo $action == 'add' ? 'Thêm rạp mới' : 'Chỉnh sửa rạp'; ?></h1>
    <div class="breadcrumb">Admin / Quản lý rạp / <?php echo $action == 'add' ? 'Thêm mới' : 'Chỉnh sửa'; ?></div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Tên rạp *</label>
                    <input type="text" name="name" class="form-control"
                           value="<?php echo $theater ? htmlspecialchars($theater['name']) : ''; ?>" 
                           required placeholder="VD: CGV Vincom Landmark 81">
                </div>
                <div class="form-group">
                    <label class="form-label">Thành phố *</label>
                    <select name="city_id" class="form-control" required>
                        <option value="">Chọn thành phố</option>
                        <?php
                        $cities_sql = "SELECT * FROM cities WHERE status = 'active' ORDER BY display_order";
                        $cities_result = mysqli_query($conn, $cities_sql);
                        if ($cities_result && mysqli_num_rows($cities_result) > 0) {
                            while($city = mysqli_fetch_assoc($cities_result)) {
                                $selected = ($theater && $theater['city_id'] == $city['id']) ? 'selected' : '';
                                echo '<option value="' . $city['id'] . '" ' . $selected . '>' . htmlspecialchars($city['name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Địa chỉ *</label>
                <input type="text" name="address" class="form-control"
                       value="<?php echo $theater ? htmlspecialchars(isset($theater['location']) ? $theater['location'] : (isset($theater['address']) ? $theater['address'] : '')) : ''; ?>" 
                       required placeholder="VD: Tầng B1, Vincom Mega Mall Landmark 81, 772 Điện Biên Phủ, Bình Thạnh">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?php echo $theater ? htmlspecialchars(isset($theater['phone']) ? $theater['phone'] : '') : ''; ?>" 
                           placeholder="VD: 028 3 999 8888">
                </div>
                <div class="form-group">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($theater && isset($theater['status']) && $theater['status'] == 'active') ? 'selected' : ''; ?>>🟢 Đang hoạt động</option>
                        <option value="inactive" <?php echo ($theater && isset($theater['status']) && $theater['status'] == 'inactive') ? 'selected' : ''; ?>>🔴 Tạm ngừng</option>
                        <option value="maintenance" <?php echo ($theater && isset($theater['status']) && $theater['status'] == 'maintenance') ? 'selected' : ''; ?>>🛠️ Bảo trì</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action == 'add' ? '🏢 Thêm rạp' : '💾 Cập nhật'; ?>
                </button>
                <a href="?page=theaters" class="btn btn-secondary">❌ Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php } elseif ($action == 'screens' && $theater_id > 0) {
    // Quản lý phòng chiếu của rạp
    $theater_stmt = $conn->prepare("SELECT name FROM theaters WHERE id = ?");
    $theater_stmt->bind_param("i", $theater_id);
    $theater_stmt->execute();
    $theater_result = $theater_stmt->get_result();
    $theater = $theater_result->fetch_assoc();
?>

<div class="content-header">
    <h1 class="content-title">🏠 Admin - Quản lý phòng chiếu</h1>
    <div class="breadcrumb">Admin / Quản lý rạp / <?php echo htmlspecialchars($theater['name']); ?> / Phòng chiếu</div>
</div>

<div style="margin-bottom: 20px;">
    <a href="?page=theaters" class="btn btn-secondary">← Quay lại danh sách rạp</a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">🏠 Phòng chiếu - <?php echo htmlspecialchars($theater['name']); ?></h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên phòng</th>
                <th>Sức chứa</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $screens_sql = "SELECT * FROM screens WHERE theater_id = ? ORDER BY screen_name";
            $screens_stmt = $conn->prepare($screens_sql);
            $screens_stmt->bind_param("i", $theater_id);
            $screens_stmt->execute();
            $screens_result = $screens_stmt->get_result();
            
            if ($screens_result && $screens_result->num_rows > 0) {
                while($screen = $screens_result->fetch_assoc()) {
                    // Kiểm tra trường capacity/total_seats
                    $capacity = isset($screen['total_seats']) ? $screen['total_seats'] : (isset($screen['capacity']) ? $screen['capacity'] : '100');
                    
                    echo '<tr>';
                    echo '<td><strong>#' . $screen['id'] . '</strong></td>';
                    echo '<td><strong>🏠 ' . htmlspecialchars($screen['screen_name']) . '</strong></td>';
                    echo '<td><span style="background: #f8f9fa; padding: 4px 8px; border-radius: 12px;">' . $capacity . ' ghế</span></td>';
                    echo '<td><span class="status-badge status-confirmed">🟢 Hoạt động</span></td>';
                    echo '<td>';
                    echo '<button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" onclick="editScreen(' . $screen['id'] . ', \'' . htmlspecialchars($screen['screen_name']) . '\', ' . $capacity . ')">✏️ Sửa</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" style="text-align: center; padding: 50px; color: #666;">';
                echo '<div style="font-size: 48px; margin-bottom: 20px;">🏠</div>';
                echo '<h3>Chưa có phòng chiếu</h3>';
                echo '<p>Rạp này chưa có phòng chiếu nào</p>';
                echo '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php } else { ?>

<div class="content-header">
    <h1 class="content-title">🏢 Admin - Quản lý rạp chiếu</h1>
    <div class="breadcrumb">Admin / Quản lý rạp / Danh sách</div>
</div>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="?page=theaters&action=add" class="btn btn-primary">🏢 + Thêm rạp mới</a>
    
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" placeholder="🔍 Tìm kiếm rạp..." 
               style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 250px;"
               onkeyup="searchTable(this, 'theaters-table')">
        <select style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterTheaters(this.value)">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="inactive">Tạm ngừng</option>
            <option value="maintenance">Bảo trì</option>
        </select>
    </div>
</div>

<div class="card">
    <table class="table" id="theaters-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên rạp</th>
                <th>Địa chỉ</th>
                <th>Điện thoại</th>
                <th>Số phòng chiếu</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT t.*, c.name as city_name,
                           (SELECT COUNT(*) FROM screens WHERE theater_id = t.id) as screen_count
                    FROM theaters t 
                    LEFT JOIN cities c ON t.city_id = c.id
                    ORDER BY c.display_order, t.name";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($theater = mysqli_fetch_assoc($result)) {
                    // Kiểm tra và gán giá trị mặc định cho các key có thể không tồn tại
                    $status = isset($theater['status']) ? $theater['status'] : 'active';
                    $address = isset($theater['location']) ? $theater['location'] : (isset($theater['address']) ? $theater['address'] : 'Chưa cập nhật');
                    $phone = isset($theater['phone']) ? $theater['phone'] : 'Chưa cập nhật';
                    
                    echo '<tr data-status="' . $status . '">';
                    echo '<td><strong>#' . $theater['id'] . '</strong></td>';
                    echo '<td>';
                    echo '<div style="font-weight: bold; color: #333; margin-bottom: 5px;">🏢 ' . htmlspecialchars($theater['name']) . '</div>';
                    if (isset($theater['city_name']) && $theater['city_name']) {
                        echo '<small style="color: #999;">🏙️ ' . htmlspecialchars($theater['city_name']) . '</small>';
                    }
                    echo '</td>';
                    echo '<td><small style="color: #666;">📍 ' . htmlspecialchars($address) . '</small></td>';
                    echo '<td><span style="color: #666;">📞 ' . htmlspecialchars($phone) . '</span></td>';
                    echo '<td><strong>' . $theater['screen_count'] . '</strong> phòng</td>';
                    
                    $status_text = '';
                    $status_class = '';
                    $status_icon = '';
                    switch($status) {
                        case 'active':
                            $status_text = 'Đang hoạt động';
                            $status_class = 'status-confirmed';
                            $status_icon = '🟢';
                            break;
                        case 'inactive':
                            $status_text = 'Tạm ngừng';
                            $status_class = 'status-cancelled';
                            $status_icon = '🔴';
                            break;
                        case 'maintenance':
                            $status_text = 'Bảo trì';
                            $status_class = 'status-pending';
                            $status_icon = '🛠️';
                            break;
                        default:
                            $status_text = 'Đang hoạt động';
                            $status_class = 'status-confirmed';
                            $status_icon = '🟢';
                            break;
                    }
                    
                    echo '<td><span class="status-badge ' . $status_class . '">' . $status_icon . ' ' . $status_text . '</span></td>';
                    echo '<td>';
                    echo '<div style="display: flex; gap: 5px;">';
                    echo '<a href="?page=theaters&action=screens&id=' . $theater['id'] . '" class="btn" style="background-color: #17a2b8; color: white; padding: 5px 10px; font-size: 12px;" title="Quản lý phòng chiếu">🏠</a>';
                    echo '<a href="?page=theaters&action=edit&id=' . $theater['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" title="Chỉnh sửa rạp">✏️</a>';
                    echo '<a href="?page=theaters&action=delete&id=' . $theater['id'] . '" class="btn" style="background-color: #dc3545; color: white; padding: 5px 10px; font-size: 12px;" onclick="return confirm(\'⚠️ Bạn có chắc muốn xóa rạp này? Tất cả phòng chiếu sẽ bị xóa.\')" title="Xóa rạp">🗑️</a>';
                    echo '</div>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" style="text-align: center; padding: 60px; color: #666;">';
                echo '<div style="font-size: 64px; margin-bottom: 20px;">🏢</div>';
                echo '<h3 style="margin-bottom: 10px;">Chưa có rạp chiếu nào</h3>';
                echo '<p>Hãy thêm rạp chiếu đầu tiên để bắt đầu.</p>';
                echo '<a href="?page=theaters&action=add" class="btn btn-primary" style="margin-top: 15px;">🏢 Thêm rạp đầu tiên</a>';
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

function filterTheaters(status) {
    const table = document.getElementById('theaters-table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (status === '' || row.getAttribute('data-status') === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function editScreen(screenId, currentName, currentCapacity) {
    const newName = prompt("Nhập tên phòng mới:", currentName);
    const newCapacity = prompt("Nhập sức chứa mới:", currentCapacity);
    
    if (newName && newCapacity && (newName !== currentName || newCapacity != currentCapacity)) {
        // Tạo form ẩn để submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const nameInput = document.createElement('input');
        nameInput.name = 'screen_name';
        nameInput.value = newName;
        
        const capacityInput = document.createElement('input');
        capacityInput.name = 'capacity';
        capacityInput.value = newCapacity;
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'edit_screen';
        
        const idInput = document.createElement('input');
        idInput.name = 'screen_id';
        idInput.value = screenId;
        
        form.appendChild(nameInput);
        form.appendChild(capacityInput);
        form.appendChild(actionInput);
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 