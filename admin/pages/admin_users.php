<?php
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Kiểm tra email đã tồn tại
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) {
            echo '<script>alert("❌ Lỗi prepare check email: ' . addslashes($conn->error) . '");</script>';
            return;
        }
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();
        
        if ($check_result->num_rows > 0) {
            echo '<script>alert("Email này đã được sử dụng!");</script>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Kiểm tra column status có tồn tại không
            $check_column_sql = "SHOW COLUMNS FROM users LIKE 'status'";
            $check_column_result = mysqli_query($conn, $check_column_sql);
            $has_status = mysqli_num_rows($check_column_result) > 0;
            
            if ($has_status) {
                $sql = "INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                    return;
                }
                $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
            } else {
                $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                    return;
                }
                $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
            }
            
            if ($stmt->execute()) {
                echo '<script>alert("Thêm người dùng thành công!"); window.location.href = "?page=users";</script>';
            } else {
                echo '<script>alert("❌ Lỗi thực thi: ' . addslashes($stmt->error) . '");</script>';
            }
            $stmt->close();
        }
    } elseif ($action == 'edit') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $password = trim($_POST['password']);
        
        // Kiểm tra email đã tồn tại (trừ user hiện tại)
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) {
            echo '<script>alert("❌ Lỗi prepare check email: ' . addslashes($conn->error) . '");</script>';
            return;
        }
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();
        
        if ($check_result->num_rows > 0) {
            echo '<script>alert("Email này đã được sử dụng!");</script>';
        } else {
            // Kiểm tra column status có tồn tại không
            $check_column_sql = "SHOW COLUMNS FROM users LIKE 'status'";
            $check_column_result = mysqli_query($conn, $check_column_sql);
            $has_status = mysqli_num_rows($check_column_result) > 0;
            
            if (!empty($password)) {
                // Cập nhật với mật khẩu mới
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                if ($has_status) {
                    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, password = ?, role = ?, status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                        return;
                    }
                    $stmt->bind_param("ssssssi", $name, $email, $phone, $hashed_password, $role, $status, $user_id);
                } else {
                    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, password = ?, role = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                        return;
                    }
                    $stmt->bind_param("sssssi", $name, $email, $phone, $hashed_password, $role, $user_id);
                }
            } else {
                // Cập nhật không thay đổi mật khẩu
                if ($has_status) {
                    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                        return;
                    }
                    $stmt->bind_param("sssssi", $name, $email, $phone, $role, $status, $user_id);
                } else {
                    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                        return;
                    }
                    $stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
                }
            }
            
            if ($stmt->execute()) {
                echo '<script>alert("Cập nhật người dùng thành công!"); window.location.href = "?page=users";</script>';
            } else {
                echo '<script>alert("❌ Lỗi thực thi: ' . addslashes($stmt->error) . '");</script>';
            }
            $stmt->close();
        }
    } elseif ($action == 'update_role') {
        $role = $_POST['role'];
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        // Kiểm tra column status có tồn tại không
        $check_column_sql = "SHOW COLUMNS FROM users LIKE 'status'";
        $check_column_result = mysqli_query($conn, $check_column_sql);
        $has_status = mysqli_num_rows($check_column_result) > 0;
        
        if ($has_status) {
            $sql = "UPDATE users SET role = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                return;
            }
            $stmt->bind_param("ssi", $role, $status, $user_id);
        } else {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                echo '<script>alert("❌ Lỗi prepare: ' . addslashes($conn->error) . '");</script>';
                return;
            }
            $stmt->bind_param("si", $role, $user_id);
        }
        
        if ($stmt->execute()) {
            echo '<script>alert("Cập nhật thành công!"); window.location.href = "?page=users";</script>';
        } else {
            echo '<script>alert("❌ Lỗi thực thi: ' . addslashes($stmt->error) . '");</script>';
        }
        $stmt->close();
    }
}

// Xử lý xóa user (GET request) - Soft delete
if ($action == 'delete' && $user_id > 0) {
    try {
        // Kiểm tra xem bảng users có column status không
        $check_column_sql = "SHOW COLUMNS FROM users LIKE 'status'";
        $check_result = mysqli_query($conn, $check_column_sql);
        $has_status = mysqli_num_rows($check_result) > 0;
        
        if ($has_status) {
            // Soft delete - chuyển status thành deleted
            $sql = "UPDATE users SET status = 'deleted' WHERE id = ?";
        } else {
            // Hard delete nếu chưa có column status
            $sql = "DELETE FROM users WHERE id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $delete_type = $has_status ? "ẩn" : "xóa vĩnh viễn";
                echo '<script>alert("✅ Đã ' . $delete_type . ' người dùng thành công!"); window.location.href = "?page=users";</script>';
            } else {
                echo '<script>alert("❌ Không tìm thấy người dùng để xóa!"); window.location.href = "?page=users";</script>';
            }
        } else {
            echo '<script>alert("❌ Lỗi execute: ' . addslashes($stmt->error) . '"); window.location.href = "?page=users";</script>';
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo '<script>alert("❌ Lỗi: ' . addslashes($e->getMessage()) . '"); window.location.href = "?page=users";</script>';
    }
    exit;
}

