<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Xử lý chọn năm
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$current_year = date('Y');

// Lấy danh sách các năm có dữ liệu
try {
    $years_sql = "SELECT DISTINCT YEAR(`date`) as year FROM orders ORDER BY year DESC";
    $years_stmt = $conn->query($years_sql);
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $available_years = [$current_year];
}

// Lấy dữ liệu doanh thu theo năm được chọn
try {
    // Tổng doanh thu
    $total_revenue_sql = "SELECT SUM(price * qty) AS total_revenue FROM orders WHERE payment_status = 'complete'";
    $total_revenue_stmt = $conn->query($total_revenue_sql);
    $total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Doanh thu năm được chọn
    $yearly_revenue_sql = "SELECT SUM(price * qty) AS yearly_revenue 
                          FROM orders 
                          WHERE payment_status = 'complete' 
                          AND YEAR(`date`) = ?";
    $yearly_revenue_stmt = $conn->prepare($yearly_revenue_sql);
    $yearly_revenue_stmt->execute([$selected_year]);
    $yearly_revenue = $yearly_revenue_stmt->fetch(PDO::FETCH_ASSOC)['yearly_revenue'] ?? 0;

    // Doanh thu 12 tháng của năm được chọn
    $monthly_revenue_sql = "SELECT 
                            MONTH(`date`) as revenue_month,
                            DATE_FORMAT(`date`, '%Y-%m') as revenue_period,
                            SUM(price * qty) as monthly_revenue
                        FROM orders 
                        WHERE payment_status = 'complete' 
                        AND YEAR(`date`) = ?
                        GROUP BY revenue_month, revenue_period
                        ORDER BY revenue_month";

    $monthly_revenue_stmt = $conn->prepare($monthly_revenue_sql);
    $monthly_revenue_stmt->execute([$selected_year]);
    $monthly_revenues_raw = $monthly_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tạo mảng đầy đủ 12 tháng
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

    // Doanh thu theo sản phẩm trong năm được chọn
    $product_revenue_sql = "SELECT p.name, SUM(o.price * o.qty) as revenue, SUM(o.qty) as total_sold
                           FROM orders o 
                           JOIN products p ON o.product_id = p.id 
                           WHERE o.payment_status = 'complete'
                           AND YEAR(o.`date`) = ?
                           GROUP BY o.product_id 
                           ORDER BY revenue DESC";
    $product_revenue_stmt = $conn->prepare($product_revenue_sql);
    $product_revenue_stmt->execute([$selected_year]);
    $product_revenues = $product_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổng số đơn hàng
    $total_orders_sql = "SELECT COUNT(*) as total_orders FROM orders WHERE payment_status = 'complete'";
    $total_orders_stmt = $conn->query($total_revenue_sql);
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
    $monthly_revenues = [];
    $product_revenues = [];
    $total_orders = 0;
    $year_orders = 0;
}

// Chuẩn bị dữ liệu cho biểu đồ
$chart_labels = [];
$chart_data = [];

foreach ($monthly_revenues as $revenue) {
    $month_names = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                   'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
    $chart_labels[] = $month_names[$revenue['revenue_month']];
    $chart_data[] = $revenue['monthly_revenue'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="admin_style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Green Coffee Admin Panel - Báo Cáo Doanh Thu</title>
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
        <!-- Chọn năm -->
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
        </div>

        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Tổng Doanh Thu</h3>
                <div class="amount">$<?= number_format($total_revenue, 2) ?></div>
                <div class="subtext">Tất cả đơn hàng đã thanh toán</div>
            </div>

            <div class="stat-card yearly">
                <h3>Doanh Thu Năm <?= $selected_year ?></h3>
                <div class="amount">$<?= number_format($yearly_revenue, 2) ?></div>
                <div class="subtext">Tổng doanh thu trong năm</div>
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
            <?php if (!empty($monthly_revenues)): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            <?php else: ?>
                <div class="no-data">Chưa có dữ liệu doanh thu cho năm <?= $selected_year ?></div>
            <?php endif; ?>
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
                                <td><span class="revenue-badge">$<?= number_format($product['revenue'], 2) ?></span></td>
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
<?php if (!empty($monthly_revenues)): ?>
// Biểu đồ doanh thu
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Doanh Thu ($) - Năm <?= $selected_year ?>',
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
                        return `Doanh thu: $${context.parsed.y.toFixed(2)}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
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
<?php endif; ?>

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