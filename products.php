<?php
require_once 'config.php';

// Add new product
if (isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $product_code = $_POST['product_code'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $min_stock = $_POST['min_stock'];
    
    $stmt = $conn->prepare("INSERT INTO products (product_name, product_code, description, price, stock_quantity, min_stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdii", $product_name, $product_code, $description, $price, $stock_quantity, $min_stock);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['message'] = "Product added successfully!";
    header("Location: products.php");
    exit();
}

// Update product
if (isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_code = $_POST['product_code'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $min_stock = $_POST['min_stock'];
    
    $stmt = $conn->prepare("UPDATE products SET product_name=?, product_code=?, description=?, price=?, stock_quantity=?, min_stock=? WHERE id=?");
    $stmt->bind_param("sssiiii", $product_name, $product_code, $description, $price, $stock_quantity, $min_stock, $id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['message'] = "Product updated successfully!";
    header("Location: products.php");
    exit();
}

// Delete product
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    $_SESSION['message'] = "Product deleted successfully!";
    header("Location: products.php");
    exit();
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="dashboard-card">
                <h4 class="mb-4">
                    <i class="fas fa-plus-circle me-2"></i><?php echo isset($_GET['edit']) ? 'Edit' : 'Add'; ?> Product
                </h4>
                
                <?php
                $edit_product = null;
                if (isset($_GET['edit'])) {
                    $id = $_GET['edit'];
                    $result = $conn->query("SELECT * FROM products WHERE id = $id");
                    $edit_product = $result->fetch_assoc();
                }
                ?>
                
                <form method="POST">
                    <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="product_name" class="form-control" 
                               value="<?php echo $edit_product['product_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Code *</label>
                        <input type="text" name="product_code" class="form-control" 
                               value="<?php echo $edit_product['product_code'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo $edit_product['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₹) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" 
                                   value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Stock *</label>
                            <input type="number" name="stock_quantity" class="form-control" 
                                   value="<?php echo $edit_product['stock_quantity'] ?? 0; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Minimum Stock Level *</label>
                        <input type="number" name="min_stock" class="form-control" 
                               value="<?php echo $edit_product['min_stock'] ?? 10; ?>" required>
                        <small class="text-muted">System will show warning when stock reaches this level</small>
                    </div>
                    
                    <div class="d-grid">
                        <?php if ($edit_product): ?>
                            <button type="submit" name="update_product" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Product
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary mt-2">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_product" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="card shadow-sm border-0 mb-4" style="background:#f9f9f9;">
    <div class="card-header bg-light border-0 d-flex align-items-center">
        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
        <h5 class="mb-0 text-dark">Low Stock Products</h5>
    </div>

    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
        <?php
        $result = $conn->query("SELECT * FROM products WHERE stock_quantity <= min_stock ORDER BY stock_quantity ASC");
        if ($result->num_rows > 0):
            while($product = $result->fetch_assoc()):
        ?>
            <div class="p-2 mb-2 rounded d-flex justify-content-between align-items-center"
                 style="background:#ffffff; border:1px solid #e5e5e5;">
                <span class="fw-semibold text-dark">
                    <?php echo $product['product_name']; ?>
                </span>
                <span class="badge bg-warning text-dark">
                    Stock: <?php echo $product['stock_quantity']; ?>
                </span>
            </div>
        <?php endwhile; else: ?>
            <p class="text-muted text-center py-3">No low stock products</p>
        <?php endif; ?>
    </div>
</div>

        </div>
        
        <div class="col-md-8">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <i class="fas fa-boxes me-2"></i>All Products
                    </h4>
                    <div>
                        <span class="badge bg-primary">Total: 
                            <?php echo $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Min Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM products ORDER BY product_name");
                            while ($product = $result->fetch_assoc()):
                                $low_stock = $product['stock_quantity'] <= $product['min_stock'];
                            ?>
                            <tr>
                                <td><strong><?php echo $product['product_code']; ?></strong></td>
                                <td>
                                    <div><?php echo $product['product_name']; ?></div>
                                    <small class="text-muted"><?php echo substr($product['description'], 0, 50); ?>...</small>
                                </td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $low_stock ? 'text-danger fw-bold' : 'text-success'; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['min_stock']; ?></td>
                                <td>
                                    <?php if($low_stock): ?>
                                        <span class="badge bg-danger">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
