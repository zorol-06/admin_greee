<?php
include 'components/connection.php';

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:admin/login.php');
    exit;
}

// Debug: Kiểm tra dữ liệu trong database
echo "<h2>DEBUG: Kiểm Tra Dữ Liệu Đơn Hàng</h2>";

try {
    // Lấy tất cả đơn hàng
    $check = $conn->prepare("SELECT id, date, payment_status, price, qty FROM orders LIMIT 10");
    $check->execute();
    $orders = $check->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Tổng cộng 10 đơn hàng gần nhất:</strong></p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Date</th><th>Payment Status</th><th>Price</th><th>Qty</th></tr>";
    
    foreach($orders as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['date']) . "</td>";
        echo "<td>" . htmlspecialchars($order['payment_status']) . "</td>";
        echo "<td>" . htmlspecialchars($order['price']) . "</td>";
        echo "<td>" . htmlspecialchars($order['qty']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Thống kê payment_status
    echo "<p><strong>Phân loại theo payment_status:</strong></p>";
    $status_check = $conn->prepare("SELECT payment_status, COUNT(*) as count FROM orders GROUP BY payment_status");
    $status_check->execute();
    $statuses = $status_check->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach($statuses as $status) {
        echo "<li>" . htmlspecialchars($status['payment_status']) . ": " . $status['count'] . " đơn</li>";
    }
    echo "</ul>";
    
    // Kiểm tra format date
    echo "<p><strong>Kiểm tra format date của đơn hàng 'complete':</strong></p>";
    $date_check = $conn->prepare("
        SELECT id, date, 
               CAST(date AS CHAR) as date_str,
               DATE_FORMAT(date, '%Y-%m-%d') as formatted_date
        FROM orders 
        WHERE payment_status = 'complete'
        LIMIT 5
    ");
    $date_check->execute();
    $dates = $date_check->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($dates) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Raw Date</th><th>Cast Date</th><th>Formatted</th></tr>";
        foreach($dates as $d) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($d['id']) . "</td>";
            echo "<td>" . htmlspecialchars($d['date']) . "</td>";
            echo "<td>" . htmlspecialchars($d['date_str']) . "</td>";
            echo "<td>" . htmlspecialchars($d['formatted_date']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Không tìm thấy đơn hàng nào với payment_status = 'complete'!</strong></p>";
        echo "<p>Bạn cần thêm dữ liệu test hoặc cập nhật payment_status của đơn hàng hiện tại</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'><strong>Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
<hr>
<p><a href="admin/RevenueReport.php">Quay lại Báo Cáo Doanh Thu</a></p>
