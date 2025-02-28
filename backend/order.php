<?php
include './conn.php';

$type = mysqli_real_escape_string($conn, $_REQUEST['button']);
$ids = $_REQUEST['prod_id'];
$order_date = mysqli_real_escape_string($conn, $_REQUEST['order_date']);
$customer_id = mysqli_real_escape_string($conn, $_REQUEST['customer_id']);
$pay_st = mysqli_real_escape_string($conn, $_REQUEST['pay_st']);
// $delivery_charge = isset($_REQUEST['delivery_charge']) ? mysqli_real_escape_string($conn, $_REQUEST['delivery_charge']) : 0;
$d_method = isset($_REQUEST['d_method']) ? mysqli_real_escape_string($conn, $_REQUEST['d_method']) : "";
$store_id = isset($_REQUEST['store_id']) ? mysqli_real_escape_string($conn, $_REQUEST['store_id']) : "";
$del_ref = isset($_REQUEST['del_ref']) ? mysqli_real_escape_string($conn, $_REQUEST['del_ref']) : "";
$pickup = isset($_REQUEST['pickup']) ? mysqli_real_escape_string($conn, $_REQUEST['pickup']) : "";

// Generating order reference number
$sqlTell = "SELECT * FROM tbl_order_grm WHERE order_date='$order_date'";
$rsTell = $conn->query($sqlTell);
$order_ref = $order_date . "-" . ($rsTell->num_rows + 1);

if (!$ids) {
    header("location:../pos.php?err");
    exit();
}

// Insert into tbl_order_grm
$sqlAddCustomer = "INSERT INTO tbl_order_grm 
(order_ref, order_date, payment_type, customer_id,  del_ref, pay_st) 
VALUES 
('$order_ref', '$order_date', '$type',   '$customer_id',  '$del_ref', '$pay_st')";

$rsAddCustomer = $conn->query($sqlAddCustomer);

if (!$rsAddCustomer) {
    die("Error: " . $conn->error);
}

$order_id = $conn->insert_id;

// Insert each product into tbl_order
foreach ($ids as $id) {
    $quantity = mysqli_real_escape_string($conn, $_REQUEST["quantity$id"]);
    $discount = mysqli_real_escape_string($conn, $_REQUEST["discount$id"]);
    $discount_type = mysqli_real_escape_string($conn, $_REQUEST["discount_type$id"]);
    $m_price = mysqli_real_escape_string($conn, $_REQUEST["m_price$id"]);

    $discount_type = ($discount_type == "percentage") ? "p" : "a";

    $sqlOrder = "INSERT INTO tbl_order 
    (product_id, quantity, customer_id, grm_ref, discount, discount_type, bill_date, m_price) 
    VALUES 
    ('$id', '$quantity', '$customer_id', '$order_id', '$discount', '$discount_type', '$order_date', '$m_price')";

    if (!$conn->query($sqlOrder)) {
        die("Error: " . $conn->error);
    }
}

// Clear session and redirect
unset($_SESSION['order_ref']);
unset($_SESSION['order_date']);

header("location:../pos_grm.php");
exit();
?>
