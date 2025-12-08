<?php
include '../components/connection.php';
session_start();

// Include file mailer để gửi email
include '../functions.php';

// Thông báo
$success_msg = [];
$warning_msg = [];
$error_msg = [];

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

try {
    // 🔹 XỬ LÝ KHÔI PHỤC ĐƠN HÀNG
    if (isset($_POST['restore_order']) && isset($_POST['order_id'])) {
        $order_id = (int) $_POST['order_id'];

        $verify_order = $conn->prepare("SELECT * FROM orders WHERE id = ? AND status = 'cancelled'");
        $verify_order->execute([$order_id]);
        $order_info = $verify_order->fetch(PDO::FETCH_ASSOC);

        if ($verify_order->rowCount() > 0) {
            // Khôi phục đơn hàng về trạng thái pending
            $restore_order = $conn->prepare("UPDATE orders SET status = 'pending', payment_status = 'pending' WHERE id = ?");
            $restore_order->execute([$order_id]);
            
            // 🔹 GỬI EMAIL THÔNG BÁO KHÔI PHỤC ĐƠN HÀNG
            $customer_email = $order_info['email'];
            $customer_name = $order_info['name'];
            $order_total = $order_info['price'];
            
            $emailSubject = 'Đơn hàng #' . $order_id . ' đã được khôi phục';
            $emailContent = "
                <html>
                <head>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            line-height: 1.6;
                            color: #333;
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                        }
                        .header { 
                            background: linear-gradient(135deg, #1976d2, #2196f3);
                            color: white; 
                            padding: 30px 20px; 
                            text-align: center; 
                            border-radius: 10px 10px 0 0;
                        }
                        .content { 
                            padding: 30px 20px; 
                            background: #f9f9f9; 
                            border-left: 1px solid #ddd;
                            border-right: 1px solid #ddd;
                        }
                        .order-info {
                            background: white;
                            padding: 20px;
                            border-radius: 8px;
                            border-left: 4px solid #2196f3;
                            margin: 20px 0;
                        }
                        .restore-badge {
                            background: #2196f3;
                            color: white;
                            padding: 10px 20px;
                            border-radius: 20px;
                            display: inline-block;
                            margin: 10px 0;
                        }
                        .footer { 
                            text-align: center; 
                            padding: 20px; 
                            font-size: 12px; 
                            color: #666;
                            background: #f1f1f1;
                            border-radius: 0 0 10px 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class='header'>
                        <h1>☕ Green Coffee</h1>
                        <p>Thế giới cà phê nguyên chất</p>
                    </div>
                    <div class='content'>
                        <div class='restore-badge'>
                            <strong>🔄 ĐƠN HÀNG ĐÃ ĐƯỢC KHÔI PHỤC</strong>
                        </div>
                        
                        <p>Xin chào <strong>{$customer_name}</strong>,</p>
                        
                        <p>Tin vui! Đơn hàng <strong>#{$order_id}</strong> của bạn đã được khôi phục và đang trong quá trình xử lý.</p>
                        
                        <div class='order-info'>
                            <h3>📦 Thông tin đơn hàng:</h3>
                            <p><strong>Mã đơn hàng:</strong> #{$order_id}</p>
                            <p><strong>Tổng giá trị:</strong> \${$order_total}</p>
                            <p><strong>Trạng thái:</strong> <span style='color: #2196f3;'>🔄 Đã khôi phục & Đang xử lý</span></p>
                            <p><strong>Thời gian khôi phục:</strong> " . date('d/m/Y H:i:s') . "</p>
                        </div>

                        <p><strong>Tiếp theo sẽ diễn ra:</strong></p>
                        <ul>
                            <li>✅ Đơn hàng của bạn sẽ được xử lý trong 24h</li>
                            <li>✅ Bạn sẽ nhận được email thông báo khi đơn hàng được giao</li>
                            <li>✅ Nhân viên có thể liên hệ với bạn để xác nhận thông tin</li>
                        </ul>

                        <p>Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi:</p>
                        <ul>
                            <li>📞 Hotline: <strong>0336965264</strong></li>
                            <li>📧 Email: <strong>hoaiphm.24itb@vku.udn.vn</strong></li>
                        </ul>

                        <p>Cảm ơn bạn đã tin tưởng <strong>Green Coffee</strong>!</p>
                        
                        <p>Trân trọng,<br>
                        <strong>Đội ngũ Green Coffee</strong></p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " <strong>Green Coffee</strong>. All rights reserved.</p>
                        <p>Đây là email tự động, vui lòng không trả lời.</p>
                    </div>
                </body>
                </html>
            ";

            // Gửi email thông báo khôi phục
            $emailSent = sendMail($customer_email, $emailSubject, $emailContent);
            
            if ($emailSent) {
                $success_msg[] = 'Đơn hàng #' . $order_id . ' đã được khôi phục và email thông báo đã gửi cho khách hàng';
            } else {
                $success_msg[] = 'Đơn hàng #' . $order_id . ' đã được khôi phục (có lỗi khi gửi email thông báo)';
            }
        } else {
            $warning_msg[] = 'Đơn hàng không tồn tại hoặc không ở trạng thái hủy';
        }
    }

    // 🔹 XỬ LÝ XÓA VĨNH VIỄN ĐƠN HÀNG ĐÃ HỦY
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $order_id = (int) $_POST['order_id'];

        $verify_order = $conn->prepare("SELECT * FROM orders WHERE id = ? AND status = 'cancelled'");
        $verify_order->execute([$order_id]);
        $order_info = $verify_order->fetch(PDO::FETCH_ASSOC);

        if ($verify_order->rowCount() > 0) {
            // 🔹 GỬI EMAIL THÔNG BÁO XÓA ĐƠN HÀNG
            $customer_email = $order_info['email'];
            $customer_name = $order_info['name'];
            $order_total = $order_info['price'];
            
            $emailSubject = 'Thông báo xóa đơn hàng #' . $order_id;
            $emailContent = "
                <html>
                <head>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            line-height: 1.6;
                            color: #333;
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                        }
                        .header { 
                            background: linear-gradient(135deg, #616161, #9e9e9e);
                            color: white; 
                            padding: 30px 20px; 
                            text-align: center; 
                            border-radius: 10px 10px 0 0;
                        }
                        .content { 
                            padding: 30px 20px; 
                            background: #f9f9f9; 
                            border-left: 1px solid #ddd;
                            border-right: 1px solid #ddd;
                        }
                        .order-info {
                            background: white;
                            padding: 20px;
                            border-radius: 8px;
                            border-left: 4px solid #616161;
                            margin: 20px 0;
                        }
                        .deleted-badge {
                            background: #616161;
                            color: white;
                            padding: 10px 20px;
                            border-radius: 20px;
                            display: inline-block;
                            margin: 10px 0;
                        }
                        .footer { 
                            text-align: center; 
                            padding: 20px; 
                            font-size: 12px; 
                            color: #666;
                            background: #f1f1f1;
                            border-radius: 0 0 10px 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class='header'>
                        <h1>☕ Green Coffee</h1>
                        <p>Thế giới cà phê nguyên chất</p>
                    </div>
                    <div class='content'>
                        <div class='deleted-badge'>
                            <strong>🗑️ ĐƠN HÀNG ĐÃ BỊ XÓA VĨNH VIỄN</strong>
                        </div>
                        
                        <p>Xin chào <strong>{$customer_name}</strong>,</p>
                        
                        <p>Chúng tôi xin thông báo đơn hàng <strong>#{$order_id}</strong> của bạn đã bị xóa vĩnh viễn khỏi hệ thống.</p>
                        
                        <div class='order-info'>
                            <h3>📦 Thông tin đơn hàng đã xóa:</h3>
                            <p><strong>Mã đơn hàng:</strong> #{$order_id}</p>
                            <p><strong>Tổng giá trị:</strong> \${$order_total}</p>
                            <p><strong>Trạng thái:</strong> <span style='color: #616161;'>🗑️ Đã xóa vĩnh viễn</span></p>
                            <p><strong>Thời gian xóa:</strong> " . date('d/m/Y H:i:s') . "</p>
                        </div>

                        <p><strong>Lưu ý quan trọng:</strong></p>
                        <ul>
                            <li>🔹 Đơn hàng đã được xóa vĩnh viễn khỏi hệ thống</li>
                            <li>🔹 Bạn sẽ không thể khôi phục đơn hàng này</li>
                            <li>🔹 Nếu đã thanh toán, tiền sẽ được hoàn lại trong vòng 3-5 ngày làm việc</li>
                        </ul>

                        <p>Nếu bạn có bất kỳ thắc mắc nào về việc xóa đơn hàng, vui lòng liên hệ ngay với chúng tôi:</p>
                        <ul>
                            <li>📞 Hotline: <strong>0336965264</strong></li>
                            <li>📧 Email: <strong>hoaiphm.24itb@vku.udn.vn</strong></li>
                        </ul>

                        <p>Chúng tôi chân thành xin lỗi vì sự bất tiện này.</p>
                        
                        <p>Trân trọng,<br>
                        <strong>Đội ngũ Green Coffee</strong></p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " <strong>Green Coffee</strong>. All rights reserved.</p>
                        <p>Đây là email tự động, vui lòng không trả lời.</p>
                    </div>
                </body>
                </html>
            ";

            // Gửi email thông báo xóa đơn
            $emailSent = sendMail($customer_email, $emailSubject, $emailContent);

            // Sau khi gửi email, thực hiện xóa đơn hàng
            $delete_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $delete_order->execute([$order_id]);
            
            if ($emailSent) {
                $success_msg[] = 'Đơn hàng #' . $order_id . ' đã được xóa và email thông báo đã gửi cho khách hàng';
            } else {
                $success_msg[] = 'Đơn hàng #' . $order_id . ' đã được xóa (có lỗi khi gửi email thông báo)';
            }
        } else {
            $warning_msg[] = 'Đơn hàng không tồn tại hoặc không ở trạng thái hủy';
        }
    }

} catch (PDOException $e) {
    $error_msg[] = 'Có lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
}

// Lấy thống kê
$count_cancelled = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled'");
$count_cancelled->execute();
$total_cancelled = $count_cancelled->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Đơn hàng đã hủy</title>
    <style>
        .flex-btn {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .flex-btn .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .flex-btn .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .btn-restore {
            background: #28a745;
        }
        .btn-delete {
            background: #dc3545;
        }
        .status-cancelled {
            color: #ff9800;
            font-weight: bold;
            background: #fff3cd;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
        }
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
            color: #ff9800;
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">
        <h1>Đơn hàng đã hủy</h1>
    </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Đơn hàng đã hủy</span>
    </div>

    <!-- Thống kê -->
    <div class="stats-container">
        <h3>📊 Thống kê đơn hàng hủy</h3>
        <p>Tổng số đơn hàng đã hủy: <span class="stats-number"><?= $total_cancelled ?></span></p>
        <p><small>Đây là danh sách tất cả đơn hàng đã bị hủy trong hệ thống</small></p>
    </div>

    <section class="order-container">
        <h1 class="heading">Danh sách đơn hàng đã hủy</h1>
        <div class="box-container">
            <?php
            $select_orders = $conn->prepare("SELECT * FROM orders WHERE status = 'cancelled' ORDER BY date DESC");
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
                    $payment_status = htmlspecialchars($fetch_orders['payment_status'] ?? 'cancelled');
                    ?>

                    <div class="box" style="border-left: 4px solid #ff9800;">
                        <div class="status-cancelled">
                            ❌ ĐÃ HỦY - Mã đơn: #<?= $order_id ?>
                        </div>

                        <div class="detail">
                            <p><strong>👤 Khách hàng:</strong> <span><?= $name ?></span></p>
                            <p><strong>📅 Ngày đặt:</strong> <span><?= $date ?></span></p>
                            <p><strong>📞 Điện thoại:</strong> <span><?= $number ?></span></p>
                            <p><strong>📧 Email:</strong> <span><?= $email ?></span></p>
                            <p><strong>💰 Tổng tiền:</strong> <span>$<?= number_format($price, 2) ?></span></p>
                            <p><strong>💳 Phương thức:</strong> <span><?= $method ?></span></p>
                            <p><strong>🏠 Địa chỉ:</strong> <span><?= $address ?></span></p>
                            <p><strong>📊 Trạng thái thanh toán:</strong> 
                                <span style="color: #ff9800;">Đã hủy</span>
                            </p>
                        </div>

                        <form action="" method="post">
                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                            
                            <div class="flex-btn">
                                <button type="submit" name="restore_order" class="btn btn-restore" 
                                        onclick="return confirm('Bạn có chắc muốn KHÔI PHỤC đơn hàng #<?= $order_id ?>?\\n\\nĐơn hàng sẽ chuyển về trạng thái chờ xử lý.\\nKhách hàng sẽ nhận được email thông báo.')">
                                    🔄 Khôi phục đơn hàng
                                </button>
                                
                                <button type="submit" name="delete_order" class="btn btn-delete" 
                                        onclick="return confirm('⚠️ CẢNH BÁO: Bạn có chắc muốn XÓA VĨNH VIỄN đơn hàng #<?= $order_id ?>?\\n\\nHành động này KHÔNG THỂ hoàn tác!\\nKhách hàng sẽ nhận được email thông báo.')">
                                    🗑️ Xóa vĩnh viễn
                                </button>
                            </div>
                        </form>
                    </div>

                <?php }
            } else {
                echo '<div class="empty">
                        <p>🎉 Chưa có đơn hàng nào bị hủy!</p>
                        <p><small>Tất cả đơn hàng đều đang trong trạng thái hoạt động</small></p>
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