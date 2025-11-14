<?php
// Kiểm tra session trước khi khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_name('CGV_SESSION');
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nếu khách chọn "Bỏ qua"
    if (isset($_POST['action']) && $_POST['action'] === 'skip') {
        unset($_SESSION['selected_combos']);
        // Chuyển sang trang thanh toán qua routing chính
        header('Location: ../../index.php?quanly=thanh-toan');
        exit;
    }

    // Nếu khách chọn "Tiếp tục"
    $selected_combos = [];
    if (isset($_POST['combo_qty']) && is_array($_POST['combo_qty'])) {
        foreach ($_POST['combo_qty'] as $combo_id => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $selected_combos[$combo_id] = $qty;
            }
        }
    }
    $_SESSION['selected_combos'] = $selected_combos;

    // Chuyển sang trang thanh toán qua routing chính
    header('Location: ../../index.php?quanly=thanh-toan');
    exit;
}
?>