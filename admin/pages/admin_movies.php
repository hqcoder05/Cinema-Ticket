<?php
// Khởi tạo biến
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

// Xử lý các action POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if ($action == 'add' || $action == 'edit') {
            // Validate và sanitize input
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $duration = intval($_POST['duration']);
            $genre = trim($_POST['genre']);
            $release_date = $_POST['release_date'];
            $poster_url = trim($_POST['poster_url']);
            $status = $_POST['status'];
            $rating = floatval($_POST['rating']);
            
            // Validation
            $errors = [];
            if (empty($title)) $errors[] = "Tên phim không được để trống";
            if ($duration <= 0) $errors[] = "Thời lượng phải lớn hơn 0";
            if ($rating < 0 || $rating > 10) $errors[] = "Đánh giá phải từ 0 đến 10";
            if (!empty($poster_url) && !filter_var($poster_url, FILTER_VALIDATE_URL)) {
                $errors[] = "URL poster không hợp lệ";
            }
            
            if (!empty($errors)) {
                $message = implode("<br>", $errors);
                $message_type = 'error';
            } else {
                if ($action == 'add') {
                    // Kiểm tra trùng tên phim
                    $check_sql = "SELECT id FROM movies WHERE title = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("s", $title);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = "Tên phim đã tồn tại!";
                        $message_type = 'error';
                    } else {
                        $sql = "INSERT INTO movies (title, description, duration, genre, release_date, poster_url, status, rating, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssissssd", $title, $description, $duration, $genre, $release_date, $poster_url, $status, $rating);
                        
                        if ($stmt->execute()) {
                            echo '<script>alert("✅ Thêm phim thành công!"); window.location.href = "?page=movies";</script>';
                            exit;
                        } else {
                            throw new Exception("Lỗi database: " . $conn->error);
                        }
                    }
                } else { // edit
                    // Kiểm tra trùng tên phim (trừ phim hiện tại)
                    $check_sql = "SELECT id FROM movies WHERE title = ? AND id != ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("si", $title, $movie_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = "Tên phim đã tồn tại!";
                        $message_type = 'error';
                    } else {
                        $sql = "UPDATE movies SET title = ?, description = ?, duration = ?, genre = ?, release_date = ?, poster_url = ?, status = ?, rating = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssissssdi", $title, $description, $duration, $genre, $release_date, $poster_url, $status, $rating, $movie_id);
                        
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                echo '<script>alert("✅ Cập nhật phim thành công!"); window.location.href = "?page=movies";</script>';
                                exit;
                            } else {
                                echo '<script>alert("ℹ️ Không có thay đổi nào được thực hiện!"); window.location.href = "?page=movies";</script>';
                                exit;
                            }
                        } else {
                            throw new Exception("Lỗi database: " . $conn->error);
                        }
                    }
                }
            }
        } elseif ($action == 'soft_delete') {
            // Soft delete - chuyển trạng thái thành 'ended'
            $sql = "UPDATE movies SET status = 'ended' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $movie_id);
            
            if ($stmt->execute()) {
                echo '<script>alert("✅ Đã chuyển phim thành \'Ngừng chiếu\'!"); window.location.href = "?page=movies";</script>';
            } else {
                echo '<script>alert("❌ Có lỗi xảy ra: ' . addslashes($conn->error) . '"); window.location.href = "?page=movies";</script>';
            }
        }
    } catch (Exception $e) {
        echo '<script>alert("❌ Lỗi: ' . addslashes($e->getMessage()) . '"); window.location.href = "?page=movies";</script>';
    }
}

