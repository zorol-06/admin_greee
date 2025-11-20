<?php
include '../components/connection.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:login.php');
    exit;
}

// Lấy dữ liệu doanh thu
try {
    // Tổng doanh thu
    $total_revenue_sql = "SELECT SUM(price * qty) AS total_revenue FROM orders WHERE payment_status = 'complete'";
    $total_revenue_stmt = $conn->query($total_revenue_sql);
    $total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Doanh thu tháng này
    $current_month_sql = "SELECT SUM(price * qty) AS monthly_revenue 
                          FROM orders 
                          WHERE payment_status = 'complete' 
                          AND MONTH(`date`) = MONTH(CURRENT_DATE()) 
                          AND YEAR(`date`) = YEAR(CURRENT_DATE())";
    $current_month_stmt = $conn->query($current_month_sql);
    $monthly_revenue = $current_month_stmt->fetch(PDO::FETCH_ASSOC)['monthly_revenue'] ?? 0;

    // Doanh thu 12 tháng gần nhất
    $monthly_revenue_sql = "SELECT 
                            YEAR(`date`) as revenue_year,
                            MONTH(`date`) as revenue_month,
                            DATE_FORMAT(`date`, '%Y-%m') as revenue_period,
                            SUM(price * qty) as monthly_revenue
                        FROM orders 
                        WHERE payment_status = 'complete' 
                        AND `date` >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                        GROUP BY revenue_year, revenue_month
                        ORDER BY revenue_year, revenue_month";

    $monthly_revenue_stmt = $conn->query($monthly_revenue_sql);
    $monthly_revenues = $monthly_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Doanh thu theo sản phẩm
    $product_revenue_sql = "SELECT p.name, SUM(o.price * o.qty) as revenue, SUM(o.qty) as total_sold
                           FROM orders o 
                           JOIN products p ON o.product_id = p.id 
                           WHERE o.payment_status = 'complete'
                           GROUP BY o.product_id 
                           ORDER BY revenue DESC";
    $product_revenue_stmt = $conn->query($product_revenue_sql);
    $product_revenues = $product_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổng số đơn hàng
    $total_orders_sql = "SELECT COUNT(*) as total_orders FROM orders WHERE payment_status = 'complete'";
    $total_orders_stmt = $conn->query($total_orders_sql);
    $total_orders = $total_orders_stmt->fetch(PDO::FETCH_ASSOC)['total_orders'] ?? 0;

    // Đơn hàng tháng này
    $month_orders_sql = "SELECT COUNT(*) as month_orders 
                        FROM orders 
                        WHERE payment_status = 'complete' 
                        AND MONTH(`date`) = MONTH(CURRENT_DATE()) 
                        AND YEAR(`date`) = YEAR(CURRENT_DATE())";
    $month_orders_stmt = $conn->query($month_orders_sql);
    $month_orders = $month_orders_stmt->fetch(PDO::FETCH_ASSOC)['month_orders'] ?? 0;

} catch(Exception $e) {
    error_log('Revenue Query Error: ' . $e->getMessage());
    $total_revenue = 0;
    $monthly_revenue = 0;
    $monthly_revenues = [];
    $product_revenues = [];
    $total_orders = 0;
    $month_orders = 0;
}

// Chuẩn bị dữ liệu cho biểu đồ
$chart_labels = [];
$chart_data = [];

if (!empty($monthly_revenues)) {
    foreach ($monthly_revenues as $revenue) {
        $chart_labels[] = "Tháng " . $revenue['revenue_month'] . "/" . substr($revenue['revenue_year'], 2);
        $chart_data[] = $revenue['monthly_revenue'];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Green Coffee Admin Panel - Báo Cáo Doanh Thu</title>
    <style>
        .revenue-dashboard {
            padding: 20px;
            background: #f5f5f5;
            min-height: calc(100vh - 200px);
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

        .stat-card.monthly {
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
        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Tổng Doanh Thu</h3>
                <div class="amount">$<?= number_format($total_revenue, 2) ?></div>
                <div class="subtext">Tất cả đơn hàng đã thanh toán</div>
            </div>

            <div class="stat-card monthly">
                <h3>Doanh Thu Tháng Này</h3>
                <div class="amount">$<?= number_format($monthly_revenue, 2) ?></div>
                <div class="subtext">Tháng <?= date('m/Y') ?></div>
            </div>

            <div class="stat-card orders">
                <h3>Tổng Đơn Hàng</h3>
                <div class="amount"><?= number_format($total_orders) ?></div>
                <div class="subtext"><?= number_format($month_orders) ?> đơn tháng này</div>
            </div>
        </div>

        <!-- Biểu đồ doanh thu -->
        <div class="charts-section">
            <h2>📈 Doanh Thu Theo Tháng</h2>
            <?php if (!empty($monthly_revenues)): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            <?php else: ?>
                <div class="no-data">Chưa có dữ liệu doanh thu theo tháng</div>
            <?php endif; ?>
        </div>

        <!-- Doanh thu theo sản phẩm -->
        <div class="products-section">
            <h2>📦 Doanh Thu Theo Sản Phẩm</h2>
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
                <div class="no-data">Chưa có dữ liệu doanh thu theo sản phẩm</div>
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
            label: 'Doanh Thu ($)',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(102, 126, 234, 0.7)',
            borderColor: 'rgba(102, 126, 234, 1)',
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