if ($action == 'add' || $action == 'edit') {
    $user = null;
    if ($action == 'edit' && $user_id > 0) {
        // Kiểm tra xem bảng users có column status không
        $check_column_sql = "SHOW COLUMNS FROM users LIKE 'status'";
        $check_result = mysqli_query($conn, $check_column_sql);
        $has_status = mysqli_num_rows($check_result) > 0;
        
        if ($has_status) {
            $sql = "SELECT * FROM users WHERE id = ? AND (status != 'deleted' OR status IS NULL)";
        } else {
            $sql = "SELECT * FROM users WHERE id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo '<script>alert("❌ Lỗi database: ' . addslashes($conn->error) . '"); window.location.href = "?page=users";</script>';
            exit;
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            echo '<script>alert("Không tìm thấy người dùng này!"); window.location.href = "?page=users";</script>';
            exit;
        }
        
        $stmt->close();
    }
?>

<div class="content-header">
    <h1 class="content-title">👥 Admin - <?php echo $action == 'add' ? 'Thêm người dùng mới' : 'Chỉnh sửa người dùng'; ?></h1>
    <div class="breadcrumb">Admin / Người dùng / <?php echo $action == 'add' ? 'Thêm mới' : 'Chỉnh sửa'; ?></div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Họ tên *</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo $user ? htmlspecialchars($user['name']) : ''; ?>" 
                           required placeholder="VD: Nguyễn Văn A">
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control"
                           value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>" 
                           required placeholder="VD: user@example.com">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?php echo $user ? htmlspecialchars($user['phone']) : ''; ?>" 
                           placeholder="VD: 0901234567">
                </div>
                <div class="form-group">
                    <label class="form-label">Quyền</label>
                    <select name="role" class="form-control">
                        <option value="user" <?php echo ($user && $user['role'] == 'user') ? 'selected' : ''; ?>>👤 Khách hàng</option>
                        <option value="admin" <?php echo ($user && $user['role'] == 'admin') ? 'selected' : ''; ?>>👑 Quản trị viên</option>
                    </select>
                </div>
            </div>
            
            <?php if ($action == 'edit'): ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($user && isset($user['status']) && $user['status'] == 'active') ? 'selected' : 'selected'; ?>>🟢 Hoạt động</option>
                        <option value="blocked" <?php echo ($user && isset($user['status']) && $user['status'] == 'blocked') ? 'selected' : ''; ?>>🔴 Khóa tài khoản</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mật khẩu (để trống nếu không đổi)</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới...">
                </div>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label class="form-label">Mật khẩu *</label>
                <input type="password" name="password" class="form-control" required placeholder="Nhập mật khẩu...">
            </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action == 'add' ? '👥 Thêm người dùng' : '💾 Cập nhật'; ?>
                </button>
                <a href="?page=users" class="btn btn-secondary">❌ Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php } elseif ($action == 'detail' && $user_id > 0) {
    // Lấy thông tin chi tiết user
    $user_sql = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    if ($user_stmt === false) {
        echo '<div style="text-align: center; padding: 50px; color: #e50914;">❌ Lỗi database: ' . htmlspecialchars($conn->error) . '</div>';
        return;
    }
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_stmt->close();
    
    if (!$user) {
        echo '<div style="text-align: center; padding: 50px; color: #666;">Không tìm thấy người dùng này.</div>';
        return;
    }
    
    // Gán giá trị mặc định cho status nếu không có
    $user_status = isset($user['status']) ? $user['status'] : 'active';
    
    // Lấy thống kê đặt vé của user
    $stats_sql = "SELECT COUNT(*) as total_bookings,
                         SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_spent
                  FROM bookings WHERE user_id = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    if ($stats_stmt === false) {
        echo '<div style="text-align: center; padding: 50px; color: #e50914;">❌ Lỗi database stats: ' . htmlspecialchars($conn->error) . '</div>';
        return;
    }
    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();
    
    // Lấy lịch sử đặt vé gần đây
    $bookings_sql = "SELECT b.*, m.title as movie_title, t.name as theater_name, st.show_date, st.show_time
                     FROM bookings b
                     INNER JOIN showtimes st ON b.showtime_id = st.id
                     INNER JOIN movies m ON st.movie_id = m.id
                     INNER JOIN screens s ON st.screen_id = s.id
                     INNER JOIN theaters t ON s.theater_id = t.id
                     WHERE b.user_id = ?
                     ORDER BY b.created_at DESC
                     LIMIT 10";
    $bookings_stmt = $conn->prepare($bookings_sql);
    if ($bookings_stmt === false) {
        echo '<div style="text-align: center; padding: 50px; color: #e50914;">❌ Lỗi database bookings: ' . htmlspecialchars($conn->error) . '</div>';
        return;
    }
    $bookings_stmt->bind_param("i", $user_id);
    $bookings_stmt->execute();
    $bookings_result = $bookings_stmt->get_result();
    $bookings_stmt->close();
?>

<div class="content-header">
    <h1 class="content-title">👤 Admin - Chi tiết người dùng</h1>
    <div class="breadcrumb">Admin / Người dùng / <?php echo htmlspecialchars($user['name']); ?></div>
</div>

<div style="margin-bottom: 20px;">
    <a href="?page=users" class="btn btn-secondary">← Quay lại danh sách</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Thông tin cơ bản -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Thông tin cơ bản</h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #e50914, #ff6b6b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold; margin: 0 auto 15px;">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <h3 style="margin: 0; color: #333;">👤 <?php echo htmlspecialchars($user['name']); ?></h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email:</label>
                <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Số điện thoại:</label>
                <p>📞 <?php echo htmlspecialchars($user['phone']); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Ngày đăng ký:</label>
                <p>📅 <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Quyền:</label>
                <?php
                $role_class = $user['role'] == 'admin' ? 'status-confirmed' : 'status-pending';
                $role_text = $user['role'] == 'admin' ? '👑 Quản trị viên' : '👤 Khách hàng';
                ?>
                <span class="status-badge <?php echo $role_class; ?>"><?php echo $role_text; ?></span>
            </div>
            
            <div class="form-group">
                <label class="form-label">Trạng thái:</label>
                <?php
                $status_class = $user_status == 'active' ? 'status-confirmed' : 'status-cancelled';
                $status_text = $user_status == 'active' ? '🟢 Hoạt động' : '🔴 Khóa';
                ?>
                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Cập nhật quyền -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚙️ Cập nhật quyền</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="?page=users&action=update_role&id=<?php echo $user_id; ?>">
                <div class="form-group">
                    <label class="form-label">Quyền:</label>
                    <select name="role" class="form-control">
                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>👤 Khách hàng</option>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>👑 Quản trị viên</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Trạng thái:</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $user_status == 'active' ? 'selected' : ''; ?>>🟢 Hoạt động</option>
                        <option value="blocked" <?php echo $user_status == 'blocked' ? 'selected' : ''; ?>>🔴 Khóa tài khoản</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Cập nhật</button>
            </form>
        </div>
    </div>
</div>

<!-- Thống kê -->
<div style="margin-bottom: 30px;">
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
            <div class="stat-label">🎫 Tổng đặt vé</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
            <div class="stat-label">✅ Vé thành công</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['total_spent'], 0, ',', '.'); ?> VNĐ</div>
            <div class="stat-label">💰 Tổng chi tiêu</div>
        </div>
    </div>
