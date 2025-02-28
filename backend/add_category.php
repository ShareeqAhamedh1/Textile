<?php
include 'conn.php';


$name =$_REQUEST['name'];



$sqlAdd = "INSERT INTO tbl_category (name) VALUES ('$name')";
$rsAdd = $conn->query($sqlAdd);

if($rsAdd > 0){
  $_SESSION['suc_cus'] = true;
  header('location:../addcategory.php');
  exit();
}
else{
  $_SESSION['error_cus'] = true;
  header('location:../addcategory.php');
  exit();

}


 ?>
