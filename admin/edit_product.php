<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Xử lý CẬP NHẬT sản phẩm
if (isset($_POST['update'])) {

    $post_id = $_POST['product_id'] ?? '';

    $name    = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $price   = filter_var($_POST['price'] ?? '', FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'] ?? '', FILTER_SANITIZE_STRING);
    $status  = filter_var($_POST['status'] ?? 'deactive', FILTER_SANITIZE_STRING);

    $update_product = $conn->prepare("UPDATE products SET name = ?, price = ?, product_detail = ?, status = ? WHERE id = ?");
    $update_product->execute([$name, $price, $content, $status, $post_id]);

    $success_msg[] = 'Sản phẩm đã được cập nhật thành công';

    

    $old_image = $_POST['old_image'];
    $image = $_FILES['image']['name'];
    $image = filter_var($image, FILTER_SANITIZE_STRING);
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = '../image/' . $image;

    $select_image = $conn->prepare("SELECT * FROM products WHERE image = ?");
    $select_image->execute([$image]);

    if (!empty($image)) {
        if ($image_size > 2000000) { // Đã sửa giới hạn kích thước ảnh (2MB)
            $warning_msg[] = 'Kích thước ảnh quá lớn'; // ĐÃ CHUYỂN VIỆT HÓA
        } elseif ($select_image->rowCount() > 0 && $image != '') {
            $warning_msg[] = 'Vui lòng đổi tên ảnh'; // ĐÃ CHUYỂN VIỆT HÓA
        } else {
            $update_image = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
            $update_image->execute([$image, $post_id]);
            move_uploaded_file($image_tmp_name, $image_folder);

            if ($old_image != $image && $old_image != '') {
                unlink('../image/' . $old_image);
                $success_msg[] = 'Ảnh đã được cập nhật thành công'; // ĐÃ CHUYỂN VIỆT HÓA
            }
        }
    }
}


// Xử lý XÓA sản phẩm
if (isset($_POST['delete'])) {
    $p_id = filter_var($_POST['product_id'], FILTER_SANITIZE_STRING);

    $delete_image = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $delete_image->execute([$p_id]); // Đã sửa lỗi sai cú pháp SQL
    
    $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);

    if($fetch_delete_image['image'] !=''){
        unlink('../image/'.$fetch_delete_image['image']);
    }

    $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_product->execute([$p_id]);
    header('location:view_product.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Chỉnh sửa Sản phẩm</title> </head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">  
        <h1>Chỉnh sửa Sản phẩm</h1> </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Chỉnh sửa Sản phẩm</span> </div>

    <section class="edit-post">
    <h1 class="heading">Chỉnh sửa sản phẩm</h1> <?php 
        $post_id = $_GET['id'];

        $select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $select_product->execute([$post_id]);

        if($select_product->rowCount() > 0){
            while($fetch_product = $select_product->fetch(PDO::FETCH_ASSOC)){
                
           ?>
           <div class="form-container">
            <form action="" method="post" enctype="multipart/form-data">
             <input type="hidden" name="old_image" value="<?= $fetch_product['image']; ?>">
             <input type="hidden" name="product_id" value="<?= $fetch_product['id']; ?>">    
        
            <div class="input-field">
            <label for="">Cập nhật trạng thái</label>
            <select name="status" required>
            <option value="active" <?= ($fetch_product['status'] == 'active') ? 'selected' : '' ?>>Hoạt động</option>
            <option value="deactive" <?= ($fetch_product['status'] == 'deactive') ? 'selected' : '' ?>>Ngừng hoạt động</option>
            </select>
            </div>
            <div class="input-field">
            <label for="">Tên sản phẩm</label> <input type="text" name="name" value="<?= $fetch_product['name'] ?>" >      
            </div>
            <div class="input-field">
            <label for="">Giá sản phẩm</label> <input type="number" name="price" value="<?= $fetch_product['price'] ?>" >      
            </div> 
             <div class="input-field">
            <label for="">Mô tả sản phẩm</label> <textarea name="content" ><?= $fetch_product['product_detail'] ?></textarea>    
             </div>
             <div class="input-field">
            <label for="">Ảnh sản phẩm</label> <input type="file" name="image" accept="image/*">
             <img src="../image/<?= $fetch_product['image']; ?>" alt="Ảnh sản phẩm hiện tại"> </div>
         <div class="flex-btn">
         <button type="submit" name="update" class="btn">Cập nhật sản phẩm</button> <a href="view_product.php" class="btn">Quay lại</a> <button type="submit" name="delete" class="btn" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">Xóa sản phẩm</button> </div>
         </form>     
           </div>  
         <?php 
       }
         } else {
             echo '
             <div class="empty">
                 <p>Chưa có sản phẩm nào được thêm! <br> 
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