</div>

<!-- Lịch sử đặt vé -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📝 Lịch sử đặt vé</h3>
    </div>
    
    <?php if ($bookings_result->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Mã đặt vé</th>
                    <th>Phim</th>
                    <th>Rạp</th>
                    <th>Ngày chiếu</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php while($booking = $bookings_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>🎫 <?php echo htmlspecialchars($booking['booking_code']); ?></strong></td>
                        <td>🎬 <?php echo htmlspecialchars($booking['movie_title']); ?></td>
                        <td>🏢 <?php echo htmlspecialchars($booking['theater_name']); ?></td>
                        <td>📅 <?php echo date('d/m/Y H:i', strtotime($booking['show_date'] . ' ' . $booking['show_time'])); ?></td>
                        <td><strong>💰 <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ</strong></td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            $status_icon = '';
                            switch($booking['booking_status']) {
                                case 'confirmed':
                                    $status_class = 'status-confirmed';
                                    $status_text = 'Đã xác nhận';
                                    $status_icon = '✅';
                                    break;
                                case 'pending':
                                    $status_class = 'status-pending';
                                    $status_text = 'Chờ xác nhận';
                                    $status_icon = '⏳';
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled';
                                    $status_text = 'Đã hủy';
                                    $status_icon = '❌';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_icon . ' ' . $status_text; ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #666;">
            <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
            <h3>Chưa có đặt vé nào</h3>
            <p>Người dùng này chưa đặt vé lần nào.</p>
        </div>
    <?php endif; ?>
</div>

<?php } else { ?>

<div class="content-header">
    <h1 class="content-title">👥 Admin - Quản lý người dùng</h1>
    <div class="breadcrumb">Admin / Người dùng / Danh sách</div>
</div>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="?page=users&action=add" class="btn btn-primary">👥 + Thêm người dùng mới</a>
    
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" placeholder="🔍 Tìm theo tên hoặc email..." 
               style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 300px;"
               onkeyup="searchUsers(this)">
        
        <select id="filter-role" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterUsers()">
            <option value="">Tất cả quyền</option>
            <option value="user">Khách hàng</option>
            <option value="admin">Quản trị viên</option>
        </select>
        
        <select id="filter-status" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;" onchange="filterUsers()">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Hoạt động</option>
            <option value="blocked">Đã khóa</option>
        </select>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="exportUsers()" class="btn btn-secondary">📊 Xuất Excel</button>
        <button onclick="printUsers()" class="btn btn-secondary">🖨️ In báo cáo</button>
    </div>
</div>

<div class="card">
    <table class="table" id="users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Người dùng</th>
                <th>Liên hệ</th>
                <th>Quyền</th>
                <th>Trạng thái</th>
                <th>Hoạt động</th>
                <th>Ngày đăng ký</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Debug: Kiểm tra có users nào trong database không
            $debug_sql = "SELECT COUNT(*) as total FROM users";
            $debug_result = mysqli_query($conn, $debug_sql);
            $total_users = $debug_result ? mysqli_fetch_assoc($debug_result)['total'] : 0;
            
            // Debug: Kiểm tra structure bảng users
            $structure_sql = "DESCRIBE users";
            $structure_result = mysqli_query($conn, $structure_sql);
            $has_status_column = false;
            if ($structure_result) {
                while ($row = mysqli_fetch_assoc($structure_result)) {
                    if ($row['Field'] == 'status') {
                        $has_status_column = true;
                        break;
                    }
                }
            }
            
            // Query chính với điều kiện phù hợp
            if ($has_status_column) {
                $sql = "SELECT u.*, 
                               (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
                               (SELECT SUM(total_amount) FROM bookings WHERE user_id = u.id AND payment_status = 'paid') as total_spent
                        FROM users u 
                        WHERE (u.status != 'deleted' OR u.status IS NULL)
                        ORDER BY u.created_at DESC";
            } else {
                // Nếu chưa có column status, lấy tất cả users
                $sql = "SELECT u.*, 
                               (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
                               (SELECT SUM(total_amount) FROM bookings WHERE user_id = u.id AND payment_status = 'paid') as total_spent
                        FROM users u 
                        ORDER BY u.created_at DESC";
            }
            
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($user = mysqli_fetch_assoc($result)) {
                    // Gán giá trị mặc định cho status nếu không có
                    $user_status = isset($user['status']) ? $user['status'] : 'active';
                    echo '<tr data-role="' . $user['role'] . '" data-status="' . $user_status . '" data-search="' . strtolower($user['name'] . ' ' . $user['email']) . '">';
                    echo '<td><strong>#' . $user['id'] . '</strong></td>';
                    echo '<td>';
                    echo '<div style="display: flex; align-items: center; gap: 12px;">';
                    echo '<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #e50914, #ff6b6b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold;">';
                    echo strtoupper(substr($user['name'], 0, 1));
                    echo '</div>';
                    echo '<div>';
                    echo '<div style="font-weight: bold; color: #333;">👤 ' . htmlspecialchars($user['name']) . '</div>';
                    echo '<small style="color: #666;">ID: ' . $user['id'] . '</small>';
                    echo '</div>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td>';
                    echo '<div style="font-size: 14px;">';
                    echo '<div style="margin-bottom: 4px;">📧 ' . htmlspecialchars($user['email']) . '</div>';
                    echo '<div style="color: #666;">📞 ' . htmlspecialchars($user['phone']) . '</div>';
                    echo '</div>';
                    echo '</td>';
                    
                    // Quyền
                    $role_class = $user['role'] == 'admin' ? 'status-confirmed' : 'status-pending';
                    $role_text = $user['role'] == 'admin' ? '👑 Quản trị viên' : '👤 Khách hàng';
                    echo '<td><span class="status-badge ' . $role_class . '">' . $role_text . '</span></td>';
                    
                    // Trạng thái
                    $status_class = $user_status == 'active' ? 'status-confirmed' : 'status-cancelled';
                    $status_text = $user_status == 'active' ? '🟢 Hoạt động' : '🔴 Khóa';
                    echo '<td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>';
                    
                    // Hoạt động
                    echo '<td>';
                    echo '<div style="font-size: 14px;">';
                    echo '<div><strong>🎫 ' . $user['total_bookings'] . '</strong> vé</div>';
                    if ($user['total_spent'] > 0) {
                        echo '<div style="color: #e50914; font-weight: bold;">💰 ' . number_format($user['total_spent'], 0, ',', '.') . ' VNĐ</div>';
                    } else {
                        echo '<div style="color: #666;">💰 0 VNĐ</div>';
                    }
                    echo '</div>';
                    echo '</td>';
                    
                    echo '<td>📅 ' . date('d/m/Y', strtotime($user['created_at'])) . '</td>';
                    echo '<td>';
                    echo '<div style="display: flex; gap: 5px;">';
                    echo '<a href="?page=users&action=detail&id=' . $user['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" title="Xem chi tiết">👁️</a>';
                    echo '<a href="?page=users&action=edit&id=' . $user['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" title="Chỉnh sửa">✏️</a>';
                    echo '<a href="?page=users&action=delete&id=' . $user['id'] . '" class="btn" style="background-color: #dc3545; color: white; padding: 5px 10px; font-size: 12px;" onclick="return confirm(\'⚠️ Bạn có chắc muốn xóa người dùng này?\')" title="Xóa người dùng">🗑️</a>';
                    echo '</div>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                // Hiển thị thông báo chi tiết dựa trên debug info
                echo '<tr><td colspan="8" style="text-align: center; padding: 60px; color: #666;">';
                
                if (!$result) {
                    // Lỗi SQL
                    echo '<div style="font-size: 48px; margin-bottom: 20px; color: #e50914;">⚠️</div>';
                    echo '<h3 style="margin-bottom: 10px; color: #e50914;">Lỗi Database</h3>';
                    echo '<p>Không thể thực thi truy vấn SQL.</p>';
                    echo '<small style="background: #f8f9fa; padding: 10px; border-radius: 5px; display: inline-block; margin-top: 10px; color: #e50914;">';
                    echo 'Error: ' . mysqli_error($conn);
                    echo '</small>';
                } elseif ($total_users == 0) {
                    // Database trống
                    echo '<div style="font-size: 64px; margin-bottom: 20px;">👥</div>';
                    echo '<h3 style="margin-bottom: 10px;">Database trống</h3>';
                    echo '<p>Chưa có người dùng nào trong database.</p>';
                    echo '<p style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;">';
                    echo '<strong>🔧 Cách khắc phục:</strong><br>';
                    echo '1. Import database: <code>mysql -u root -p phimchill < database/data_phimchill.sql</code><br>';
                    echo '2. Hoặc chạy script fix: <code>mysql -u root -p phimchill < database/quick_fix_users.sql</code>';
                    echo '</p>';
                } else {
                    // Có users nhưng bị filter
                    echo '<div style="font-size: 64px; margin-bottom: 20px;">🔍</div>';
                    echo '<h3 style="margin-bottom: 10px;">Không tìm thấy người dùng phù hợp</h3>';
                    echo '<p>Database có <strong>' . $total_users . '</strong> users nhưng bị lọc bởi điều kiện truy vấn.</p>';
                    echo '<small>Status column: ' . ($has_status_column ? '✅ Có' : '❌ Không có') . '</small>';
                    echo '<p style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">';
                    echo '<strong>🔧 Cách khắc phục:</strong><br>';
                    echo 'Chạy script fix: <code>mysql -u root -p phimchill < database/quick_fix_users.sql</code>';
                    echo '</p>';
                }
                
                echo '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php } ?>

<script>
function filterUsers() {
    const roleFilter = document.getElementById('filter-role').value;
    const statusFilter = document.getElementById('filter-status').value;
    const table = document.getElementById('users-table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        let showRow = true;
        
        if (roleFilter && row.getAttribute('data-role') !== roleFilter) {
            showRow = false;
        }
        
        if (statusFilter && row.getAttribute('data-status') !== statusFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function searchUsers(input) {
    const searchTerm = input.value.toLowerCase();
    const table = document.getElementById('users-table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search');
        const showRow = searchData.includes(searchTerm);
        row.style.display = showRow ? '' : 'none';
    });
}

function exportUsers() {
    exportTableToCSV('users-table', 'danh-sach-nguoi-dung');
}

function printUsers() {
    printTable('users-table');
}
</script> 