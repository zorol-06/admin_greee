<?php
include '../components/connection.php';
session_start();

// 🔹 THÊM: Include file mailer để gửi email
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
    // 🔹 XỬ LÝ HỦY ĐƠN HÀNG
    if (isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
        $order_id = (int) $_POST['order_id'];

        $verify_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $verify_order->execute([$order_id]);
        $order_info = $verify_order->fetch(PDO::FETCH_ASSOC);

        if ($verify_order->rowCount() > 0) {
            // Cập nhật trạng thái thành 'cancelled'
            $cancel_order = $conn->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'cancelled' WHERE id = ?");
            $cancel_order->execute([$order_id]);
            
            // 🔹 GỬI EMAIL THÔNG BÁO HỦY ĐƠN HÀNG
            $customer_email = $order_info['email'];
            $customer_name = $order_info['name'];
            $order_total = $order_info['price'];
            
            $emailSubject = 'Thông báo hủy đơn hàng #' . $order_id;
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
                            background: linear-gradient(135deg, #d32f2f, #f44336);
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
                            border-left: 4px solid #f44336;
                            margin: 20px 0;
                        }
                        .cancelled-badge {
                            background: #f44336;
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
                        <div class='cancelled-badge'>
                            <strong>❌ ĐƠN HÀNG ĐÃ BỊ HỦY</strong>
                        </div>
                        
                        <p>Xin chào <strong>{$customer_name}</strong>,</p>
                        
                        <p>Chúng tôi rất tiếc phải thông báo đơn hàng <strong>#{$order_id}</strong> của bạn đã bị hủy.</p>
                        
                        <div class='order-info'>
                            <h3>📦 Thông tin đơn hàng đã hủy:</h3>
                            <p><strong>Mã đơn hàng:</strong> #{$order_id}</p>
                            <p><strong>Tổng giá trị:</strong> \${$order_total}</p>
                            <p><strong>Trạng thái:</strong> <span style='color: #f44336;'>❌ Đã hủy</span></p>
                            <p><strong>Thời gian hủy:</strong> " . date('d/m/Y H:i:s') . "</p>
                        </div>

                        <p><strong>Lý do hủy đơn hàng:</strong></p>
                        <ul>
                            <li>🔹 Hết hàng hoặc sản phẩm không khả dụng</li>
                            <li>🔹 Thông tin đơn hàng không hợp lệ</li>
                            <li>🔹 Vấn đề về thanh toán</li>
                            <li>🔹 Theo yêu cầu của khách hàng</li>
                        </ul>

                        <p>Nếu bạn có bất kỳ thắc mắc nào về việc hủy đơn hàng, vui lòng liên hệ với chúng tôi:</p>
                        <ul>
                            <li>📞 Hotline: <strong>1900 1234</strong></li>
                            <li>📧 Email: <strong>support@greencoffee.com</strong></li>
                        </ul>

                        <p>Chúng tôi chân thành xin lỗi vì sự bất tiện này và hy vọng sẽ được phục vụ bạn trong những đơn hàng tiếp theo.</p>
                        
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

            // Gửi email thông báo hủy đơn
            $emailSent = sendMail($customer_email, $emailSubject, $emailContent);
            
            if ($emailSent) {
                $success_msg[] = 'Đơn hàng #' . $order_id . ' đã được hủy và email thông báo đã gửi cho khách hàng';
            } else {
                $success_msg[] = 'Đơn hàng #' . $order_id . ' đã được hủy (có lỗi khi gửi email thông báo)';
            }
        } else {
            $warning_msg[] = 'Đơn hàng không tồn tại';
        }
    }

    // 🔹 XỬ LÝ XÓA ĐƠN HÀNG
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $order_id = (int) $_POST['order_id'];

        $verify_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $verify_order->execute([$order_id]);
        $order_info = $verify_order->fetch(PDO::FETCH_ASSOC);

        if ($verify_order->rowCount() > 0) {
            // 🔹 GỬI EMAIL THÔNG BÁO XÓA ĐƠN HÀNG TRƯỚC KHI XÓA
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
                            <strong>🗑️ ĐƠN HÀNG ĐÃ BỊ XÓA</strong>
                        </div>
                        
                        <p>Xin chào <strong>{$customer_name}</strong>,</p>
                        
                        <p>Chúng tôi xin thông báo đơn hàng <strong>#{$order_id}</strong> của bạn đã bị xóa khỏi hệ thống.</p>
                        
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
                            <li>📞 Hotline: <strong>1900 1234</strong></li>
                            <li>📧 Email: <strong>support@greencoffee.com</strong></li>
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
            $warning_msg[] = 'Đơn hàng đã bị xóa trước đó hoặc không tồn tại';
        }
    }

    // Xử lý CẬP NHẬT trạng thái đơn hàng (giữ nguyên)
    if (isset($_POST['update_order']) && isset($_POST['order_id']) && isset($_POST['update_payment'])) {
        $order_id = (int) $_POST['order_id'];
        $update_payment = trim($_POST['update_payment']);

        if ($update_payment === 'complete') {
            // Code xử lý cập nhật trạng thái hoàn tất (giữ nguyên)
            $update_order = $conn->prepare("UPDATE orders SET payment_status = 'complete', status = 'delivered' WHERE id = ?");
            $update_order->execute([$order_id]);
            
            // Gửi email thông báo (giữ nguyên code hiện tại của bạn)
            $select_order_info = $conn->prepare("SELECT * FROM orders WHERE id = ?");
            $select_order_info->execute([$order_id]);
            $order_info = $select_order_info->fetch(PDO::FETCH_ASSOC);
            
            if ($order_info) {
                $customer_email = $order_info['email'];
                $customer_name = $order_info['name'];
                $order_total = $order_info['price'];
                
                $emailSubject = 'Đơn hàng của bạn đã được giao thành công!';
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
                                background: linear-gradient(135deg, #2e7d32, #4caf50);
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
                                border-left: 4px solid #4caf50;
                                margin: 20px 0;
                            }
                            .success-badge {
                                background: #4caf50;
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
                            <div class='success-badge'>
                                <strong>🎉 ĐƠN HÀNG ĐÃ GIAO THÀNH CÔNG!</strong>
                            </div>
                            
                            <p>Xin chào <strong>{$customer_name}</strong>,</p>
                            
                            <p>Chúng tôi xin thông báo đơn hàng <strong>#{$order_id}</strong> của bạn đã được giao thành công!</p>
                            
                            <div class='order-info'>
                                <h3>📦 Thông tin đơn hàng:</h3>
                                <p><strong>Mã đơn hàng:</strong> #{$order_id}</p>
                                <p><strong>Tổng giá trị:</strong> \${$order_total}</p>
                                <p><strong>Trạng thái:</strong> <span style='color: #4caf50;'>✅ Đã giao hàng & Thanh toán hoàn tất</span></p>
                                <p><strong>Ngày cập nhật:</strong> " . date('d/m/Y H:i:s') . "</p>
                            </div>

                            <p>Cảm ơn bạn đã tin tưởng và mua sắm tại <strong>Green Coffee</strong>!</p>
                            
                            <p>Nếu bạn có bất kỳ câu hỏi nào về đơn hàng, đừng ngần ngại liên hệ với chúng tôi:</p>
                            <ul>
                                <li>📞 Hotline: <strong>1900 1234</strong></li>
                                <li>📧 Email: <strong>support@greencoffee.com</strong></li>
                            </ul>

                            <p>Chúng tôi hy vọng bạn hài lòng với sản phẩm và dịch vụ của chúng tôi!</p>
                            
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

                // Gửi email thông báo
                $emailSent = sendMail($customer_email, $emailSubject, $emailContent);
                
                if ($emailSent) {
                    $success_msg[] = 'Đơn hàng đã được đánh dấu là hoàn tất và email thông báo đã gửi cho khách hàng';
                } else {
                    $success_msg[] = 'Đơn hàng đã được đánh dấu là hoàn tất (có lỗi khi gửi email thông báo)';
                }
            }
            
        } else if ($update_payment === 'pending') {
            $update_order = $conn->prepare("UPDATE orders SET payment_status = 'pending', status = 'pending' WHERE id = ?");
            $update_order->execute([$order_id]);
            $success_msg[] = 'Đơn hàng đã được đặt lại trạng thái chờ xử lý';
        } else {
            $warning_msg[] = 'Giá trị trạng thái không hợp lệ';
        }
    }
} catch (PDOException $e) {
    $error_msg[] = 'Có lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Đơn hàng đã đặt</title>
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
        .btn-cancel {
            background: #ff9800;
        }
        .btn-delete {
            background: #dc3545;
        }
        .btn-update {
            background: #28a745;
        }
        .status-cancelled {
            color: #ff9800;
            font-weight: bold;
        }
        .status-delivered {
            color: #28a745;
            font-weight: bold;
        }
        .status-pending {
            color: #17a2b8;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">
        <h1>Đơn hàng đã đặt</h1>
    </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Đơn hàng đã đặt</span>
    </div>

    <section class="order-container">
        <h1 class="heading">Tất cả đơn hàng đã đặt</h1>
        <div class="box-container">
            <?php
            $select_orders = $conn->prepare("SELECT * FROM orders ORDER BY date DESC");
            $select_orders->execute();

            if ($select_orders->rowCount() > 0) {
                while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                    $order_id = (int) $fetch_orders['id'];
                    $status_text = htmlspecialchars($fetch_orders['status'] ?? '');
                    $name = htmlspecialchars($fetch_orders['name'] ?? '');
                    $date = htmlspecialchars($fetch_orders['date'] ?? '');
                    $number = htmlspecialchars($fetch_orders['number'] ?? '');
                    $email = htmlspecialchars($fetch_orders['email'] ?? '');
                    $price = htmlspecialchars($fetch_orders['price'] ?? '');
                    $method = htmlspecialchars($fetch_orders['method'] ?? '');
                    $address = htmlspecialchars($fetch_orders['address'] ?? '');
                    $payment_status = htmlspecialchars($fetch_orders['payment_status'] ?? 'pending');
                    
                    // Xác định màu sắc và văn bản hiển thị cho trạng thái
                    $status_class = 'status-pending';
                    $status_display = 'Đang xử lý';
                    
                    if ($status_text === 'delivered') {
                        $status_class = 'status-delivered';
                        $status_display = 'Đã giao hàng';
                    } elseif ($status_text === 'cancelled') {
                        $status_class = 'status-cancelled';
                        $status_display = 'Đã hủy';
                    }
                    
                    // SỬA LỖI: Đặt trong ngoặc các toán tử ternary lồng nhau
                    $payment_display = ($payment_status === 'complete') ? 'Đã thanh toán' : 
                                      (($payment_status === 'cancelled') ? 'Đã hủy' : 'Chưa thanh toán');
                    $payment_color = ($payment_status === 'complete') ? 'green' : 
                                    (($payment_status === 'cancelled') ? 'orange' : 'red');
                    ?>

                    <div class="box">
                        <div class="status <?php echo $status_class; ?>">
                            <?php echo $status_display; ?>
                        </div>

                        <div class="detail">
                            <p>Tên người dùng : <span><?php echo $name; ?></span></p>
                            <p>Mã đơn hàng : <span><?php echo $order_id; ?></span></p>
                            <p>Ngày đặt hàng : <span><?php echo $date; ?></span></p>
                            <p>Số điện thoại : <span><?php echo $number; ?></span></p>
                            <p>Email người dùng : <span><?php echo $email; ?></span></p>
                            <p>Tổng tiền : <span>$<?php echo $price; ?></span></p>
                            <p>Phương thức thanh toán : <span><?php echo $method; ?></span></p>
                            <p>Địa chỉ : <span><?php echo $address; ?></span></p>
                            <p>Trạng thái thanh toán : <span style="color:<?php echo $payment_color; ?>">
                                <?php echo $payment_display; ?>
                            </span></p>
                        </div>

                        <form action="" method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

                            <label for="update_payment_<?php echo $order_id; ?>">Cập nhật trạng thái đơn hàng:</label>
                            <select name="update_payment" id="update_payment_<?php echo $order_id; ?>">
                                <option value="pending" <?php echo ($payment_status === 'pending' || $payment_status === 'unpaid') ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="complete" <?php echo ($payment_status === 'complete') ? 'selected' : ''; ?>>Hoàn tất</option>
                            </select>

                            <div class="flex-btn">
                                <button type="submit" name="update_order" class="btn btn-update">Cập nhật đơn hàng</button>
                                
                                <?php if ($status_text !== 'cancelled'): ?>
                                    <button type="submit" name="cancel_order" class="btn btn-cancel" 
                                            onclick="return confirm('Bạn có chắc muốn HỦY đơn hàng #<?php echo $order_id; ?>?\\n\\nKhách hàng sẽ nhận được email thông báo.')">
                                        Hủy đơn hàng
                                    </button>
                                <?php endif; ?>
                                
                                <button type="submit" name="delete_order" class="btn btn-delete" 
                                        onclick="return confirm('⚠️ CẢNH BÁO: Bạn có chắc muốn XÓA VĨNH VIỄN đơn hàng #<?php echo $order_id; ?>?\\n\\nHành động này KHÔNG THỂ hoàn tác!\\nKhách hàng sẽ nhận được email thông báo.')">
                                    Xóa đơn hàng
                                </button>
                            </div>
                        </form>
                    </div>

                <?php }
            } else {
                echo '<div class="empty"><p>Chưa có đơn hàng nào được đặt!</p></div>';
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