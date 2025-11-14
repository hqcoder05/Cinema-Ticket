<?php 
// Đảm bảo session được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_name('CGV_SESSION');
    session_start();
}
require_once 'admin/config/config.php'; 
?>

<div class="main-content">
    <div class="movies-container">
        <h2 style="color: #fff; text-align: center; margin: 20px 0;">DANH SÁCH PHIM</h2>
        
        <!-- Bộ lọc thể loại -->
        <div class="filter-section" style="text-align: center; margin: 20px 0;">
            <?php
            $selected_genre = isset($_GET['genre']) ? $_GET['genre'] : '';
            if ($selected_genre) {
                echo '<p style="color: #e71a0f; font-size: 18px; margin-bottom: 10px;">Thể loại: ' . htmlspecialchars($selected_genre) . '</p>';
                echo '<a href="index.php?quanly=phim" style="color: #ccc; text-decoration: none;">← Xem tất cả phim</a>';
            }
            ?>
        </div>
        
        <div class="movies-grid">
            <?php
            // Xây dựng câu query dựa trên filter
            $sql = "SELECT * FROM movies WHERE status = 'showing'";
            if ($selected_genre) {
                $sql .= " AND genre LIKE '%" . mysqli_real_escape_string($conn, $selected_genre) . "%'";
            }
            $sql .= " ORDER BY created_at DESC";
            
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($movie = mysqli_fetch_assoc($result)) {
                    echo '<div class="movie-card">';
                    echo '<div class="movie-poster">';
                    echo '<img src="' . $movie['poster_url'] . '" alt="' . htmlspecialchars($movie['title']) . '">';
                    echo '<div class="movie-overlay">';
                    echo '<button class="btn-detail" onclick="showMovieDetail(' . $movie['id'] . ')">Chi tiết</button>';
                    echo '<button class="btn-book" onclick="bookMovie(' . $movie['id'] . ')">Đặt vé</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="movie-info">';
                    echo '<h3>' . htmlspecialchars($movie['title']) . '</h3>';
                    echo '<p class="genre">' . htmlspecialchars($movie['genre']) . '</p>';
                    echo '<p class="duration">' . $movie['duration'] . ' phút</p>';
                    echo '<p class="rating">⭐ ' . $movie['rating'] . '/10</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="color: #fff; text-align: center;">Hiện tại không có phim nào đang chiếu.</p>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Modal chi tiết phim -->
<div id="movieModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="movieDetails"></div>
    </div>
</div>
