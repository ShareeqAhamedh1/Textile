<?php
session_start();
$_SESSION['grm_ref'] = $_REQUEST['grm_id'];

header('location:../pos.php');
exit();

 ?>
