<?php
include 'conn.php';

$id = $_REQUEST['order_id'];

  $sqlDeleteAd= "DELETE FROM tbl_return_exchange WHERE or_id='$id'";
  $rsDelAd = $conn->query($sqlDeleteAd);
  if ($rsDelAd > 0) {
    echo 200;
  }
  else {
    echo 500;
  }



 ?>
