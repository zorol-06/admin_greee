<?php
include '../components/connection.php';
session_start();

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
    // Xử lý XÓA đơn hàng
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $order_id = (int) $_POST['order_id'];

        $verify_delete = $conn->prepare("SELECT id FROM orders WHERE id = ?");
        $verify_delete->execute([$order_id]);

        if ($verify_delete->rowCount() > 0) {
            $delete_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $delete_order->execute([$order_id]);
            $success_msg[] = 'Đơn hàng đã được xóa thành công';
        } else {
            $warning_msg[] = 'Đơn hàng đã bị xóa trước đó hoặc không tồn tại';
        }
    }

    // Xử lý CẬP NHẬT trạng thái đơn hàng
    if (isset($_POST['update_order']) && isset($_POST['order_id']) && isset($_POST['update_payment'])) {
        $order_id = (int) $_POST['order_id'];
        $update_payment = trim($_POST['update_payment']);

        if ($update_payment === 'complete') {
            // Khi hoàn tất: cập nhật cả payment_status và status
            $update_order = $conn->prepare("UPDATE orders SET payment_status = 'complete', status = 'delivered' WHERE id = ?");
            $update_order->execute([$order_id]);
            $success_msg[] = 'Đơn hàng đã được đánh dấu là hoàn tất (đã thanh toán và đã giao hàng)';
        } else if ($update_payment === 'pending') {
            // Khi chờ xử lý: chỉ cập nhật payment_status, giữ status là pending
            $update_order = $conn->prepare("UPDATE orders SET payment_status = 'pending', status = 'pending' WHERE id = ?");
            $update_order->execute([$order_id]);
            $success_msg[] = 'Đơn hàng đã được đặt lại trạng thái chờ xử lý';
        } else {
            $warning_msg[] = 'Giá trị trạng thái không hợp lệ';
        }
    }
} catch (PDOException $e) {
    // Ghi log ở đây nếu cần
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
                    // Các trường đầu ra luôn escape
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
                    $status_color = ($status_text === 'delivered') ? 'green' : 'red';
                    $status_display = ($status_text === 'delivered') ? 'Đã giao hàng' : 'Đang xử lý';
                    
                    // Xác định văn bản cho trạng thái thanh toán
                    $payment_display = ($payment_status === 'complete') ? 'Đã thanh toán' : 'Chưa thanh toán';
                    $payment_color = ($payment_status === 'complete') ? 'green' : 'red';
                    ?>

                    <div class="box">
                        <div class="status" style="color:<?php echo $status_color; ?>">
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
                                <button type="submit" name="update_order" class="btn">Cập nhật đơn hàng</button>
                                <button type="submit" name="delete_order" class="btn" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này không?');">Xóa đơn hàng</button>
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