<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Kiểm tra post_id hợp lệ
$get_id = $_GET['post_id'] ?? null;

if (!$get_id) {
    // Nếu không có post_id, quay về danh sách sản phẩm
    header('location:view_product.php');
    exit;
}

// Xóa sản phẩm
if (isset($_POST['delete'])) {
    $p_id = filter_var($_POST['product_id'], FILTER_SANITIZE_STRING);

    $delete_image = $conn->prepare("SELECT * FROM products WHERE id = ?");
    // LỖI LOGIC/CÚ PHÁP ĐÃ ĐƯỢC PHÁT HIỆN TRƯỚC ĐÓ VÀ ĐƯỢC SỬA TẠI ĐÂY
    $delete_image->execute([$p_id]); // ĐÃ SỬA: Bỏ dấu nháy đơn

    $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);

    if($fetch_delete_image['image'] !=''){
        unlink('../image/'.$fetch_delete_image['image']);
    }

    $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_product->execute([$p_id]);
    header('location:view_product.php');
    exit; // Thêm exit sau khi chuyển hướng
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Xem Sản phẩm</title> </head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">  
        <h1>Xem Sản phẩm</h1> </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Xem Sản phẩm</span> </div>

    <section class="read-post">
        <h1 class="heading">Chi tiết Sản phẩm</h1> <?php 
        $select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $select_product->execute([$get_id]);

        if ($select_product->rowCount() > 0) {
            while ($fetch_product = $select_product->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <form action="" method="post">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($fetch_product['id']); ?>">        

            <div class="status" 
                 style="color: <?php if($fetch_product['status'] == 'active') {echo 'green';} else {echo 'red'; } ?>">
                 <?= ($fetch_product['status'] == 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động'); ?> 
            </div>

            <?php if ($fetch_product['image'] != '') { ?>
                <img src="../image/<?= htmlspecialchars($fetch_product['image']); ?>" alt="Hình ảnh sản phẩm" class="image">
            <?php } ?>
             <div class="price">$<?= htmlspecialchars($fetch_product['price']); ?>-</div>
             <div class="title"><?= htmlspecialchars($fetch_product['name']); ?></div>
             <div class="content"><?= htmlspecialchars($fetch_product['product_detail']); ?></div>  
             <div class="flex-btn">
             <a href="edit_product.php?id=<?= htmlspecialchars($fetch_product['id']); ?>" class="btn">Chỉnh sửa</a>       <button type="submit" name="delete" class="btn" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">Xóa</button> <a href="view_product.php" class="btn">Quay lại</a> </div>
            </form>
        <?php 
            }
        } else {
            echo '
            <div class="empty">
                <p>Không tìm thấy sản phẩm! <br> 
                <a href="add_products.php" style="margin-top:1.5rem;" class="btn">Thêm sản phẩm</a></p> </div>';
        }
        ?>  
    </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script type="text/javascript" src="script.js"></script>
<?php include '../components/alert.php'; ?>

</body>
</html>