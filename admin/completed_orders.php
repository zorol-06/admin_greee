<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Lấy thống kê
$count_completed = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered' AND payment_status = 'complete'");
$count_completed->execute();
$total_completed = $count_completed->fetch(PDO::FETCH_ASSOC)['total'];

// Tính tổng doanh thu từ đơn hàng hoàn tất
$total_revenue = $conn->prepare("SELECT SUM(price) as revenue FROM orders WHERE status = 'delivered' AND payment_status = 'complete'");
$total_revenue->execute();
$revenue = $total_revenue->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Đơn hàng hoàn tất</title>
    <style>
        .stats-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .revenue-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #17a2b8;
        }
        .status-completed {
            color: #28a745;
            font-weight: bold;
            background: #d4edda;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
        }
        .box {
            border-left: 4px solid #28a745 !important;
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">
        <h1>Đơn hàng hoàn tất</h1>
    </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Đơn hàng hoàn tất</span>
    </div>

    <!-- Thống kê -->
   <!-- <div class="stats-container">
        <h3>📊 Thống kê đơn hàng hoàn tất</h3>
        <p>Tổng số đơn hàng hoàn tất: <span class="stats-number"><?= $total_completed ?></span></p>
        <p>Tổng doanh thu: <span class="revenue-number">$<?= number_format($revenue,) ?></span></p>
        <p><small>Đây là danh sách tất cả đơn hàng đã được giao và thanh toán hoàn tất</small></p>
    </div> --->

    <section class="order-container">
        <h1 class="heading">Danh sách đơn hàng hoàn tất</h1>
        <div class="box-container">
            <?php
            $select_orders = $conn->prepare("SELECT * FROM orders WHERE status = 'delivered' AND payment_status = 'complete' ORDER BY date DESC");
            $select_orders->execute();

            if ($select_orders->rowCount() > 0) {
                while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                    $order_id = (int) $fetch_orders['id'];
                    $name = htmlspecialchars($fetch_orders['name'] ?? '');
                    $date = htmlspecialchars($fetch_orders['date'] ?? '');
                    $number = htmlspecialchars($fetch_orders['number'] ?? '');
                    $email = htmlspecialchars($fetch_orders['email'] ?? '');
                    $price = htmlspecialchars($fetch_orders['price'] ?? '');
                    $method = htmlspecialchars($fetch_orders['method'] ?? '');
                    $address = htmlspecialchars($fetch_orders['address'] ?? '');
                    $coupon_code = htmlspecialchars($fetch_orders['coupon_code'] ?? '');
                    $discount_amount = htmlspecialchars($fetch_orders['discount_amount'] ?? '0');
                    $final_price = htmlspecialchars($fetch_orders['final_price'] ?? $price);
                    ?>

                    <div class="box">
                        <div class="status-completed">
                            ✅ HOÀN TẤT - Mã đơn: #<?= $order_id ?>
                        </div>

                        <div class="detail">
                            <p><strong>👤 Khách hàng:</strong> <span><?= $name ?></span></p>
                            <p><strong>📅 Ngày đặt:</strong> <span><?= $date ?></span></p>
                            <p><strong>📞 Điện thoại:</strong> <span><?= $number ?></span></p>
                            <p><strong>📧 Email:</strong> <span><?= $email ?></span></p>
                            <p><strong>💰 Giá gốc:</strong> <span><?= number_format($price, ) ?> VND</span></p>
                            
                            <?php if (!empty($coupon_code) && $discount_amount > 0): ?>
                                <p><strong>🎫 Mã giảm giá:</strong> <span><?= $coupon_code ?> (-<?= number_format($discount_amount, ) ?>) VND</span></p>
                            <?php endif; ?>
                            
                            <p><strong>💵 Thành tiền:</strong> <span style="color: #28a745; font-weight: bold;"><?= number_format($final_price, ) ?> VND</span></p>
                            <p><strong>💳 Phương thức:</strong> <span><?= $method ?></span></p>
                            <p><strong>🏠 Địa chỉ:</strong> <span><?= $address ?></span></p>
                            <p><strong>📊 Trạng thái:</strong> 
                                <span style="color: #28a745;">✅ Đã giao hàng & Thanh toán hoàn tất</span>
                            </p>
                        </div>

                        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <p style="margin: 0; font-size: 14px; color: #666;">
                                <strong>📝 Ghi chú:</strong> Đơn hàng đã được xử lý hoàn tất
                            </p>
                        </div>
                    </div>

                <?php }
            } else {
                echo '<div class="empty">
                        <p>📭 Chưa có đơn hàng hoàn tất nào!</p>
                        <p><small>Các đơn hàng sẽ xuất hiện ở đây sau khi được giao và thanh toán thành công</small></p>
                      </div>';
            }
            ?>
        </div>
    </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script type="text/javascript" src="script.js"></script>
<?php include '../components/alert.php'; ?>

</body>
</html>