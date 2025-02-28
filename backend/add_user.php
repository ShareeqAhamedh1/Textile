<?php
include 'conn.php';

$name = mysqli_real_escape_string($conn, $_REQUEST['name']);
$pass = mysqli_real_escape_string($conn, $_REQUEST['password']);



  $sqlDeleteAd= "INSERT INTO tbl_user (username,password) VALUES ('$name','$pass')";
  $rsDelAd = $conn->query($sqlDeleteAd);


  if ($rsDelAd > 0) {
    $_SESSION['suc_ad_del'] = true;
    header("location:../add_user.php");
    exit();
  }



 ?>
