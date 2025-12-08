<?php
include '../components/connection.php';
session_start();

if (isset($_POST['register'])) {
   

    $name  = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $pass  = htmlspecialchars(trim($_POST['password']));
    $cpass = htmlspecialchars(trim($_POST['cpassword']));

    // Xử lý ảnh
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_STRING);
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../image/' . $image;
    } else {
        $image = 'default.png';
    }

    // Kiểm tra email trùng
    $select_admin = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $select_admin->execute([$email]);

    if ($select_admin->rowCount() > 0) {
        $warning_msg[] = 'User email already exists!';
    } elseif ($pass != $cpass) {
        $warning_msg[] = 'Confirm password not matched!';
    } else {
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

        $insert_admin = $conn->prepare("INSERT INTO admin ( name, email, password, profile) VALUES ( ?, ?, ?, ?)");
        $insert_admin->execute([ $name, $email, $hashed_pass, $image]);

        if ($insert_admin->rowCount() > 0) {
            if (!empty($_FILES['image']['name'])) {
                move_uploaded_file($image_tmp_name, $image_folder);
            }
            $success_msg[] = 'New user registered successfully!';
        } else {
            $error_msg[] = 'User registration failed!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Register Page</title>
</head>
<body>

<div class="main">
    <section>
        <div class="form-container" id="admin_login">
            <form action="" method="post" enctype="multipart/form-data">
                <h3>register now</h3>

                <div class="input-field">
                    <label>user name <sup>*</sup></label>
                    <input type="text" name="name" maxlength="20" required placeholder="enter your name"
                           oninput="this.value=this.value.replace(/\s/g,'')">
                </div>

                <div class="input-field">
                    <label>user email <sup>*</sup></label>
                    <input type="email" name="email" maxlength="50" required placeholder="enter your email"
                           oninput="this.value=this.value.replace(/\s/g,'')">
                </div>

                <div class="input-field">
                    <label>user password <sup>*</sup></label>
                    <input type="password" name="password" maxlength="20" required placeholder="enter your password"
                           oninput="this.value=this.value.replace(/\s/g,'')">
                </div>

                <div class="input-field">
                    <label>confirm password <sup>*</sup></label>
                    <input type="password" name="cpassword" maxlength="20" required placeholder="confirm your password"
                           oninput="this.value=this.value.replace(/\s/g,'')">
                </div>

                <div class="input-field">
                    <label>select profile <sup>*</sup></label>
                    <input type="file" name="image" accept="image/*">
                </div>

                <button type="submit" name="register" class="btn">register now</button>
                <p>already have an account ? <a href="login.php">login now</a></p>
            </form>
        </div>
    </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="script.js"></script>
<?php include '../components/alert.php'; ?>
</body>
</html>