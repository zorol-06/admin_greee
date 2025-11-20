<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <title>Green Coffee Admin Panel - Registered Admin</title>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">  
        <h1>Quản Trị Viên  </h1>
    </div>

    <div class="title2">
        <a href="dashboard.php">Dashboard</a><span> / Quản Trị Viên </span>
    </div>

    <section class="accounts">
        <h1 class="heading">Quản Trị Viên  </h1>
        <div class="box-container">
        <?php   
            $select_admin = $conn->prepare("SELECT * FROM admin");
            $select_admin->execute();

            if ($select_admin->rowCount() > 0) {
                while ($fetch_admin = $select_admin->fetch(PDO::FETCH_ASSOC)) {    
                    $admin_id = $fetch_admin['id'];      
        ?>
            <div class="box">
                <p>Admin ID : <span><?= htmlspecialchars($admin_id); ?></span></p>
                <p>Admin Name : <span><?= htmlspecialchars($fetch_admin['name']); ?></span></p>
                <p>Admin Email : <span><?= htmlspecialchars($fetch_admin['email']); ?></span></p>
            </div>
        <?php
                }
            } else {
                echo '
                <div class="empty">
                    <p>No Registered Admin yet!</p>
                </div>';
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
