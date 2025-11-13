<?php
// Sử dụng cùng session name
session_name('CGV_SESSION');
session_start();

// Xóa tất cả session data
session_unset();
session_destroy();

// Tạo session mới (clean session)
session_start();

// Redirect về trang chủ
header("Location: ../../index.php");
exit();
?>
