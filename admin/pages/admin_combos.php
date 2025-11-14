<?php error_reporting(E_ALL); ini_set('display_errors', 1); ?>
<?php
// kết nối database
require_once __DIR__ .'/../config/config.php';

// Khởi tạo biến
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$combo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] =='POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = trim($_POST['image_url']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    if ($action == 'add') {
        $sql = "INSERT INTO combos (name, description, price, image_url, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $name, $description, $price, $image_url, $status);
        if ($stmt->execute()) {
            $message = "Thêm combo thành công!";
            $message_type = 'success';
            // Thêm JavaScript để ẩn form sau khi thêm thành công
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    showSuccessNotification("🎉 Thêm combo thành công!", function() {
                        const editForm = document.querySelector(".card.shadow-sm");
                        if (editForm) {
                            editForm.style.transition = "all 0.5s ease-out";
                            editForm.style.transform = "translateY(-20px)";
                            editForm.style.opacity = "0";
                            
                            setTimeout(function() {
                                editForm.style.display = "none";
                                document.querySelector(".card.shadow-sm:last-child").scrollIntoView({ 
                                    behavior: "smooth", 
                                    block: "start" 
                                });
                            }, 500);
                        }
                    });
                });
            </script>';
        } else {
            $message = "Thêm combo thất bại!" .$conn->error;
            $message_type = 'error';
        }
    } elseif ($action =='edit' && $combo_id > 0) {
        $sql = "UPDATE combos SET name =?, description = ?, price =?, image_url=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $name, $description, $price, $image_url, $status, $combo_id);
        if ($stmt->execute()) {
            $message = "Cập nhật combo thành công!";
            $message_type = 'success';
            // Thêm JavaScript để ẩn form sau khi cập nhật thành công
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Hiển thị thông báo thành công đẹp mắt
                    showSuccessNotification("🎉 Cập nhật combo thành công!", function() {
                        // Ẩn form chỉnh sửa với hiệu ứng
                        const editForm = document.querySelector(".card.shadow-sm");
                        if (editForm) {
                            editForm.style.transition = "all 0.5s ease-out";
                            editForm.style.transform = "translateY(-20px)";
                            editForm.style.opacity = "0";
                            
                            setTimeout(function() {
                                editForm.style.display = "none";
                                // Scroll mượt đến danh sách
                                document.querySelector(".card.shadow-sm:last-child").scrollIntoView({ 
                                    behavior: "smooth", 
                                    block: "start" 
                                });
                            }, 500);
                        }
                    });
                });
            </script>';
        } else {
            $message = "Cập nhật combo thất bại!" .$conn->error;
            $message_type = 'error';
        }
    }
}

if ($action == 'delete' && $combo_id > 0) {
    $sql = "DELETE FROM combos WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $combo_id);
    if ($stmt->execute()) {
        // Kiểm tra xem có thực sự xóa được record không
        if ($stmt->affected_rows > 0) {
            // Sử dụng header redirect thay vì JavaScript để tránh conflict với auto logout
            $_SESSION['delete_success'] = "🗑️ Xóa combo thành công!";
            header("Location: ?page=combos");
            exit();
        } else {
            $message = "Không tìm thấy combo để xóa!";
            $message_type = 'error';
        }
    } else {
        $message = "Xóa combo thất bại! " . $conn->error;
        $message_type = 'error';
    }
}

