<?php
// Lấy tham số thời gian
$date_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01'); // Đầu tháng
$date_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d'); // Hôm nay

// Báo cáo doanh thu
$revenue_sql = "SELECT 
                    DATE(b.created_at) as date,
                    COUNT(b.id) as total_bookings,
                    SUM(b.total_amount) as total_revenue
                FROM bookings b 
                WHERE b.payment_status = 'paid' 
                    AND DATE(b.created_at) BETWEEN ? AND ?
                GROUP BY DATE(b.created_at)
                ORDER BY date DESC";
$revenue_stmt = $conn->prepare($revenue_sql);
$revenue_stmt->bind_param("ss", $date_from, $date_to);
$revenue_stmt->execute();
$revenue_result = $revenue_stmt->get_result();

// Tổng doanh thu trong khoảng thời gian
$total_revenue_sql = "SELECT 
                        COUNT(b.id) as total_bookings,
                        SUM(b.total_amount) as total_revenue,
                        COUNT(DISTINCT b.user_id) as unique_customers
                      FROM bookings b 
                      WHERE b.payment_status = 'paid' 
                        AND DATE(b.created_at) BETWEEN ? AND ?";
$total_revenue_stmt = $conn->prepare($total_revenue_sql);
$total_revenue_stmt->bind_param("ss", $date_from, $date_to);
$total_revenue_stmt->execute();
$total_revenue_result = $total_revenue_stmt->get_result();
$total_stats = $total_revenue_result->fetch_assoc();

// Top phim bán chạy
$top_movies_sql = "SELECT 
                    m.title, m.poster_url,
                    COUNT(b.id) as total_bookings,
                    SUM(b.total_amount) as revenue
                   FROM bookings b
                   INNER JOIN showtimes st ON b.showtime_id = st.id
                   INNER JOIN movies m ON st.movie_id = m.id
                   WHERE b.payment_status = 'paid' 
                     AND DATE(b.created_at) BETWEEN ? AND ?
                   GROUP BY m.id, m.title, m.poster_url
                   ORDER BY total_bookings DESC
                   LIMIT 10";
$top_movies_stmt = $conn->prepare($top_movies_sql);
$top_movies_stmt->bind_param("ss", $date_from, $date_to);
$top_movies_stmt->execute();
$top_movies_result = $top_movies_stmt->get_result();

// Top khách hàng VIP
$top_customers_sql = "SELECT 
                        u.name, u.email,
                        COUNT(b.id) as total_bookings,
                        SUM(b.total_amount) as total_spent
                      FROM bookings b
                      INNER JOIN users u ON b.user_id = u.id
                      WHERE b.payment_status = 'paid' 
                        AND DATE(b.created_at) BETWEEN ? AND ?
                      GROUP BY u.id, u.name, u.email
                      ORDER BY total_spent DESC
                      LIMIT 10";
$top_customers_stmt = $conn->prepare($top_customers_sql);
$top_customers_stmt->bind_param("ss", $date_from, $date_to);
$top_customers_stmt->execute();
$top_customers_result = $top_customers_stmt->get_result();

// Thống kê theo rạp
$theater_stats_sql = "SELECT 
                        t.name as theater_name,
                        COUNT(b.id) as total_bookings,
                        SUM(b.total_amount) as revenue
                      FROM bookings b
                      INNER JOIN showtimes st ON b.showtime_id = st.id
                      INNER JOIN screens s ON st.screen_id = s.id
                      INNER JOIN theaters t ON s.theater_id = t.id
                      WHERE b.payment_status = 'paid' 
                        AND DATE(b.created_at) BETWEEN ? AND ?
                      GROUP BY t.id, t.name
                      ORDER BY revenue DESC";
$theater_stats_stmt = $conn->prepare($theater_stats_sql);
$theater_stats_stmt->bind_param("ss", $date_from, $date_to);
$theater_stats_stmt->execute();
$theater_stats_result = $theater_stats_stmt->get_result();
?>

<div class="content-header">
    <h1 class="content-title">📈 Admin - Báo cáo & Thống kê</h1>
    <div class="breadcrumb">Admin / Báo cáo / Tổng quan</div>
</div>

