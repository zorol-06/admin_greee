<?php
require_once 'mailer/Exception.php';
require_once 'mailer/PHPMailer.php';
require_once 'mailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMail($mailTo, $subject, $content) {
    // Lưu ý: Tên biến $username và $password cần được bảo mật
    $username = 'hoaimanh041106@gmail.com';
    $password = 'boov bdqx qpmm zxhl'; // Mật khẩu Ứng dụng (App Password)

    $mail = new PHPMailer(true);

    try {
        // Cấu hình máy chủ - QUAN TRỌNG: Tắt debug khi chạy chính thức
        $mail->SMTPDebug = 0; // ⚠️ ĐÃ SỬA: Tắt debug (0 = off)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Người nhận và người gửi
        $mail->setFrom($username, 'Green Coffee'); // ⚠️ ĐÃ SỬA: Thay "Mailer" bằng tên thương hiệu
        $mail->addAddress($mailTo);
        
        // Thêm reply-to address
        $mail->addReplyTo($username, 'Green Coffee Support');

        // Nội dung
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $content;
        
        // Thêm AltBody cho client không hỗ trợ HTML
        $plainContent = strip_tags($content);
        $mail->AltBody = $plainContent;

        // 🔹 QUAN TRỌNG: Không echo trong function, trả về boolean
        if ($mail->send()) {
            return true; // ✅ Thành công
        } else {
            error_log("Mailer Error: " . $mail->ErrorInfo); // Ghi log lỗi
            return false; // ❌ Thất bại
        }
        
    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage()); // Ghi log exception
        return false; // ❌ Thất bại
    }
}
?>