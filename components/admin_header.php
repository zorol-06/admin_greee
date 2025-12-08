<header class="header">
    <div class="flex">
        <a href="dashboard.php" class="logo"><img src="../img/logo.jpg" alt="Logo"></a>
        
       <nav class="navbar">
            <a href="dashboard.php">Bảng điều khiển</a>
            <a href="add_products.php">Thêm sản phẩm</a>
            <a href="view_product.php">Xem sản phẩm</a>
            <a href="RevenueReport.php">xem doanh thu</a>
        </nav>  
        
        <div class="icons">
            <i class="bx bxs-user" id="user-btn"></i> <i class="bx bx-list-plus" id="menu-btn"></i> </div>
        
        <div class="profile-detail">
            <?php
            
            $select_profile = $conn->prepare("SELECT * FROM admin WHERE id =?");
            $select_profile->execute([$admin_id]);

            if($select_profile->rowCount() > 0){
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC); 
            
            ?>
            <div class="profile">
                <img src="../image/<?= $fetch_profile['profile']; ?>" class="logo-img" alt="Ảnh đại diện">
                <p><?= $fetch_profile['name']; ?></p>
            </div>
            
            <div class="flex-btn">
                <a href="profile.php" class="btn">Hồ sơ</a>
                <a href="../components/admin_logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi trang web này?');" class="btn">Đăng xuất</a>
            </div>
            <?php 
            }
            // Nếu không tìm thấy hồ sơ Admin, có thể thêm thông báo hoặc nút đăng nhập/đăng ký ở đây
            ?>
        </div>
    </div>
</header>