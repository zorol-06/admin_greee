<?php
include '../components/connection.php';
session_start();


$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

//  Hàm dùng chung để thêm sản phẩm (cho cả publish & draft)
function add_product($conn, $status) {
    global $success_msg, $warning_msg;
    
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);

    //  Xử lý hình ảnh
    $image = $_FILES['image']['name'];
    $image = filter_var($image, FILTER_SANITIZE_STRING);
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = '../image/' . $image;

    if (!empty($image)) {
        // 🔍 Kiểm tra ảnh có bị trùng tên không
        $check_image = $conn->prepare("SELECT * FROM products WHERE image = ?");
        $check_image->execute([$image]);

        if ($check_image->rowCount() > 0) {
            $warning_msg[] = 'Tên ảnh đã tồn tại, vui lòng đổi tên file khác.';
        } elseif ($image_size > 2000000) {
            $warning_msg[] = 'Kích thước ảnh vượt quá 2MB.';
        } else {
            // Chỉ di chuyển tệp nếu không có lỗi
            move_uploaded_file($image_tmp_name, $image_folder);
        }
    } else {
        $image = '';
    }

    //  Nếu không có lỗi, thêm sản phẩm vào database
    // Cần kiểm tra lại nếu có lỗi hình ảnh thì vẫn bị chạy đoạn insert này.
    // Tốt nhất là kiểm tra $warning_msg có rỗng không TRƯỚC khi chạy SQL
    if (empty($warning_msg)) {
        $insert = $conn->prepare("
            INSERT INTO products ( name, price, image, product_detail, status)
            VALUES ( ?, ?, ?, ?, ?)
        ");
        $insert->execute([ $name, $price, $image, $content, $status]);

        if ($status === 'active') {
            $success_msg[] = 'Thêm sản phẩm và xuất bản thành công!'; // ĐÃ CHUYỂN VIỆT HÓA
        } else {
            $success_msg[] = 'Lưu sản phẩm dưới dạng nháp thành công!'; // ĐÃ CHUYỂN VIỆT HÓA
        }
    }
}

// 🟢 Xử lý khi admin nhấn “Xuất bản”
if (isset($_POST['publish'])) {
    add_product($conn, 'active');
}

// 🟡 Xử lý khi admin nhấn “Lưu nháp”
if (isset($_POST['draft'])) {
    add_product($conn, 'deactive');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin - Thêm Sản phẩm</title> </head>
<body>

    <?php include '../components/admin_header.php'; ?>

    <div class="main">
        <div class="banner">  
            <h1>Thêm Sản phẩm</h1> </div>

        <div class="title2">
            <a href="dashboard.php">Bảng điều khiển</a><span> / Thêm Sản phẩm</span> </div>

        <section class="form-container">
            <h1 class="heading">Thêm Sản phẩm Mới</h1> <form action="" method="post" enctype="multipart/form-data">

                <div class="input-field">
                    <label>Tên Sản phẩm <sup>*</sup></label> <input type="text" name="name" maxlength="100" required placeholder="Nhập tên sản phẩm"> </div>

                <div class="input-field">
                    <label>Giá Sản phẩm <sup>*</sup></label> <input type="number" name="price" min="0" required placeholder="Nhập giá sản phẩm"> </div>

                <div class="input-field">
                    <label>Mô tả Sản phẩm <sup>*</sup></label> <textarea name="content" maxlength="10000" required placeholder="Nhập mô tả sản phẩm"></textarea> </div>

                <div class="input-field">
                    <label>Hình ảnh Sản phẩm <sup>*</sup></label> <input type="file" name="image" accept="image/*" required>
                </div>

                <div class="flex-btn">
                    <button type="submit" name="publish" class="btn">Thêm Sản phẩm</button> <button type="submit" name="draft" class="btn">Lưu thành Bản nháp</button> </div>
            </form>
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script type="text/javascript" src="script.js"></script>
    <?php include '../components/alert.php'; ?>

</body>
</html>