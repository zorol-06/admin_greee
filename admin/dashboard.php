<?php
include '../components/connection.php';

    session_start();

    $admin_id = $_SESSION['admin_id'];
    if(!isset($admin_id)){
        header('location:login.php');
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
    <title>green coffee admin panel - dashboard page</title>

</head>
<body>

    <?php include '../components/admin_header.php'; ?>
    <div class="main">
        <div class="banner">  
            <h1>dashboard</h1>
        </div>
        <div class="title2">
            <a href="dashboard.php">home</a><span>dashboard</span>
        </div>
        <section class="dashboard">
           <h1 class="heading">dshboard</h1>
           <div class="box-container">
            <div class="box">
            <h3>chào mừng!</h3>
            <p><?=$fetch_profile['name'];?></p>
            <a href="" class="btn">profile</a>
            </div>
            <div class="box">    
            <?php
            $select_product = $conn->prepare("SELECT * FROM products");
            $select_product->execute();
            $num_of_products = $select_product->rowCount();
            ?>
            <h3><?=$num_of_products;?></h3>
            <p>sản phẩm được thêm vào</p>
            <a href="add_products.php"class="btn">thêm sản phẩm mới</a>
            </div>
            <div class="box">    
            <?php
            $select_active_product = $conn->prepare("SELECT * FROM products WHERE status = ?");
            $select_active_product->execute(['active']);
            $num_of_active_products = $select_active_product->rowCount();
            ?>
            <h3><?=$num_of_active_products;?></h3>
            <p>tổng sản phẩm đang hoạt động </p>
            <a href="active_products.php"class="btn">xem sản phẩm đang hoạt động </a>
           </div>
            <div class="box">    
            <?php
            $select_deactive_product = $conn->prepare("SELECT * FROM products WHERE status = ?");
            $select_deactive_product->execute(['deactive']);
            $num_of_deactive_products = $select_deactive_product->rowCount();
            ?>
            <h3><?=$num_of_deactive_products;?></h3>
            <p>tổng số sản phẩm không hoạt động</p>
            <a href="inactive_products.php"class="btn">xem sản phẩm không hoạt động</a>
            </div>
            <div class="box">    
            <?php
            $select_users = $conn->prepare("SELECT * FROM users");
            $select_users->execute();
            $num_of_users = $select_users->rowCount();
            ?>
            <h3><?=$num_of_users;?></h3>
            <p>người dùng đã đăng ký</p>
            <a href="user_account.php"class="btn">xem người dùng</a>
            </div>
             <div class="box">    
            <?php
            $select_admin = $conn->prepare("SELECT * FROM admin");
            $select_admin->execute();
            $num_of_admin = $select_admin->rowCount();
            ?>
            <h3><?=$num_of_admin;?></h3>
            <p>admin đã đăng ký</p>
            <a href="admin_acount.php"class="btn">xem admin</a>
            </div>
            <div class="box">    
            <?php
            $select_message = $conn->prepare("SELECT * FROM message");
            $select_message->execute();
            $num_of_message = $select_message->rowCount();
            ?>
            <h3><?=$num_of_message;?></h3>
            <p>Tin nhắn khách hàng</p>
            <a href="admin_message.php"class="btn">xem tin nhắn</a>
            </div>
            <div class="box">    
            <?php
            $select_pending_orders = $conn->prepare("SELECT * FROM orders WHERE status = ?");
            $select_pending_orders->execute(['pending']);
            $num_of_pending_orders = $select_pending_orders->rowCount();
            ?>
            <h3><?=$num_of_pending_orders;?></h3>
            <p>Đơn hàng cần xử lý</p>
            <a href="order_processing.php"class="btn">xem đơn hàng</a>
            </div>
         <div class="box">    
    <?php
    $select_completed_orders = $conn->prepare("SELECT * FROM orders WHERE status = ? AND payment_status = ?");
    $select_completed_orders->execute(['delivered', 'complete']);
    $num_of_completed_orders = $select_completed_orders->rowCount();
    ?>
    <h3><?= $num_of_completed_orders; ?></h3>
    <p>Tổng số đơn hàng hoàn tất</p>
    <a href="completed_orders.php" class="btn">Xem đơn hàng hoàn tất</a>
</div>
           <div class="box">    
    <?php
    
    $select_canceled_orders = $conn->prepare("SELECT * FROM orders WHERE status = ?");
    $select_canceled_orders->execute(['cancelled']);
    $num_of_canceled_orders = $select_canceled_orders->rowCount();
    ?>
    <h3><?= $num_of_canceled_orders; ?></h3>
    <p>Tổng số đơn hàng bị hủy</p>
    <a href="cancelled_orders.php" class="btn">Xem tổng số đơn hàng bị hủy</a>
</div>
             <div class="box">    
              <?php
             $select_coupons = $conn->prepare("SELECT * FROM coupons");
             $select_coupons->execute();
             $num_of_coupons = $select_coupons->rowCount();
              ?>
    
             <h3><?= $num_of_coupons; ?></h3>
             <p>Tổng số mã giảm giá</p>
             <a href="coupons.php" class="btn">Xem mã giảm giá</a>
            </div>
            <div class="box">    
              <?php
           $select_pending_orders = $conn->prepare("SELECT * FROM orders "); 
           $select_pending_orders->execute(); 
           $num_of_pending_orders = $select_pending_orders->rowCount()

              ?>
    
             <h3><?= $num_of_pending_orders; ?></h3>
             <p>Tổng số đơn hàng</p>
             <a href="order.php" class="btn">Xem đơn hàng</a>
            </div>
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