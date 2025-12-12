<?php
require_once 'config.php';

// Get statistics
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$year_start = date('Y-01-01');

// Today's stats
$today_stats = $conn->query("SELECT 
    COUNT(*) as bills_today,
    SUM(total_amount) as sales_today,
    AVG(total_amount) as avg_today
    FROM bills WHERE DATE(created_at) = '$today'")->fetch_assoc();

// Monthly stats
$month_stats = $conn->query("SELECT 
    COUNT(*) as bills_month,
    SUM(total_amount) as sales_month,
    AVG(total_amount) as avg_month
    FROM bills WHERE created_at >= '$month_start'")->fetch_assoc();

// Yearly stats
$year_stats = $conn->query("SELECT 
    COUNT(*) as bills_year,
    SUM(total_amount) as sales_year,
    AVG(total_amount) as avg_year
    FROM bills WHERE created_at >= '$year_start'")->fetch_assoc();

// Top products
$top_products = $conn->query("
    SELECT p.product_name, SUM(bi.quantity) as total_sold, SUM(bi.total_price) as revenue
    FROM bill_items bi
    JOIN products p ON bi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");

// Low stock products count
$low_stock_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock")->fetch_assoc()['count'];

// Recent bills
$recent_bills = $conn->query("SELECT * FROM bills ORDER BY created_at DESC LIMIT 5");
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <h3 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h3>
    
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Today's Sales</h6>
                        <h4 class="text-success">₹<?php echo number_format($today_stats['sales_today'] ?? 0, 2); ?></h4>
                        <small><?php echo $today_stats['bills_today'] ?? 0; ?> bills today</small>
                    </div>
                    <div class="display-4 text-success">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Monthly Sales</h6>
                        <h4 class="text-primary">₹<?php echo number_format($month_stats['sales_month'] ?? 0, 2); ?></h4>
                        <small><?php echo $month_stats['bills_month'] ?? 0; ?> bills this month</small>
                    </div>
                    <div class="display-4 text-primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Yearly Sales</h6>
                        <h4 class="text-info">₹<?php echo number_format($year_stats['sales_year'] ?? 0, 2); ?></h4>
                        <small><?php echo $year_stats['bills_year'] ?? 0; ?> bills this year</small>
                    </div>
                    <div class="display-4 text-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Low Stock Alert</h6>
                        <h4 class="<?php echo $low_stock_count > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $low_stock_count; ?> products
                        </h4>
                        <small>Need restocking</small>
                    </div>
                    <div class="display-4 text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts and Tables -->
    <div class="row">
        <div class="col-md-6">
            <div class="dashboard-card">
                <h5 class="mb-4">
                    <i class="fas fa-star me-2"></i>Top Selling Products
                </h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $product['product_name']; ?></td>
                                <td><span class="badge bg-primary"><?php echo $product['total_sold']; ?></span></td>
                                <td>₹<?php echo number_format($product['revenue'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="dashboard-card">
                <h5 class="mb-4">
                    <i class="fas fa-clock me-2"></i>Recent Bills
                </h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($bill = $recent_bills->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $bill['bill_no']; ?></strong></td>
                                <td><?php echo $bill['customer_name']; ?></td>
                                <td><span class="badge bg-success">₹<?php echo number_format($bill['total_amount'], 2); ?></span></td>
                                <td><?php echo date('H:i', strtotime($bill['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="dashboard-card">
                <h5 class="mb-4">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <a href="index.php" class="btn btn-success btn-lg w-100 py-3">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                            New Bill
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="products.php" class="btn btn-primary btn-lg w-100 py-3">
                            <i class="fas fa-boxes fa-2x mb-2"></i><br>
                            Manage Products
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="history.php" class="btn btn-info btn-lg w-100 py-3">
                            <i class="fas fa-history fa-2x mb-2"></i><br>
                            View History
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="products.php" class="btn btn-warning btn-lg w-100 py-3">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                            Low Stock (<?php echo $low_stock_count; ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>