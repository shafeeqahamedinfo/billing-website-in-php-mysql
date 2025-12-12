<?php
require_once 'config.php';

if (!isset($_GET['bill_id'])) {
    die("Bill ID not specified");
}

$bill_id = $_GET['bill_id'];
$bill_result = $conn->query("SELECT b.*, c.* FROM bills b 
                           LEFT JOIN company_settings c ON 1=1 
                           WHERE b.id = $bill_id LIMIT 1");

if ($bill_result->num_rows == 0) {
    die("Bill not found");
}

$bill = $bill_result->fetch_assoc();
$items_result = $conn->query("SELECT * FROM bill_items WHERE bill_id = $bill_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $bill['bill_no']; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .company-details {
            font-size: 11px;
            color: #666;
        }
        .invoice-details {
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background: #f5f5f5;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="company-name"><?php echo $bill['company_name']; ?></div>
            <div class="company-details">
                <?php echo $bill['address']; ?> | Phone: <?php echo $bill['phone']; ?> | 
                Email: <?php echo $bill['email']; ?> | GST: <?php echo $bill['gst_no']; ?>
            </div>
        </div>
        
        <div class="invoice-details">
            <div style="float: left;">
                <strong>Bill To:</strong><br>
                <?php echo $bill['customer_name']; ?><br>
                <?php if($bill['customer_phone']): ?>
                    Phone: <?php echo $bill['customer_phone']; ?>
                <?php endif; ?>
            </div>
            <div style="float: right; text-align: right;">
                <strong>Invoice No:</strong> <?php echo $bill['bill_no']; ?><br>
                <strong>Date:</strong> <?php echo date('d-m-Y H:i', strtotime($bill['created_at'])); ?><br>
                <strong>Payment:</strong> <?php echo strtoupper($bill['payment_method']); ?>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                while($item = $items_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo $item['product_name']; ?></td>
                    <td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₹<?php echo number_format($item['total_price'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
                
                <tr>
                    <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                    <td><strong>₹<?php echo number_format($bill['subtotal'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>GST (18%):</strong></td>
                    <td><strong>₹<?php echo number_format($bill['tax_amount'], 2); ?></strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>Grand Total:</strong></td>
                    <td><strong>₹<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Software generated invoice. This is a computer generated receipt and does not require signature.</p>
            <p><?php echo $bill['company_name']; ?> | <?php echo $bill['address']; ?></p>
        </div>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Bill
        </button>
      <button onclick="window.location.href='index.php'" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> close
        </button>
    </div>
    
    <script>
        // Auto print
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>