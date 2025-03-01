<?php
include 'conn.php';

$order_id = $_REQUEST['order_id'];
$qty =$_REQUEST['qty'];

if($qty <= 0){
  exit();
}

$sql ="UPDATE tbl_order SET quantity='$qty' WHERE id='$order_id'";
$rs=$conn->query($sql);

if($rs > 0){
  echo 200;
}
else {
  echo 500;
}
