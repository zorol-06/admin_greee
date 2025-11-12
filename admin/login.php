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
            $warning_msg[] = 'Incorrect password!';
        }
    } else {
        $warning_msg[] = 'Email not found!';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--boxicons cnd link-->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?> ">
    <title>green coffee admin panel - register page</title>

</head>
<body>

    <div class="main">
        <section>
            <div class="form-container" id="admin_login">
           <form action=""method="post" enctype="multipart/form-data">
            <h3>login now</h3>
           
            <div class="input-field">
            <label for="">user email <sup>*</sup></label>
             <input type="email" name="email" maxlength="20" required placeholder="enter your email"
            oninput="this.value = this.value.replace(/\s/g,'')">     
            </div>
            <div class="input-field">
            <label for="">user password <sup>*</sup></label>
             <input type="password" name="password" maxlength="20" required placeholder="enter your password"
            oninput="this.value = this.value.replace(/\s/g,'')">     
            </div> 
             
            
            <button type="submit" name="login" class="btn">login now</button>
            <p>do not have an account ? <a href="register.php">register now</a></p>
        </form>
            </div>
        </section>
    </div>

     <!--sweetalert cdn link-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <!--custom js link-->
    <script type="text/javascript" src="script.js"></script>
     <!-- alert -->
    <?php include '../components/alert.php'; ?>

</body>
</html>