<?php
include 'conn.php';

$id = $_REQUEST['order_id'];

$sql ="SELECT * FROM tbl_order WHERE id='$id'";
$rs=$conn->query($sql);
if($rs->num_rows > 0){
  $row = $rs->fetch_assoc();

  $p_id=$row['product_id'];
  $grm_ref=$row['grm_ref'];
  $ret_or_ex_status =$_REQUEST['st'];

  $sql = "INSERT INTO tbl_return_exchange (p_id,grm_ref,ret_or_ex_st,or_id) VALUES ('$p_id','$grm_ref','$ret_or_ex_status','$id')";
  $rs = $conn->query($sql);
  if($rs ==1){
    echo 200;
  }
  else {
    echo 500;
  }
}