<!-- Bộ lọc thời gian -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <input type="hidden" name="page" value="reports">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">📅 Từ ngày:</label>
                <input type="date" name="from" value="<?php echo $date_from; ?>" class="form-control">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">📅 Đến ngày:</label>
                <input type="date" name="to" value="<?php echo $date_to; ?>" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">📊 Xem báo cáo</button>
            
            <div style="margin-left: auto; color: #666; padding: 10px 0;">
                <strong>Khoảng thời gian:</strong> <?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>
            </div>
        </form>
    </div>
</div>

<!-- Tổng quan -->
<div class="stats-grid" style="margin-bottom: 30px; grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-number">💰 <?php echo number_format($total_stats['total_revenue'] ?? 0, 0, ',', '.'); ?> VNĐ</div>
        <div class="stat-label">Tổng doanh thu</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number">🎫 <?php echo $total_stats['total_bookings'] ?? 0; ?></div>
        <div class="stat-label">Vé đã bán</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number">👥 <?php echo $total_stats['unique_customers'] ?? 0; ?></div>
        <div class="stat-label">Khách hàng</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number">
            💵 <?php 
            $avg_ticket = ($total_stats['total_bookings'] > 0) ? 
                         ($total_stats['total_revenue'] / $total_stats['total_bookings']) : 0;
            echo number_format($avg_ticket, 0, ',', '.'); 
            ?> VNĐ
        </div>
        <div class="stat-label">Giá vé trung bình</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Top phim bán chạy -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏆 Top phim bán chạy</h3>
        </div>
        <div class="card-body">
            <?php if ($top_movies_result->num_rows > 0): ?>
                <div style="space-y: 15px;">
                    <?php $rank = 1; while($movie = $top_movies_result->fetch_assoc()): ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #eee; border-radius: 10px; transition: all 0.3s ease;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                            <div style="font-size: 20px; font-weight: bold; color: #e50914; width: 30px; text-align: center;">
                                <?php 
                                if ($rank == 1) echo '🥇';
                                else if ($rank == 2) echo '🥈';
                                else if ($rank == 3) echo '🥉';
                                else echo '#' . $rank;
                                ?>
                            </div>
                            <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                 style="width: 50px; height: 75px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 5px 0; color: #333; font-size: 16px;">🎬 <?php echo htmlspecialchars($movie['title']); ?></h4>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    🎫 <?php echo $movie['total_bookings']; ?> vé • 
                                    💰 <?php echo number_format($movie['revenue'], 0, ',', '.'); ?> VNĐ
                                </p>
                            </div>
                        </div>
                    <?php $rank++; endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🎬</div>
                    <p>Chưa có dữ liệu trong khoảng thời gian này</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top khách hàng VIP -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💎 Top khách hàng VIP</h3>
        </div>
        <div class="card-body">
            <?php if ($top_customers_result->num_rows > 0): ?>
                <div style="space-y: 15px;">
                    <?php $rank = 1; while($customer = $top_customers_result->fetch_assoc()): ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #eee; border-radius: 10px; transition: all 0.3s ease;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                            <div style="font-size: 20px; font-weight: bold; color: #e50914; width: 30px; text-align: center;">
                                <?php 
                                if ($rank == 1) echo '🥇';
                                else if ($rank == 2) echo '🥈';
                                else if ($rank == 3) echo '🥉';
                                else echo '#' . $rank;
                                ?>
                            </div>
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e50914, #ff6b6b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px;">
                                <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 5px 0; color: #333; font-size: 16px;">👤 <?php echo htmlspecialchars($customer['name']); ?></h4>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    🎫 <?php echo $customer['total_bookings']; ?> vé • 
                                    💰 <?php echo number_format($customer['total_spent'], 0, ',', '.'); ?> VNĐ
                                </p>
                            </div>
                        </div>
                    <?php $rank++; endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 15px;">👥</div>
                    <p>Chưa có dữ liệu trong khoảng thời gian này</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Doanh thu theo ngày -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3 class="card-title">📅 Doanh thu theo ngày</h3>
    </div>
    <div class="card-body">
        <?php if ($revenue_result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>📅 Ngày</th>
                            <th>🎫 Số vé bán</th>
                            <th>💰 Doanh thu</th>
                            <th>📊 Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_revenue = 0;
                        $revenues = [];
                        while($row = $revenue_result->fetch_assoc()) {
                            $revenues[] = $row;
                            if ($row['total_revenue'] > $max_revenue) {
                                $max_revenue = $row['total_revenue'];
                            }
                        }
                        
                        foreach($revenues as $revenue): 
                            $percentage = $max_revenue > 0 ? ($revenue['total_revenue'] / $max_revenue) * 100 : 0;
                        ?>
                            <tr>
                                <td><strong>📅 <?php echo date('d/m/Y', strtotime($revenue['date'])); ?></strong></td>
                                <td>🎫 <?php echo $revenue['total_bookings']; ?> vé</td>
                                <td><strong style="color: #e50914;">💰 <?php echo number_format($revenue['total_revenue'], 0, ',', '.'); ?> VNĐ</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="background: #f8f9fa; border-radius: 10px; height: 20px; width: 200px; position: relative; overflow: hidden;">
                                            <div style="background: linear-gradient(90deg, #e50914, #ff6b6b); height: 100%; width: <?php echo $percentage; ?>%; border-radius: 10px; transition: width 0.3s ease;"></div>
                                        </div>
                                        <span style="font-size: 12px; font-weight: bold; color: #333;">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
                <h3>Chưa có dữ liệu doanh thu</h3>
                <p>Không có giao dịch nào trong khoảng thời gian này.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Thống kê theo rạp -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🏢 Doanh thu theo rạp</h3>
    </div>
    <div class="card-body">
        <?php if ($theater_stats_result->num_rows > 0): ?>
            <div style="display: grid; gap: 20px;">
                <?php 
                $max_theater_revenue = 0;
                $theater_revenues = [];
                while($row = $theater_stats_result->fetch_assoc()) {
                    $theater_revenues[] = $row;
                    if ($row['revenue'] > $max_theater_revenue) {
                        $max_theater_revenue = $row['revenue'];
                    }
                }
                
                foreach($theater_revenues as $theater): 
                    $percentage = $max_theater_revenue > 0 ? ($theater['revenue'] / $max_theater_revenue) * 100 : 0;
                ?>
                    <div style="display: flex; align-items: center; gap: 20px; padding: 20px; border: 1px solid #eee; border-radius: 12px; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">🏢 <?php echo htmlspecialchars($theater['theater_name']); ?></h4>
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                🎫 <?php echo $theater['total_bookings']; ?> vé • 
                                <strong style="color: #e50914;">💰 <?php echo number_format($theater['revenue'], 0, ',', '.'); ?> VNĐ</strong>
                            </p>
                        </div>
                        <div style="width: 300px;">
                            <div style="background: #f8f9fa; border-radius: 15px; height: 30px; position: relative; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #e50914, #ff6b6b); height: 100%; width: <?php echo $percentage; ?>%; border-radius: 15px; transition: width 0.3s ease;"></div>
                                <span style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); font-size: 12px; font-weight: bold; color: #333;">
                                    <?php echo number_format($percentage, 1); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 15px;">🏢</div>
                <h3>Chưa có dữ liệu theo rạp</h3>
                <p>Không có giao dịch nào trong khoảng thời gian này.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Buttons for export -->
<div style="text-align: center; margin-top: 30px;">
    <button onclick="exportReport()" class="btn btn-secondary" style="margin-right: 10px;">📊 Xuất báo cáo Excel</button>
    <button onclick="printReport()" class="btn btn-secondary">🖨️ In báo cáo</button>
</div>

<script>
function exportReport() {
    // Xuất báo cáo
    window.open('?page=reports&export=excel&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>', '_blank');
}

function printReport() {
    // In báo cáo
    window.print();
}

// Animate numbers on load
document.addEventListener('DOMContentLoaded', function() {
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(number => {
        const text = number.textContent;
        number.style.opacity = '0';
        number.style.transform = 'scale(0.5)';
        
        setTimeout(() => {
            number.style.transition = 'all 0.6s ease';
            number.style.opacity = '1';
            number.style.transform = 'scale(1)';
        }, Math.random() * 500);
    });
});
</script> 