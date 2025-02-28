<?php
include 'conn.php';



$ref =mysqli_real_escape_string($conn,$_REQUEST['stock_ref']);
$stock_hs_price =$_REQUEST['stock_hs_price'];
$user_id = $_SESSION['u_id'];



$sqlAdd = "INSERT INTO tbl_stock_grm (stock_ref,user_id,stock_hs_price) VALUES ('$ref','$user_id','$stock_hs_price')";
$rsAdd = $conn->query($sqlAdd);

if ($rsAdd > 0) {
  $_SESSION['suc_ad_del'] = true;
  echo json_encode(array("statusCode"=>200));
  exit();
}



 ?>