// Xử lý xóa phim (GET request)
if ($action == 'delete' && $movie_id > 0) {
    try {
        // Kiểm tra ràng buộc foreign key trước khi xóa
        $check_showtimes = "SELECT COUNT(*) as count FROM showtimes WHERE movie_id = ?";
        $stmt_check = $conn->prepare($check_showtimes);
        $stmt_check->bind_param("i", $movie_id);
        $stmt_check->execute();
        $showtime_result = $stmt_check->get_result();
        $showtime_count = $showtime_result->fetch_assoc()['count'];
        
        // Kiểm tra bookings qua showtimes
        $check_bookings = "SELECT COUNT(*) as count FROM bookings b 
                          INNER JOIN showtimes s ON b.showtime_id = s.id 
                          WHERE s.movie_id = ?";
        $stmt_bookings = $conn->prepare($check_bookings);
        $stmt_bookings->bind_param("i", $movie_id);
        $stmt_bookings->execute();
        $booking_result = $stmt_bookings->get_result();
        $booking_count = $booking_result->fetch_assoc()['count'];
        
        // Luôn cho phép xóa nhưng cảnh báo mạnh nếu có ràng buộc
        if ($showtime_count > 0 || $booking_count > 0) {
            echo '<script>
                if (confirm("🚨 CẢNH BÁO XÓA VĨNH VIỄN!\\n\\n" +
                           "Phim này có:\\n" +
                           "• ' . $showtime_count . ' lịch chiếu\\n" +
                           "• ' . $booking_count . ' vé đã bán\\n\\n" +
                           "⚠️ XÓA SẼ MẤT TẤT CẢ DỮ LIỆU!\\n" +
                           "Bạn có CHẮC CHẮN muốn xóa vĩnh viễn?")) {
                    // User chọn xóa dù có ràng buộc
                    var finalConfirm = confirm("❌ XÁC NHẬN LẦN CUỐI:\\n\\nPhim sẽ bị XÓA VĨNH VIỄN cùng với TẤT CẢ lịch chiếu và vé đã bán!\\n\\nKhông thể hoàn tác!");
                    if (finalConfirm) {
                        window.location.href = "?page=movies&action=force_delete&id=' . $movie_id . '";
                    } else {
                        window.location.href = "?page=movies";
                    }
                } else {
                    window.location.href = "?page=movies";
                }
            </script>';
        } else {
            // Xóa an toàn - không có ràng buộc
            $sql = "DELETE FROM movies WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $movie_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo '<script>alert("✅ Xóa phim thành công!"); window.location.href = "?page=movies";</script>';
                } else {
                    echo '<script>alert("❌ Không tìm thấy phim để xóa!"); window.location.href = "?page=movies";</script>';
                }
            } else {
                echo '<script>alert("❌ Lỗi: ' . addslashes($conn->error) . '"); window.location.href = "?page=movies";</script>';
            }
        }
    } catch (Exception $e) {
        echo '<script>alert("❌ Lỗi: ' . addslashes($e->getMessage()) . '"); window.location.href = "?page=movies";</script>';
    }
    exit;
}

// Xử lý soft delete phim (GET request) 
if ($action == 'soft_delete' && $movie_id > 0) {
    try {
        $sql = "UPDATE movies SET status = 'ended' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        
        if ($stmt->execute()) {
            echo '<script>alert("✅ Đã chuyển phim thành \'Ngừng chiếu\'!"); window.location.href = "?page=movies";</script>';
        } else {
            echo '<script>alert("❌ Có lỗi xảy ra: ' . addslashes($conn->error) . '"); window.location.href = "?page=movies";</script>';
        }
    } catch (Exception $e) {
        echo '<script>alert("❌ Lỗi: ' . addslashes($e->getMessage()) . '"); window.location.href = "?page=movies";</script>';
    }
    exit;
}

// Xử lý force delete phim (GET request) - Xóa có ràng buộc
if ($action == 'force_delete' && $movie_id > 0) {
    try {
        // Xóa tất cả bookings liên quan đến phim này
        $delete_bookings = "DELETE b FROM bookings b 
                           INNER JOIN showtimes s ON b.showtime_id = s.id 
                           WHERE s.movie_id = ?";
        $stmt_del_bookings = $conn->prepare($delete_bookings);
        $stmt_del_bookings->bind_param("i", $movie_id);
        $stmt_del_bookings->execute();
        
        // Xóa tất cả showtimes của phim
        $delete_showtimes = "DELETE FROM showtimes WHERE movie_id = ?";
        $stmt_del_showtimes = $conn->prepare($delete_showtimes);
        $stmt_del_showtimes->bind_param("i", $movie_id);
        $stmt_del_showtimes->execute();
        
        // Cuối cùng xóa phim
        $sql = "DELETE FROM movies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo '<script>alert("✅ Đã XÓA VĨNH VIỄN phim và tất cả dữ liệu liên quan!"); window.location.href = "?page=movies";</script>';
            } else {
                echo '<script>alert("❌ Không tìm thấy phim để xóa!"); window.location.href = "?page=movies";</script>';
            }
        } else {
            echo '<script>alert("❌ Lỗi: ' . addslashes($conn->error) . '"); window.location.href = "?page=movies";</script>';
        }
    } catch (Exception $e) {
        echo '<script>alert("❌ Lỗi: ' . addslashes($e->getMessage()) . '"); window.location.href = "?page=movies";</script>';
    }
    exit;
}

