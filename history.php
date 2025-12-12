<?php
require_once 'config.php';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$query = "SELECT b.*, 
          (SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id) as item_count
          FROM bills b WHERE 1=1";

if ($search) {
    $query .= " AND (b.bill_no LIKE '%$search%' OR b.customer_name LIKE '%$search%' OR b.customer_phone LIKE '%$search%')";
}

if ($date_from) {
    $query .= " AND DATE(b.created_at) >= '$date_from'";
}

if ($date_to) {
    $query .= " AND DATE(b.created_at) <= '$date_to'";
}

$query .= " ORDER BY b.created_at DESC";

$result = $conn->query($query);
$total_bills = $result->num_rows;

// Calculate totals
$total_sales_result = $conn->query("SELECT SUM(total_amount) as total_sales FROM bills");
$total_sales = $total_sales_result->fetch_assoc()['total_sales'] ?? 0;

$today_sales_result = $conn->query("SELECT SUM(total_amount) as today_sales FROM bills WHERE DATE(created_at) = CURDATE()");
$today_sales = $today_sales_result->fetch_assoc()['today_sales'] ?? 0;
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <h6 class="text-muted">Total Bills</h6>
                <div class="stat-number"><?php echo $total_bills; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <h6 class="text-muted">Total Sales</h6>
                <div class="stat-number">₹<?php echo number_format($total_sales, 2); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <h6 class="text-muted">Today's Sales</h6>
                <div class="stat-number">₹<?php echo number_format($today_sales, 2); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <h6 class="text-muted">Average Bill</h6>
                <div class="stat-number">
                    <?php echo $total_bills > 0 ? '₹' . number_format($total_sales / $total_bills, 2) : '₹0.00'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-history me-2"></i>Billing History
            </h4>
            
            <form method="GET" class="row g-2">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search bill/customer..." value="<?php echo $search; ?>">
                </div>
                <div class="col-auto">
                    <input type="date" name="date_from" class="form-control form-control-sm" 
                           value="<?php echo $date_from; ?>">
                </div>
                <div class="col-auto">
                    <input type="date" name="date_to" class="form-control form-control-sm" 
                           value="<?php echo $date_to; ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="history.php" class="btn btn-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
        
        <div class="table-responsive history-table">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bill No</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($bill = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $bill['bill_no']; ?></strong></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($bill['created_at'])); ?></td>
                        <td>
                            <?php echo $bill['customer_name']; ?><br>
                            <small class="text-muted"><?php echo $bill['customer_phone']; ?></small>
                        </td>
                        <td><?php echo $bill['item_count']; ?> items</td>
                        <td>₹<?php echo number_format($bill['subtotal'], 2); ?></td>
                        <td>₹<?php echo number_format($bill['tax_amount'], 2); ?></td>
                        <td><strong>₹<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $bill['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo strtoupper($bill['payment_method']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="print_bill.php?bill_id=<?php echo $bill['id']; ?>" 
                                   target="_blank" class="btn btn-outline-primary" title="Print">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="view_bill.php?bill_id=<?php echo $bill['id']; ?>" 
                                   class="btn btn-outline-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="delete_bill.php?bill_id=<?php echo $bill['id']; ?>" 
                                   class="btn btn-outline-danger" 
                                   onclick="return confirm('Delete this bill?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($total_bills == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p>No bills found</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>