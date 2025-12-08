
<?php
include '../components/connection.php';
include '../functions.php'; // có hàm sendMail()
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

/* ==================== XỬ LÝ XÓA BẰNG AJAX ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = (int)$_POST['user_id'];

    // Không cho tự xóa admin
    if ($user_id === $admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể tự xóa tài khoản Admin đang đăng nhập!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng!']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // XÓA THẬT
    $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete->execute([$user_id]);

    // GỬI EMAIL THÔNG BÁO
    $subject = "Tài khoản Green Coffee của bạn đã bị xóa";
    $message = "
    <html>
    <body style='font-family:Arial,sans-serif;background:#f9f9f9;padding:20px;margin:0;'>
        <div style='max-width:600px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.1);'>
            <div style='background:linear-gradient(135deg,#d32f2f,#f44336);color:white;padding:30px;text-align:center;'>
                <h1 style='margin:0;'>Green Coffee</h1>
            </div>
            <div style='padding:35px;line-height:1.7;'>
                <h2 style='color:#d32f2f;margin-top:0;'>TÀI KHOẢN ĐÃ BỊ XÓA</h2>
                <p>Xin chào <strong>{$user['name']}</strong>,</p>
                <p>Chúng tôi rất tiếc phải thông báo rằng tài khoản của bạn đã bị <strong>xóa vĩnh viễn</strong> khỏi hệ thống Green Coffee.</p>
                <p>Nếu bạn cho rằng đây là nhầm lẫn, vui lòng liên hệ ngay:</p>
                <p style='background:#f0f0f0;padding:15px;border-radius:8px;'>
                    <strong>Email:</strong> support@greencoffee.vn<br>
                    <strong>Hotline:</strong> 0336.965.264
                </p>
                <p>Trân trọng,<br><strong>Đội ngũ Green Coffee</strong></p>
            </div>
            <div style='background:#f1f1f1;padding:20px;text-align:center;font-size:12px;color:#666;'>
                © " . date('Y') . " Green Coffee – Đây là email tự động, không trả lời thư này.
            </div>
        </div>
    </body>
    </html>";

    $mailSent = sendMail($user['email'], $subject, $message);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Đã xóa thành công <b>' . htmlspecialchars($user['name']) . '</b>' . ($mailSent ? ' và gửi email thông báo!' : ' (gửi email thất bại)')
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Coffee Admin - Quản lý người dùng</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <style>
        .delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #ff416c, #ff4757);
            color: white;
            padding: 13px 28px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(255, 65, 108, 0.4);
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .delete-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(255, 65, 108, 0.6);
        }
        .delete-btn i { font-size: 18px; }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">
        <h1>Quản lý người dùng</h1>
    </div>
    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a> <span>/ Người dùng đã đăng ký</span>
    </div>

    <section class="accounts">
        <h1 class="heading">Danh sách người dùng</h1>
        <div class="box-container">
            <?php
            $select = $conn->prepare("SELECT * FROM users ORDER BY id DESC");
            $select->execute();
            if ($select->rowCount() > 0) {
                while ($user = $select->fetch(PDO::FETCH_ASSOC)) {
                    $uid = $user['id'];
            ?>
                    <div class="box">
                        <p><strong>ID:</strong> <span><?= $uid ?></span></p>
                        <p><strong>Họ tên:</strong> <span><?= htmlspecialchars($user['name']) ?></span></p>
                        <p><strong>Email:</strong> <span><?= htmlspecialchars($user['email']) ?></span></p>
                        <p><strong>Ngày đăng ký:</strong> 
                            <span><?= date('d/m/Y H:i', strtotime($user['created_at'] ?? 'now')) ?></span>
                        </p>

                        <?php if ($uid != $admin_id): ?>
                            <button class="delete-btn" onclick="deleteUser(<?= $uid ?>, '<?= addslashes(htmlspecialchars($user['name'])) ?>')">
                                <i class='bx bx-trash'></i> Xóa tài khoản
                            </button>
                        <?php else: ?>
                            <p style="color:#28a745;font-weight:bold;margin-top:15px;">
                                Đây là tài khoản Admin (được bảo vệ)
                            </p>
                        <?php endif; ?>
                    </div>
            <?php
                }
            } else {
                echo '<div class="empty"><p>Chưa có người dùng nào đăng ký!</p></div>';
            }
            ?>
        </div>
    </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script>
function deleteUser(id, name) {
    swal({
        title: "Xóa tài khoản này?",
        text: "Tên: " + name + "\nHành động không thể hoàn tác!",
        icon: "warning",
        buttons: ["Hủy", "Xóa ngay"],
        dangerMode: true,
    }).then((confirm) => {
        if (confirm) {
            swal({ title: "Đang xóa...", allowOutsideClick: false, didOpen: () => swal.showLoading() });

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete_user&user_id=' + id
            })
            .then(r => r.json())
            .then(result => {
                if (result.status === 'success') {
                    swal("Thành công!", result.message, "success").then(() => {
                        location.reload(); // TỰ ĐỘNG RELOAD TRANG SAU KHI XÓA
                    });
                } else {
                    swal("Lỗi!", result.message, "error");
                }
            })
            .catch(() => swal("Lỗi!", "Không thể kết nối đến server!", "error"));
        }
    });
}
</script>

<?php include '../components/alert.php'; ?>
</body>
</html>