// Xử lý toggle status phim (GET request) - Tạm ngưng/Kích hoạt
if ($action == 'toggle_status' && $movie_id > 0) {
    try {
        // Lấy trạng thái hiện tại
        $current_status_sql = "SELECT status FROM movies WHERE id = ?";
        $current_stmt = $conn->prepare($current_status_sql);
        $current_stmt->bind_param("i", $movie_id);
        $current_stmt->execute();
        $current_result = $current_stmt->get_result();
        
        if ($current_result->num_rows > 0) {
            $current_status = $current_result->fetch_assoc()['status'];
            $new_status = ($current_status == 'showing') ? 'ended' : 'showing';
            
            $update_sql = "UPDATE movies SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_status, $movie_id);
            
            if ($update_stmt->execute()) {
                if ($update_stmt->affected_rows > 0) {
                    $status_text = ($new_status == 'showing') ? 'Đang chiếu' : 'Ngừng chiếu';
                    echo '<script>alert("✅ Đã chuyển trạng thái thành: ' . $status_text . '"); window.location.href = "?page=movies";</script>';
                } else {
                    echo '<script>alert("❌ Không tìm thấy phim để cập nhật!"); window.location.href = "?page=movies";</script>';
                }
            } else {
                echo '<script>alert("❌ Lỗi: ' . addslashes($conn->error) . '"); window.location.href = "?page=movies";</script>';
            }
        } else {
            echo '<script>alert("❌ Không tìm thấy phim!"); window.location.href = "?page=movies";</script>';
        }
    } catch (Exception $e) {
        echo '<script>alert("❌ Lỗi: ' . addslashes($e->getMessage()) . '"); window.location.href = "?page=movies";</script>';
    }
    exit;
}

