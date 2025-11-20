<?php
include '../components/connection.php';
session_start();

if (isset($_POST['login'])) {

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

    $select_admin = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $select_admin->execute([$email]);

    if ($select_admin->rowCount() > 0) {
        $row = $select_admin->fetch(PDO::FETCH_ASSOC);

        if (password_verify($pass, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            header('location:dashboard.php');
            exit;
        } else {
            $warning_msg[] = 'Mật khẩu không đúng!'; // ĐÃ CHUYỂN VIỆT HÓA
        }
    } else {
        $warning_msg[] = 'Email không tồn tại!'; // ĐÃ CHUYỂN VIỆT HÓA
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?> ">
    <title>Green Coffee Admin Panel - Đăng nhập</title> </head>
<body>

    <div class="main">
        <section>
            <div class="form-container" id="admin_login">
           <form action="" method="post" enctype="multipart/form-data">
            <h3>Đăng nhập ngay</h3> <div class="input-field">
            <label for="">Email người dùng <sup>*</sup></label> <input type="email" name="email" maxlength="20" required placeholder="nhập email của bạn" oninput="this.value = this.value.replace(/\s/g,'')">    
            </div>
            <div class="input-field">
            <label for="">Mật khẩu người dùng <sup>*</sup></label> <input type="password" name="password" maxlength="20" required placeholder="nhập mật khẩu của bạn" oninput="this.value = this.value.replace(/\s/g,'')">    
            </div> 
            
            
            <button type="submit" name="login" class="btn">Đăng nhập ngay</button> <p>Bạn chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p> </form>
            </div>
        </section>
    </div>

      <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script type="text/javascript" src="script.js"></script>
      <?php include '../components/alert.php'; ?>

</body>
</html>