<?php
session_start();

$host = 'sql107.infinityfree.com';
$username = 'if0_40628042';
$password = 'RfnFrGvMPGeHA';
$database = 'if0_40628042_db';


$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get company details
function getCompanyDetails($conn) {
    $result = $conn->query("SELECT * FROM company_settings LIMIT 1");
    return $result->fetch_assoc();
}

// Function to generate bill number
function generateBillNumber($conn, $prefix = 'INV') {
    $year = date('Y');
    $month = date('m');
    
    $result = $conn->query("SELECT MAX(id) as max_id FROM bills WHERE bill_no LIKE '$prefix-$year-$month-%'");
    $row = $result->fetch_assoc();
    
    $next_number = 1;
    if ($row['max_id']) {
        $last_bill = $conn->query("SELECT bill_no FROM bills WHERE id = " . $row['max_id'])->fetch_assoc();
        $last_number = intval(substr($last_bill['bill_no'], -4));
        $next_number = $last_number + 1;
    }
    
    return $prefix . '-' . $year . '-' . $month . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
}
?>