// Form thêm/sửa phim
if ($action == 'add' || $action == 'edit') {
    $movie = null;
    if ($action == 'edit' && $movie_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $movie = $result->fetch_assoc();
        
        if (!$movie) {
            echo '<script>alert("❌ Không tìm thấy phim!"); window.location.href = "?page=movies";</script>';
            exit;
        }
    }
?>

<div class="page-header">
    <h2>
        <i class="fas fa-film"></i> 
        <?php echo $action == 'add' ? 'Thêm phim mới' : 'Chỉnh sửa phim'; ?>
    </h2>
    <p class="page-subtitle">
        <?php echo $action == 'add' ? 'Thêm phim mới vào hệ thống' : 'Cập nhật thông tin phim'; ?>
    </p>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 2rem;">
    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'error' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
            Thông tin phim
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" id="movieForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-film"></i> Tên phim *
                        </label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo $movie ? htmlspecialchars($movie['title']) : ''; ?>" 
                               required placeholder="VD: Avengers: Endgame" maxlength="255">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tags"></i> Thể loại
                        </label>
                        <input type="text" name="genre" class="form-control"
                               value="<?php echo $movie ? htmlspecialchars($movie['genre']) : ''; ?>" 
                               placeholder="VD: Hành động, Phiêu lưu, Khoa học viễn tưởng" maxlength="100">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-clock"></i> Thời lượng (phút) *
                        </label>
                        <input type="number" name="duration" class="form-control"
                               value="<?php echo $movie ? $movie['duration'] : ''; ?>" 
                               required placeholder="120" min="1" max="999">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar"></i> Ngày khởi chiếu
                        </label>
                        <input type="date" name="release_date" class="form-control"
                               value="<?php echo $movie ? $movie['release_date'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-star"></i> Đánh giá (0-10)
                        </label>
                        <input type="number" step="0.1" min="0" max="10" name="rating" class="form-control"
                               value="<?php echo $movie ? $movie['rating'] : ''; ?>" 
                               placeholder="8.5">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i> URL Poster
                        </label>
                        <input type="url" name="poster_url" class="form-control" id="posterUrl"
                               value="<?php echo $movie ? htmlspecialchars($movie['poster_url']) : ''; ?>" 
                               placeholder="https://example.com/poster.jpg"
                               onchange="previewPoster()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-toggle-on"></i> Trạng thái
                        </label>
                        <select name="status" class="form-control" required>
                            <option value="showing" <?php echo ($movie && $movie['status'] == 'showing') ? 'selected' : ''; ?>>
                                🎬 Đang chiếu
                            </option>
                            <option value="coming_soon" <?php echo ($movie && $movie['status'] == 'coming_soon') ? 'selected' : ''; ?>>
                                🔜 Sắp chiếu
                            </option>
                            <option value="ended" <?php echo ($movie && $movie['status'] == 'ended') ? 'selected' : ''; ?>>
                                🚫 Ngừng chiếu
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-align-left"></i> Mô tả phim
                </label>
                <textarea name="description" rows="4" class="form-control" 
                          placeholder="Mô tả nội dung phim, diễn viên chính, đạo diễn..."><?php echo $movie ? htmlspecialchars($movie['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-eye"></i> Xem trước poster
                </label>
                <div id="posterPreview" class="poster-preview">
                    <?php if ($movie && $movie['poster_url']): ?>
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="Poster preview" id="previewImg">
                    <?php else: ?>
                        <div class="no-poster">
                            <i class="fas fa-image"></i>
                            <p>Chưa có poster</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'save'; ?>"></i>
                    <?php echo $action == 'add' ? 'Thêm phim' : 'Cập nhật'; ?>
                </button>
                <a href="?page=movies" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.row { display: flex; margin: 0 -0.5rem; }
.col-md-4 { flex: 0 0 33.333%; padding: 0 0.5rem; }
.col-md-6 { flex: 0 0 50%; padding: 0 0.5rem; }
.col-md-8 { flex: 0 0 66.667%; padding: 0 0.5rem; }

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
    margin-bottom: 1rem;
}
.alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
.alert-warning { background: #fff3cd; color: #856404; border-color: #ffeaa7; }

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e3e6f0;
}

.poster-preview {
    max-width: 200px;
    height: 300px;
    border: 2px dashed #e3e6f0;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.poster-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
    border-radius: 0.375rem;
}

.no-poster {
    text-align: center;
    color: #6c757d;
}

.no-poster i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

.no-poster-small {
    width: 50px;
    height: 75px;
    background: #f8f9fa;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 1.2rem;
}

.movie-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.movie-date {
    color: #6c757d;
    font-size: 0.75rem;
}

.genre-tag {
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    color: #495057;
    border: 1px solid #e9ecef;
}

.rating {
    color: #ffc107;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    color: #e3e6f0;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: #495057;
}

.filters-row {
    display: flex;
    gap: 1rem;
    align-items: center;
}

@media (max-width: 768px) {
    .actions-container {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .filters-row {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table {
        min-width: 800px;
    }
}
</style>

<script>
function previewPoster() {
    const url = document.getElementById('posterUrl').value;
    const preview = document.getElementById('posterPreview');
    
    if (url) {
        preview.innerHTML = '<img src="' + url + '" alt="Poster preview" id="previewImg" onerror="showNoPoster()">';
    } else {
        showNoPoster();
    }
}

function showNoPoster() {
    document.getElementById('posterPreview').innerHTML = 
        '<div class="no-poster"><i class="fas fa-image"></i><p>Poster không tải được</p></div>';
}

// Form validation
document.getElementById('movieForm').addEventListener('submit', function(e) {
    const title = document.querySelector('input[name="title"]').value.trim();
    const duration = document.querySelector('input[name="duration"]').value;
    
    if (!title) {
        alert('Vui lòng nhập tên phim!');
        e.preventDefault();
        return false;
    }
    
    if (!duration || duration <= 0) {
        alert('Vui lòng nhập thời lượng hợp lệ!');
        e.preventDefault();
        return false;
    }
});
</script>

<?php } else { 
    // Trang danh sách phim
?>

<div class="page-header">
    <h2><i class="fas fa-film"></i> Quản lý Phim</h2>
    <p class="page-subtitle">Quản lý danh sách phim trong hệ thống</p>
</div>

<div class="actions-container">
    <div class="actions-left">
        <a href="?page=movies&action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm phim mới
        </a>
    </div>
    <div class="actions-right">
        <div class="filters-row">
            <input type="text" placeholder="🔍 Tìm kiếm phim..." 
                   class="search-input" id="searchInput"
                   onkeyup="searchMovies()">
            <select class="filter-select" onchange="filterByStatus(this.value)">
                <option value="">Tất cả trạng thái</option>
                <option value="showing">Đang chiếu</option>
                <option value="coming_soon">Sắp chiếu</option>
                <option value="ended">Ngừng chiếu</option>
            </select>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="table" id="moviesTable">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th style="width: 80px;">Poster</th>
                <th>Tên phim</th>
                <th style="width: 120px;">Thể loại</th>
                <th style="width: 100px;">Thời lượng</th>
                <th style="width: 80px;">Đánh giá</th>
                <th style="width: 120px;">Trạng thái</th>
                <th style="width: 150px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM movies ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while($movie = $result->fetch_assoc()) {
                    $status_config = [
                        'showing' => ['class' => 'status-confirmed', 'icon' => '🎬', 'text' => 'Đang chiếu'],
                        'coming_soon' => ['class' => 'status-pending', 'icon' => '🔜', 'text' => 'Sắp chiếu'],
                        'ended' => ['class' => 'status-cancelled', 'icon' => '🚫', 'text' => 'Ngừng chiếu']
                    ];
                    
                    $status = $status_config[$movie['status']] ?? $status_config['ended'];
                    
                    echo '<tr data-status="' . $movie['status'] . '" data-search="' . strtolower($movie['title'] . ' ' . $movie['genre']) . '">';
                    
                    // ID
                    echo '<td><strong>#' . $movie['id'] . '</strong></td>';
                    
                    // Poster
                    echo '<td>';
                    if ($movie['poster_url']) {
                        echo '<div class="movie-poster">';
                        echo '<img src="' . htmlspecialchars($movie['poster_url']) . '" alt="' . htmlspecialchars($movie['title']) . '" onerror="this.src=\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNzUiIHZpZXdCb3g9IjAgMCA1MCA3NSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9Ijc1IiBmaWxsPSIjRjhGOUZBIi8+CjxwYXRoIGQ9Ik0yNSAzNUMyNy43NjE0IDM1IDMwIDMyLjc2MTQgMzAgMzBDMzAgMjcuMjM4NiAyNy43NjE0IDI1IDI1IDI1QzIyLjIzODYgMjUgMjAgMjcuMjM4NiAyMCAzMEMyMCAzMi43NjE0IDIyLjIzODYgMzUgMjUgMzVaIiBmaWxsPSIjNkM3NTdEIi8+Cjwvc3ZnPgo=\'">';
                        echo '</div>';
                    } else {
                        echo '<div class="no-poster-small"><i class="fas fa-image"></i></div>';
                    }
                    echo '</td>';
                    
                    // Tên phim
                    echo '<td>';
                    echo '<div class="movie-title">' . htmlspecialchars($movie['title']) . '</div>';
                    if ($movie['release_date']) {
                        echo '<small class="movie-date"><i class="fas fa-calendar"></i> ' . date('d/m/Y', strtotime($movie['release_date'])) . '</small>';
                    }
                    echo '</td>';
                    
                    // Thể loại
                    echo '<td>';
                    if ($movie['genre']) {
                        echo '<span class="genre-tag">' . htmlspecialchars($movie['genre']) . '</span>';
                    } else {
                        echo '<span class="text-muted">Chưa có</span>';
                    }
                    echo '</td>';
                    
                    // Thời lượng
                    echo '<td><strong>' . $movie['duration'] . '</strong> phút</td>';
                    
                    // Đánh giá
                    echo '<td>';
                    if ($movie['rating']) {
                        echo '<span class="rating"><i class="fas fa-star"></i> ' . $movie['rating'] . '</span>';
                    } else {
                        echo '<span class="text-muted">N/A</span>';
                    }
                    echo '</td>';
                    
                    // Trạng thái
                    echo '<td>';
                    echo '<span class="status-badge ' . $status['class'] . '">';
                    echo $status['icon'] . ' ' . $status['text'];
                    echo '</span>';
                    echo '</td>';
                    
                    // Hành động
                    echo '<td>';
                    echo '<div class="action-buttons">';
                    
                    // Nút sửa
                    echo '<a href="?page=movies&action=edit&id=' . $movie['id'] . '" class="btn btn-primary btn-sm" title="Chỉnh sửa phim">';
                    echo '<i class="fas fa-edit"></i>';
                    echo '</a>';
                    
                    // Nút tạm ngưng/kích hoạt
                    if ($movie['status'] == 'showing') {
                        echo '<a href="?page=movies&action=toggle_status&id=' . $movie['id'] . '" class="btn btn-warning btn-sm" title="Tạm ngưng chiếu phim" onclick="return confirm(\'🔶 Tạm ngưng chiếu phim này?\\n\\nPhim sẽ chuyển thành trạng thái \\\"Ngừng chiếu\\\" nhưng vẫn giữ nguyên dữ liệu.\')">';
                        echo '<i class="fas fa-stop-circle"></i>';
                        echo '</a>';
                    } else {
                        echo '<a href="?page=movies&action=toggle_status&id=' . $movie['id'] . '" class="btn btn-success btn-sm" title="Kích hoạt lại phim" onclick="return confirm(\'🔶 Kích hoạt lại phim này?\\n\\nPhim sẽ chuyển thành trạng thái \\\"Đang chiếu\\\".\')">';
                        echo '<i class="fas fa-play-circle"></i>';
                        echo '</a>';
                    }
                    
                    // Nút xóa vĩnh viễn
                    echo '<a href="?page=movies&action=delete&id=' . $movie['id'] . '" class="btn btn-danger btn-sm" title="XÓA VĨNH VIỄN phim" onclick="return confirm(\'🗑️ XÓA VĨNH VIỄN PHIM?\\n\\nPhim sẽ bị xóa hoàn toàn khỏi hệ thống!\\n\\n⚠️ Khuyến nghị: Dùng \\\"Tạm ngưng\\\" thay vì xóa.\')">';
                    echo '<i class="fas fa-trash-alt"></i>';
                    echo '</a>';
                    
                    echo '</div>';
                    echo '</td>';
                    
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8" class="no-data">';
                echo '<div class="empty-state">';
                echo '<i class="fas fa-film fa-3x"></i>';
                echo '<h3>Chưa có phim nào</h3>';
                echo '<p>Hãy thêm phim đầu tiên để bắt đầu quản lý.</p>';
                echo '<a href="?page=movies&action=add" class="btn btn-primary">';
                echo '<i class="fas fa-plus"></i> Thêm phim đầu tiên';
                echo '</a>';
                echo '</div>';
                echo '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<style>
.movie-poster {
    width: 50px;
    height: 75px;
    border-radius: 0.375rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-poster-small {
    width: 50px;
    height: 75px;
    background: #f8f9fa;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 1.2rem;
}

.movie-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.movie-date {
    color: #6c757d;
    font-size: 0.75rem;
}

.genre-tag {
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    color: #495057;
    border: 1px solid #e9ecef;
}

.rating {
    color: #ffc107;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    color: #e3e6f0;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: #495057;
}

.filters-row {
    display: flex;
    gap: 1rem;
    align-items: center;
}

@media (max-width: 768px) {
    .actions-container {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .filters-row {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table {
        min-width: 800px;
    }
}
</style>

<script>
function searchMovies() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#moviesTable tbody tr');
    
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        const visible = !searchTerm || searchData.includes(searchTerm);
        row.style.display = visible ? '' : 'none';
    });
    
    updateResultsCount();
}

function filterByStatus(status) {
    const rows = document.querySelectorAll('#moviesTable tbody tr');
    
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        const visible = !status || rowStatus === status;
        row.style.display = visible ? '' : 'none';
    });
    
    updateResultsCount();
}

function updateResultsCount() {
    const visibleRows = document.querySelectorAll('#moviesTable tbody tr:not([style*="display: none"])');
    const totalRows = document.querySelectorAll('#moviesTable tbody tr');
    
    // Có thể thêm hiển thị số kết quả ở đây
    console.log(`Hiển thị ${visibleRows.length}/${totalRows.length} phim`);
}

// Auto-refresh mỗi 30 giây để cập nhật dữ liệu
setInterval(function() {
    // Có thể thêm AJAX refresh ở đây
}, 30000);
</script>

<?php } ?>

<script>
// Global confirmation function
window.confirmDelete = function(message) {
    return confirm(message || 'Bạn có chắc chắn muốn thực hiện hành động này?');
};

// Show success/error messages
<?php if ($message): ?>
setTimeout(function() {
    const alertElement = document.querySelector('.alert');
    if (alertElement) {
        alertElement.style.opacity = '0';
        setTimeout(() => alertElement.remove(), 300);
    }
}, 5000);
<?php endif; ?>
</script> 