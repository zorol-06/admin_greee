<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Xử lý thêm mã giảm giá
if (isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $discount_type = $_POST['discount_type'];
    $discount_value = (int)$_POST['discount_value'];
    $min_order = (int)$_POST['min_order'];
    $expire_date = $_POST['expire_date'];
    $status = $_POST['status'];

    // Kiểm tra mã trùng
    $check_code = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
    $check_code->execute([$code]);

    if ($check_code->rowCount() > 0) {
        $warning_msg[] = 'Mã giảm giá đã tồn tại!';
    } else {
        $insert_coupon = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, expire_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_coupon->execute([$code, $discount_type, $discount_value, $min_order, $expire_date, $status]);
        $success_msg[] = 'Thêm mã giảm giá thành công!';
    }
}

// Xóa mã giảm giá
if (isset($_POST['delete_coupon'])) {
    $coupon_id = (int)$_POST['coupon_id'];
    $delete_coupon = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $delete_coupon->execute([$coupon_id]);
    $success_msg[] = 'Xóa mã giảm giá thành công!';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css">
    <title>Quản lý mã giảm giá</title>
</head>
<body>
    <?php include '../components/admin_header.php'; ?>
    
    <div class="main">
        <div class="banner">
            <h1>Quản lý mã giảm giá</h1>
        </div>

        <!-- Form thêm mã giảm giá -->
        <section class="form-container">
            <h2>Thêm mã giảm giá mới</h2>
            <form action="" method="post">
                <div class="input-field">
                    <label>Mã giảm giá:</label>
                    <input type="text" name="code" required placeholder="Nhập mã">
                </div>
                <div class="input-field">
                    <label>Loại giảm giá:</label>
                    <select name="discount_type" required>
                        <option value="percent">Phần trăm (%)</option>
                        <option value="fixed">Số tiền cố định</option>
                    </select>
                </div>
                <div class="input-field">
                    <label>Giá trị giảm:</label>
                    <input type="number" name="discount_value" required placeholder="10 hoặc 50000">
                </div>
                <div class="input-field">
                    <label>Đơn hàng tối thiểu:</label>
                    <input type="number" name="min_order" value="0" placeholder="0">
                </div>
                <div class="input-field">
                    <label>Ngày hết hạn:</label>
                    <input type="date" name="expire_date" required>
                </div>
                <div class="input-field">
                    <label>Trạng thái:</label>
                    <select name="status" required>
                        <option value="active">Kích hoạt</option>
                        <option value="inactive">Vô hiệu</option>
                    </select>
                </div>
                <button type="submit" name="add_coupon" class="btn">Thêm mã</button>
            </form>
        </section>

        <!-- Danh sách mã giảm giá -->
        <section class="coupon-list">
            <h2>Danh sách mã giảm giá</h2>
            <div class="box-container">
                <?php
                $select_coupons = $conn->prepare("SELECT * FROM coupons ORDER BY id DESC");
                $select_coupons->execute();
                
                if ($select_coupons->rowCount() > 0) {
                    while ($coupon = $select_coupons->fetch(PDO::FETCH_ASSOC)) {
                        $is_expired = strtotime($coupon['expire_date']) < time();
                ?>
                <div class="box <?php echo $is_expired ? 'expired' : ''; ?>">
                    <div class="coupon-header">
                        <h3><?php echo htmlspecialchars($coupon['code']); ?></h3>
                        <span class="status"><?php echo $coupon['status'] == 'active' ? 'Đang hoạt động' : 'Vô hiệu'; ?></span>
                    </div>
                    <div class="coupon-details">
                        <p>Giảm: <span>
                            <?php 
                            echo $coupon['discount_type'] == 'percent' 
                                ? $coupon['discount_value'] . '%' 
                                : number_format($coupon['discount_value']) . ' VND';
                            ?>
                        </span></p>
                        <p>Đơn tối thiểu: <span><?php echo number_format($coupon['min_order']); ?> VND</span></p>
                        <p>Hết hạn: <span><?php echo date('d/m/Y', strtotime($coupon['expire_date'])); ?></span></p>
                    </div>
                    <form action="" method="post" class="delete-form">
                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                        <button type="submit" name="delete_coupon" class="btn delete-btn" 
                                onclick="return confirm('Bạn có chắc muốn xóa mã này?')">Xóa</button>
                    </form>
                </div>
                <?php
                    }
                } else {
                    echo '<p class="empty">Chưa có mã giảm giá nào!</p>';
                }
                ?>
            </div>
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <?php include '../components/alert.php'; ?>
</body>
</html>