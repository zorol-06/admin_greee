<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// === HÀM ĐỊNH DẠNG TIỀN VIỆT NAM ĐẸP ===
function format_vnd($number) {
    $number = (float)$number;
    if ($number == floor($number)) {
        return number_format($number, 0, ',', '.') . ' ₫';
    } else {
        return number_format($number, 0, ',', '.') . ' ₫';
    }
}

// === TẠO DANH SÁCH NĂM TỪ 2024 ĐẾN NAY ===
$current_year = date('Y');
$available_years = range(2024, $current_year); // Từ 2024 đến năm hiện tại
rsort($available_years); // Sắp xếp giảm dần

// Xử lý chọn năm
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;
if (!in_array($selected_year, $available_years)) {
    $selected_year = $current_year; // Nếu năm không hợp lệ, mặc định năm hiện tại
}

// Xử lý xuất Excel
if (isset($_POST['export_excel'])) {
    exportToExcel($conn, $selected_year);
}

function exportToExcel($conn, $year) {
    try {
        // Doanh thu năm được chọn (dùng final_price - số tiền thực tế khách trả)
        $yearly_revenue_sql = "SELECT SUM(final_price) AS yearly_revenue 
                              FROM orders 
                              WHERE payment_status = 'complete' 
                              AND YEAR(`date`) = ?";
        $yearly_revenue_stmt = $conn->prepare($yearly_revenue_sql);
        $yearly_revenue_stmt->execute([$year]);
        $yearly_revenue = $yearly_revenue_stmt->fetch(PDO::FETCH_ASSOC)['yearly_revenue'] ?? 0;

        // Doanh thu theo tháng (dùng final_price)
        $monthly_revenue_sql = "SELECT 
                                MONTH(`date`) as revenue_month,
                                DATE_FORMAT(`date`, '%Y-%m') as revenue_period,
                                SUM(final_price) as monthly_revenue
                            FROM orders 
                            WHERE payment_status = 'complete' 
                            AND YEAR(`date`) = ?
                            GROUP BY revenue_month, revenue_period
                            ORDER BY revenue_month";
        $monthly_revenue_stmt = $conn->prepare($monthly_revenue_sql);
        $monthly_revenue_stmt->execute([$year]);
        $monthly_revenues = $monthly_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Doanh thu theo sản phẩm (tính từ final_price)
        $product_revenue_sql = "SELECT 
                               p.name, 
                               SUM(o.final_price) as revenue, 
                               SUM(o.qty) as total_sold
                               FROM orders o 
                               JOIN products p ON o.product_id = p.id 
                               WHERE o.payment_status = 'complete'
                               AND YEAR(o.`date`) = ?
                               GROUP BY o.product_id 
                               ORDER BY revenue DESC";
        $product_revenue_stmt = $conn->prepare($product_revenue_sql);
        $product_revenue_stmt->execute([$year]);
        $product_revenues = $product_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tổng số đơn hàng trong năm
        $year_orders_sql = "SELECT COUNT(*) as year_orders 
                            FROM orders 
                            WHERE payment_status = 'complete' 
                            AND YEAR(`date`) = ?";
        $year_orders_stmt = $conn->prepare($year_orders_sql);
        $year_orders_stmt->execute([$year]);
        $year_orders = $year_orders_stmt->fetch(PDO::FETCH_ASSOC)['year_orders'] ?? 0;

        // Tạo file Excel với UTF-8 BOM
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment;filename="bao_cao_doanh_thu_' . $year . '.xls"');
        header('Cache-Control: max-age=0');
        
        // Xuất BOM để hỗ trợ UTF-8
        echo "\xEF\xBB\xBF";
        
        // Bắt đầu bảng HTML
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #dddddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
                .section { margin-top: 20px; font-weight: bold; }
                .money { text-align: right; }
            </style>
        </head>
        <body>';
        
        // Tiêu đề file
        echo '<div class="title">BÁO CÁO DOANH THU NĂM ' . $year . '</div>';
        
        // Tổng quan doanh thu
        echo '<table>
                <tr><th colspan="2">TỔNG QUAN DOANH THU</th></tr>
                <tr><td>Doanh thu năm:</td><td class="money">' . format_vnd($yearly_revenue) . '</td></tr>
                <tr><td>Tổng số đơn hàng:</td><td>' . number_format($year_orders) . '</td></tr>
              </table>';
        
        echo '<br>';
        
        // Doanh thu theo tháng
        echo '<div class="section">DOANH THU THEO THÁNG</div>';
        echo '<table>
                <tr>
                    <th>Tháng</th>
                    <th>Kỳ</th>
                    <th>Doanh thu</th>
                </tr>';
        
        $month_names = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                       'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
        
        // Tạo mảng đầy đủ 12 tháng
        $full_monthly_data = [];
        for ($month = 1; $month <= 12; $month++) {
            $found = false;
            foreach ($monthly_revenues as $revenue) {
                if ($revenue['revenue_month'] == $month) {
                    $full_monthly_data[] = $revenue;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $full_monthly_data[] = [
                    'revenue_month' => $month,
                    'revenue_period' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                    'monthly_revenue' => 0
                ];
            }
        }
        
        foreach ($full_monthly_data as $revenue) {
            echo '<tr>
                    <td>' . $month_names[$revenue['revenue_month']] . '</td>
                    <td>' . $revenue['revenue_period'] . '</td>
                    <td class="money">' . format_vnd($revenue['monthly_revenue']) . '</td>
                  </tr>';
        }
        
        echo '</table>';
        echo '<br>';
        
        // Doanh thu theo sản phẩm
        echo '<div class="section">DOANH THU THEO SẢN PHẨM</div>';
        echo '<table>
                <tr>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng bán</th>
                    <th>Doanh thu</th>
                </tr>';
        
        if (!empty($product_revenues)) {
            foreach ($product_revenues as $product) {
                echo '<tr>
                        <td>' . htmlspecialchars($product['name']) . '</td>
                        <td>' . number_format($product['total_sold']) . ' sản phẩm</td>
                        <td class="money">' . format_vnd($product['revenue']) . '</td>
                      </tr>';
            }
        } else {
            echo '<tr><td colspan="3" style="text-align:center;">Chưa có dữ liệu</td></tr>';
        }
        
        echo '</table>';
        
        echo '</body></html>';
        exit;
        
    } catch(Exception $e) {
        error_log('Excel Export Error: ' . $e->getMessage());
        $_SESSION['error'] = 'Có lỗi xảy ra khi xuất file Excel: ' . $e->getMessage();
        header('location: revenue_report.php?year=' . $year);
        exit;
    }
}

// === LẤY DỮ LIỆU DOANH THU THEO NĂM ĐƯỢC CHỌN ===
try {
    // Tổng doanh thu tất cả thời gian (dùng final_price)
    $total_revenue_sql = "SELECT SUM(final_price) AS total_revenue 
                         FROM orders 
                         WHERE payment_status = 'complete'";
    $total_revenue_stmt = $conn->query($total_revenue_sql);
    $total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Doanh thu năm được chọn (dùng final_price)
    $yearly_revenue_sql = "SELECT SUM(final_price) AS yearly_revenue 
                          FROM orders 
                          WHERE payment_status = 'complete' 
                          AND YEAR(`date`) = ?";
    $yearly_revenue_stmt = $conn->prepare($yearly_revenue_sql);
    $yearly_revenue_stmt->execute([$selected_year]);
    $yearly_revenue = $yearly_revenue_stmt->fetch(PDO::FETCH_ASSOC)['yearly_revenue'] ?? 0;

    // Doanh thu 12 tháng của năm được chọn (dùng final_price)
    $monthly_revenue_sql = "SELECT 
                            MONTH(`date`) as revenue_month,
                            DATE_FORMAT(`date`, '%Y-%m') as revenue_period,
                            SUM(final_price) as monthly_revenue
                        FROM orders 
                        WHERE payment_status = 'complete' 
                        AND YEAR(`date`) = ?
                        GROUP BY revenue_month, revenue_period
                        ORDER BY revenue_month";

    $monthly_revenue_stmt = $conn->prepare($monthly_revenue_sql);
    $monthly_revenue_stmt->execute([$selected_year]);
    $monthly_revenues_raw = $monthly_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tạo mảng đầy đủ 12 tháng (kể cả tháng không có doanh thu)
    $monthly_revenues = [];
    for ($month = 1; $month <= 12; $month++) {
        $found = false;
        foreach ($monthly_revenues_raw as $revenue) {
            if ($revenue['revenue_month'] == $month) {
                $monthly_revenues[] = $revenue;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $monthly_revenues[] = [
                'revenue_month' => $month,
                'revenue_period' => $selected_year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                'monthly_revenue' => 0
            ];
        }
    }

    // Doanh thu theo sản phẩm trong năm được chọn (tính từ final_price)
    $product_revenue_sql = "SELECT 
                           p.name, 
                           SUM(o.final_price) as revenue, 
                           SUM(o.qty) as total_sold
                           FROM orders o 
                           JOIN products p ON o.product_id = p.id 
                           WHERE o.payment_status = 'complete'
                           AND YEAR(o.`date`) = ?
                           GROUP BY o.product_id 
                           ORDER BY revenue DESC";
    $product_revenue_stmt = $conn->prepare($product_revenue_sql);
    $product_revenue_stmt->execute([$selected_year]);
    $product_revenues = $product_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổng số đơn hàng tất cả thời gian
    $total_orders_sql = "SELECT COUNT(*) as total_orders 
                        FROM orders 
                        WHERE payment_status = 'complete'";
    $total_orders_stmt = $conn->query($total_orders_sql);
    $total_orders = $total_orders_stmt->fetch(PDO::FETCH_ASSOC)['total_orders'] ?? 0;

    // Đơn hàng trong năm được chọn
    $year_orders_sql = "SELECT COUNT(*) as year_orders 
                        FROM orders 
                        WHERE payment_status = 'complete' 
                        AND YEAR(`date`) = ?";
    $year_orders_stmt = $conn->prepare($year_orders_sql);
    $year_orders_stmt->execute([$selected_year]);
    $year_orders = $year_orders_stmt->fetch(PDO::FETCH_ASSOC)['year_orders'] ?? 0;

} catch(Exception $e) {
    error_log('Revenue Query Error: ' . $e->getMessage());
    $total_revenue = 0;
    $yearly_revenue = 0;
    $monthly_revenues = array_fill(0, 12, ['monthly_revenue' => 0]);
    $product_revenues = [];
    $total_orders = 0;
    $year_orders = 0;
}

// Chuẩn bị dữ liệu cho biểu đồ (đảm bảo đủ 12 tháng)
$chart_labels = [];
$chart_data = [];

$month_names = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
               'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];

foreach ($monthly_revenues as $revenue) {
    $chart_labels[] = $month_names[$revenue['revenue_month']];
    $chart_data[] = $revenue['monthly_revenue'];
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Green Coffee Admin - Báo Cáo Doanh Thu</title>
    <style>
        .revenue-dashboard {
            padding: 20px;
            background: #f5f5f5;
            min-height: calc(100vh - 200px);
        }

        .year-selector {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .year-selector label {
            font-weight: 600;
            color: #333;
        }

        .year-selector select {
            padding: 8px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .year-selector select:focus {
            outline: none;
            border-color: #667eea;
        }

        .export-btn {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .export-btn:hover {
            background: #218838;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-card.total {
            border-left-color: #4facfe;
        }

        .stat-card.yearly {
            border-left-color: #43e97b;
        }

        .stat-card.orders {
            border-left-color: #f093fb;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .amount {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card .subtext {
            font-size: 12px;
            color: #888;
        }

        .charts-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .charts-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }

        .products-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .products-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .products-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }

        .products-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .products-table tr:hover {
            background: #f8f9fa;
        }

        .revenue-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 40px;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
            }
            
            .stat-card .amount {
                font-size: 24px;
            }
            
            .year-selector {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .export-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="main">
    <div class="banner">  
        <h1>Báo Cáo Doanh Thu</h1>
    </div>

    <div class="title2">
        <a href="dashboard.php">Bảng điều khiển</a><span> / Báo Cáo Doanh Thu</span>
    </div>

    <section class="revenue-dashboard">
        <!-- Chọn năm và xuất Excel -->
        <div class="year-selector">
            <label for="yearSelect">Chọn năm:</label>
            <select id="yearSelect" onchange="window.location.href = '?year=' + this.value">
                <?php foreach ($available_years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                        Năm <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span style="color: #666; font-size: 14px;">
                📊 Đang xem dữ liệu năm <?= $selected_year ?>
            </span>
            
            <form method="post" style="margin-left: auto;">
                <button type="submit" name="export_excel" class="export-btn">
                    <i class='bx bx-download'></i> Xuất Excel
                </button>
            </form>
        </div>

        <!-- Thống kê quan trọng -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Tổng Doanh Thu</h3>
                <div class="amount"><?= format_vnd($total_revenue) ?></div>
                <div class="subtext">Tất cả đơn hàng đã thanh toán</div>
            </div>

            <div class="stat-card yearly">
                <h3>Doanh Thu Năm <?= $selected_year ?></h3>
                <div class="amount"><?= format_vnd($yearly_revenue) ?></div>
                <div class="subtext">Số tiền thực tế khách hàng trả</div>
            </div>

            <div class="stat-card orders">
                <h3>Đơn Hàng Năm <?= $selected_year ?></h3>
                <div class="amount"><?= number_format($year_orders) ?></div>
                <div class="subtext">Tổng <?= number_format($total_orders) ?> đơn tất cả</div>
            </div>
        </div>

        <!-- Biểu đồ doanh thu -->
        <div class="charts-section">
            <h2>📈 Doanh Thu Theo Tháng - Năm <?= $selected_year ?></h2>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Doanh thu theo sản phẩm -->
        <div class="products-section">
            <h2>📦 Doanh Thu Theo Sản Phẩm - Năm <?= $selected_year ?></h2>
            <?php if (!empty($product_revenues)): ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Sản Phẩm</th>
                            <th>Số Lượng Bán</th>
                            <th>Doanh Thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product_revenues as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= number_format($product['total_sold']) ?> sản phẩm</td>
                                <td><span class="revenue-badge"><?= format_vnd($product['revenue']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">Chưa có dữ liệu doanh thu theo sản phẩm cho năm <?= $selected_year ?></div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
// Biểu đồ doanh thu
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Doanh Thu - Năm <?= $selected_year ?>',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)',
                'rgba(83, 102, 255, 0.7)',
                'rgba(40, 159, 64, 0.7)',
                'rgba(210, 99, 132, 0.7)',
                'rgba(54, 62, 235, 0.7)',
                'rgba(255, 106, 86, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(199, 199, 199, 1)',
                'rgba(83, 102, 255, 1)',
                'rgba(40, 159, 64, 1)',
                'rgba(210, 99, 132, 1)',
                'rgba(54, 62, 235, 1)',
                'rgba(255, 106, 86, 1)'
            ],
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.parsed.y;
                        let formattedValue = value == Math.floor(value) ? 
                            value.toLocaleString() + ' ₫' : 
                            value.toLocaleString() + ' ₫';
                        return `Doanh thu: ${formattedValue}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' ₫';
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Hiệu ứng cho các card
document.querySelectorAll('.stat-card').forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

    setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, index * 200);
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script type="text/javascript" src="script.js"></script>
<?php include '../components/alert.php'; ?>

</body>
</html>