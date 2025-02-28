<?php

include './conn.php';

$user_id = mysqli_real_escape_string($conn, $_SESSION['u_id']);
$product_id = mysqli_real_escape_string($conn, $_REQUEST['product_id']);
$quantity = mysqli_real_escape_string($conn, $_REQUEST['quantity']);


// $barcode = mysqli_real_escape_string($conn, $_REQUEST['barcode']);
// $quantity = mysqli_real_escape_string($conn, $_REQUEST['quantity']);
// $note = mysqli_real_escape_string($conn, $_REQUEST['note']);
// $grm_id = mysqli_real_escape_string($conn, $_REQUEST['id']);


$sql = "SELECT * FROM tbl_product WHERE id='$product_id' ";
$rs = $conn->query($sql);
if($rs->num_rows >0){
  $row = $rs->fetch_assoc();
  $p_id = $row['id'];
  $grm_ref=$row['grm_ref'];
  $barcode=$row['barcode'];

$sqlAddCustomer = "INSERT INTO tbl_expiry_date (product_id,quantity,barcode, grm_ref, user_id)
 VALUES('$p_id','$quantity','$barcode','$grm_ref','$user_id')";


$rsAddCustomer = $conn->query($sqlAddCustomer);

if($rsAddCustomer > 0){
  $_SESSION['suc_cus_edited'] = true;
  $_SESSION['barcode'] = $barcode;
  // $_SESSION['shipping_type'] = $shipping_type;

  echo json_encode(array("statusCode"=>200));
  exit();
}else{
  $_SESSION['error_cus_edited'] = true;
  echo json_encode(array("statusCode"=>300));
  exit();
}


}else{
    $_SESSION['invalid_product'] = true;
    header("location:../productlist.php");
    exit();
  }
