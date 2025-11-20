<?php
include '../components/connection.php';
session_start();

// 🔒 Kiểm tra admin đăng nhập
$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// 🗑️ Xử lý xóa tin nhắn
if (isset($_POST['delete'])) {

    $delete_id = filter_var($_POST['delete_id'], FILTER_SANITIZE_STRING);

    // Kiểm tra xem tin nhắn có tồn tại không
    $verify_delete = $conn->prepare("SELECT * FROM message WHERE id = ?");
    $verify_delete->execute([$delete_id]);

    if ($verify_delete->rowCount() > 0) {
        // Nếu có thì xóa
        $delete_message = $conn->prepare("DELETE FROM message WHERE id = ?");
        $delete_message->execute([$delete_id]);
        $success_msg[] = 'Tin nhắn đã được xóa thành công!';
    } else {
        $warning_msg[] = 'Tin nhắn này không tồn tại hoặc đã bị xóa trước đó.';
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
    <title>Green Coffee Admin Panel - Messages</title>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">  
        <h1>Tin nhắn chưa đọc</h1>
    </div>

    <div class="title2">
        <a href="dashboard.php">Dashboard</a><span> / Tin nhắn chưa đọc</span>
    </div>

    <section class="accounts">
        <h1 class="heading">Tin nhắn khách hàng</h1>
        <div class="box-container">
        <?php   
            $select_message = $conn->prepare("SELECT * FROM message ORDER BY id DESC");
            $select_message->execute();

            if ($select_message->rowCount() > 0) {
                while ($fetch_message = $select_message->fetch(PDO::FETCH_ASSOC)) {    
        ?>
            <div class="box">
                <h3 class="name"><i class='bx bx-user'></i> <?= htmlspecialchars($fetch_message['name']); ?></h3>  
                <h4><i class='bx bx-envelope'></i> <?= htmlspecialchars($fetch_message['subject']); ?></h4>
                <p><i class='bx bx-message-dots'></i> <?= nl2br(htmlspecialchars($fetch_message['message'])); ?></p>

                <form action="" method="post" class="flex-btn">
                    <input type="hidden" name="delete_id" value="<?= $fetch_message['id']; ?>"> 
                    <button type="submit" name="delete" class="btn delete-btn"
                        onclick="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này không?');">
                      
                        Xóa tin nhắn
                    </button>
                </form>           
            </div>
        <?php
                }
            } else {
                echo '
                <div class="empty">
                    <p>Không có tin nhắn nào cả</p>
                </div>';
            }
        ?>
        </div>
    </section>
</div>

<!-- sweetalert + custom JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script type="text/javascript" src="script.js"></script>
<?php include '../components/alert.php'; ?>

</body>
</html>
