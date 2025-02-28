<?php
include '../backend/conn.php';
$grm_id = $_SESSION['grm_ref'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $discount_amount = $_POST['discount_amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 1;
    $action = $_POST['action'] ?? '';
    $status = ($action === "complete_bill") ? "1" : "0";

    if(isset($_SESSION['c_id'])){
      $cid =$_SESSION['c_id'];
    }
    else {
      $cid =0;
    }

    $sql ="UPDATE tbl_order_grm SET discount_price='$discount_amount',customer_id ='$cid',payment_type='$payment_method',order_st='$status' WHERE id='$grm_id'";
    $rs = $conn->query($sql);

    if ($rs > 0) {
      unset($_SESSION['grm_ref']);
      unset($_SESSION['c_id']);
        echo 200; // Success response
    } else {
        echo 500;
    }

}
?>
