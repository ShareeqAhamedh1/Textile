<?php
include 'conn.php';

$order_id = $_REQUEST['order_id'];

$sql ="DELETE FROM tbl_order WHERE id='$order_id'";
$rs=$conn->query($sql);

$sql ="DELETE FROM tbl_return_exchange WHERE or_id='$order_id'";
$rs=$conn->query($sql);

if($rs > 0){
  echo 200;
}
else {
  echo 500;
}
