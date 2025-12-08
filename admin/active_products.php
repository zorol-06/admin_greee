<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Xử lý Xóa sản phẩm
if(isset($_POST['delete'])){
    $p_id = filter_var($_POST['product_id'], FILTER_SANITIZE_STRING);
    $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_product->execute([$p_id]);
    $success_msg[] = 'Sản phẩm đã được xóa thành công'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Sản phẩm Đang hoạt động</title> </head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner"> 
        <h1>Sản phẩm Đang hoạt động</h1> </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Sản phẩm Đang hoạt động</span> </div>

    <section class="show-post">
       <div class="box-container">
        <?php
        
        $select_products = $conn->prepare("SELECT * FROM products WHERE status = 'active'");
        $select_products->execute();

        if ($select_products->rowCount() > 0) {
            while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
        ?>
            <form action="" method="post" class="box">
                <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">

                <?php if (!empty($fetch_products['image'])): ?> 
                    <img src="../image/<?= htmlspecialchars($fetch_products['image']); ?>" class="image">
                <?php endif; ?>

                <div class="status" style="color: green;">
                    <?= htmlspecialchars($fetch_products['status']); ?>
                </div>

                <div class="price">$<?= $fetch_products['price']; ?>-</div>
                <div class="title"><?= htmlspecialchars($fetch_products['name']);?></div>

                <div class="flex-btn">
                    <a href="edit_product.php?id=<?= $fetch_products['id']; ?>" class="btn">chỉnh sửa</a> <button type="submit" name="delete" class="btn" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">xóa</button> <a href="read_product.php?post_id=<?= $fetch_products['id']; ?>" class="btn">xem</a> </div>

            </form>
        <?php
            }
        } else {
            echo '
            <div class="empty">
                <p>Không tìm thấy sản phẩm đang hoạt động nào! <br> <a href="add_products.php" style="margin-top:1.5rem;" class="btn">Thêm sản phẩm</a></p> </div>';
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