// Nếu là sửa, lấy dữ liệu combo
$combo = null;
if ($action == 'edit' && $combo_id > 0) {
    $sql = "SELECT * FROM combos WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $combo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $combo = $result->fetch_assoc();
}
?>
<div class="container-fluid px-4">
    <h3 class="mt-4 mb-2">
        <i class="fas fa-cocktail"></i> Quản lý Combo
    </h3>
    <p class="mb-4 text-muted">Quản lý danh sách combo bắp nước/đồ ăn trong hệ thống</p>

    <?php 
    // Hiển thị thông báo xóa thành công từ session
    if (isset($_SESSION['delete_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['delete_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showSuccessNotification("<?= $_SESSION['delete_success']; ?>");
            });
        </script>
        <?php unset($_SESSION['delete_success']); ?>
    <?php endif; ?>

    <?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="card shadow-sm mb-4" style="max-width: 600px;">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="fas fa-<?= $action == 'edit' ? 'edit' : 'plus' ?>"></i>
                <?= $action == 'edit' ? 'Chỉnh sửa combo' : 'Thêm combo mới'; ?>
            </h5>
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type == 'success' ? 'success' : 'danger'; ?>"><?= $message; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Tên combo</label>
                    <input type="text" name="name" class="form-control" required value="<?= $combo['name'] ?? ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control"><?= $combo['description'] ?? ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Giá (VNĐ)</label>
                    <input type="number" name="price" min="0" step="1000" class="form-control" required value="<?= $combo['price'] ?? ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Link ảnh</label>
                    <input type="text" name="image_url" class="form-control" value="<?= $combo['image_url'] ?? ''; ?>" oninput="document.getElementById('preview-img').src=this.value">
                </div>
                <div class="mb-3">
                    <label class="form-label">Xem trước ảnh</label><br>
                    <img id="preview-img" src="<?= $combo['image_url'] ?? '' ?>" alt="Preview" style="width:100px; height:100px; object-fit:cover; border:1px solid #eee; border-radius:8px;">
                </div>
                <div class="mb-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= (($combo['status'] ?? '') == 'active') ? 'selected' : '' ?>>Hiện</option>
                        <option value="inactive" <?= (($combo['status'] ?? '') == 'inactive') ? 'selected' : '' ?>>Ẩn</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= $action == 'edit' ? 'Cập nhật' : 'Thêm mới'; ?></button>
                <a href="?page=combos" class="btn btn-secondary">Hủy</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-list"></i> Danh sách combo bắp nước</h5>
                <a href="?page=combos&action=add" class="btn btn-success">
                    <i class="fas fa-plus"></i> Thêm combo mới
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ảnh</th>
                            <th>Tên combo</th>
                            <th>Mô tả</th>
                            <th>Giá</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sql = "SELECT * FROM combos ORDER BY id DESC";
                    $result = $conn->query($sql);
                    $stt = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td>#<?= $stt++; ?></td>
                        <td>
                            <?php if ($row['image_url']): ?>
                                <img src="<?= $row['image_url']; ?>" alt="Combo" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #eee;">
                            <?php else: ?>
                                <span class="text-muted">Không có ảnh</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($row['name']); ?></strong></td>
                        <td style="max-width:200px;"><?= nl2br(htmlspecialchars($row['description'])); ?></td>
                        <td class="text-danger fw-bold"><?= number_format($row['price']); ?> VNĐ</td>
                        <td>
                            <?php if ($row['status'] == 'active'): ?>
                                <span class="badge bg-success">Hiện 👁️</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Ẩn 🚫</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?page=combos&action=edit&id=<?= $row['id']; ?>" class="btn btn-warning btn-sm" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?page=combos&action=delete&id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa combo này?');" title="Xóa">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function showSuccessNotification(message, callback) {
    // Tạo thông báo overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    `;
    
    // Tạo thông báo box
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 40px 60px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        transform: scale(0.5);
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        max-width: 400px;
        font-family: Arial, sans-serif;
    `;
    
    notification.innerHTML = `
        <div style="font-size: 48px; margin-bottom: 20px;">✅</div>
        <h3 style="margin: 0 0 10px 0; font-size: 24px; font-weight: bold;">${message}</h3>
        <p style="margin: 0; font-size: 16px; opacity: 0.9;">Thao tác đã được thực hiện thành công!</p>
    `;
    
    overlay.appendChild(notification);
    document.body.appendChild(overlay);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'scale(1)';
        notification.style.opacity = '1';
    }, 10);
    
    // Auto close after 2 seconds
    setTimeout(() => {
        notification.style.transform = 'scale(0.8)';
        notification.style.opacity = '0';
        overlay.style.opacity = '0';
        
        setTimeout(() => {
            document.body.removeChild(overlay);
            if (callback) callback();
        }, 300);
    }, 2000);
    
    // Click to close
    overlay.addEventListener('click', function() {
        notification.style.transform = 'scale(0.8)';
        notification.style.opacity = '0';
        overlay.style.opacity = '0';
        
        setTimeout(() => {
            document.body.removeChild(overlay);
            if (callback) callback();
        }, 300);
    });
}
</script>

<style>
/* Thêm CSS cho hiệu ứng mượt mà */
.card.shadow-sm {
    transition: all 0.3s ease-out;
}

.card.shadow-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

/* Animation cho alert */
.alert {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>