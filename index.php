<?php
require_once 'config.php';

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    $result = $conn->query("SELECT * FROM products WHERE id = $product_id");
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'code' => $product['product_code']
            ];
        }
    }
}

// Update cart quantity
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
}

// Remove from cart
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
}

// Process payment
if (isset($_POST['process_payment'])) {
    $customer_name = $_POST['customer_name'] ?? 'Walk-in Customer';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    // Calculate totals
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $tax_rate = 0.18; // 18% GST
    $tax_amount = $subtotal * $tax_rate;
    $total_amount = $subtotal + $tax_amount;
    
    // Generate bill number
    $bill_no = generateBillNumber($conn);
    
    // Insert bill
    $stmt = $conn->prepare("INSERT INTO bills (bill_no, customer_name, customer_phone, subtotal, tax_amount, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddds", $bill_no, $customer_name, $customer_phone, $subtotal, $tax_amount, $total_amount, $payment_method);
    
    if ($stmt->execute()) {
        $bill_id = $stmt->insert_id;
        
        // Insert bill items and update stock
        foreach ($_SESSION['cart'] as $item) {
            $stmt2 = $conn->prepare("INSERT INTO bill_items (bill_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            $total_price = $item['price'] * $item['quantity'];
            $stmt2->bind_param("iisidd", $bill_id, $item['id'], $item['name'], $item['quantity'], $item['price'], $total_price);
            $stmt2->execute();
            $stmt2->close();
            
            // Update stock quantity
            $conn->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE id = {$item['id']}");
        }
        
        // Store bill ID for printing
        $_SESSION['last_bill_id'] = $bill_id;
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to print page
        header("Location: print_bill.php?bill_id=$bill_id");
        exit();
    }
    $stmt->close();
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Left Side: Product List -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Products</h5>
                </div>
                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <?php
                        $result = $conn->query("SELECT * FROM products ORDER BY stock_quantity ASC");
                        while ($product = $result->fetch_assoc()):
                            $low_stock = $product['stock_quantity'] <= $product['min_stock'];
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="product-card <?php echo $low_stock ? 'low-stock' : ''; ?> p-3" 
                                 onclick="addToCart(<?php echo $product['id']; ?>)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo $product['product_name']; ?></h6>
                                        <small class="text-muted"><?php echo $product['product_code']; ?></small>
                                        <div class="mt-2">
                                            <strong class="text-primary">₹<?php echo number_format($product['price'], 2); ?></strong>
                                        </div>
                                        <div class="mt-1">
                                            <small>Stock: 
                                                <span class="<?php echo $low_stock ? 'text-danger fw-bold' : 'text-success'; ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </small>
                                            <?php if($low_stock): ?>
                                            <span class="badge bg-danger ms-1">Low Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <form method="POST" class="mt-2" id="form-<?php echo $product['id']; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" 
                                               class="form-control" style="width: 70px;">
                                        <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Billing Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Billing Items</h5>
                </div>
                <div class="card-body">
                    <!-- Billing Items List -->
                    <div style="max-height: 40vh; overflow-y: auto;" class="mb-4">
                        <?php if (empty($_SESSION['cart'])): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                <p>No items in cart. Click on products to add.</p>
                            </div>
                        <?php else: ?>
                            <?php
                            $subtotal = 0;
                            foreach ($_SESSION['cart'] as $item):
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                            <div class="billing-item row align-items-center">
                                <div class="col-5">
                                    <strong><?php echo $item['name']; ?></strong><br>
                                    <small class="text-muted"><?php echo $item['code']; ?></small>
                                </div>
                                <div class="col-3">
                                    <div class="d-flex align-items-center">
                                        <form method="POST" class="d-flex">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="update_quantity" 
                                                    onclick="this.form.quantity.value--" 
                                                    class="quantity-btn">-</button>
                                            <input type="number" name="quantity" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" class="quantity-input mx-2" 
                                                   onchange="this.form.submit()">
                                            <button type="submit" name="update_quantity" 
                                                    onclick="this.form.quantity.value++" 
                                                    class="quantity-btn">+</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-3 text-end">
                                    ₹<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?><br>
                                    <strong>₹<?php echo number_format($item_total, 2); ?></strong>
                                </div>
                                <div class="col-1 text-end">
                                    <a href="?remove=<?php echo $item['id']; ?>" class="text-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Summary and Payment -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="summary-box">
                                <h6 class="mb-3">Customer Details</h6>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Customer Name</label>
                                        <input type="text" name="customer_name" class="form-control" 
                                               placeholder="Walk-in Customer">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="customer_phone" class="form-control" 
                                               placeholder="Optional">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method" class="form-select">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="upi">UPI</option>
                                            <option value="online">Online</option>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="process_payment" 
                                                class="btn btn-success btn-lg" 
                                                <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-credit-card me-2"></i>Process Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="summary-box">
                                <h6 class="mb-3">Bill Summary</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>GST (5%):</span>
                                    <span>₹<?php echo number_format($subtotal * 0.05, 2); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong class="text-success">₹<?php echo number_format($subtotal * 1.18, 2); ?></strong>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="?clear_cart" class="btn btn-outline-danger" 
                                       onclick="return confirm('Clear all items?')">
                                        <i class="fas fa-trash me-2"></i>Clear Cart
                                    </a>
                                    <?php if(isset($_SESSION['last_bill_id'])): ?>
                                    <a href="print_bill.php?bill_id=<?php echo $_SESSION['last_bill_id']; ?>" 
                                       target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-print me-2"></i>Print Last Bill
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addToCart(productId) {
    document.getElementById('form-' + productId).submit();
}

// Auto-refresh stock warnings every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include 'footer.php'; ?>