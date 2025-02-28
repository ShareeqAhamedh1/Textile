<?php

include 'conn.php';

$u_id = $_REQUEST['u_id'];
$username = $_REQUEST['u_name'];
$password = $_REQUEST['u_pass'];

$sqlEditUser = "UPDATE tbl_user SET username='$username',
                                    password='$password',
                                     WHERE user_id='$u_id'
                                    ";
$rsEditUser = $conn->query($sqlEditUser);

if($rsEditUser > 0){
    header('location:../add_user.php');
    exit();
}
else{
    header('location:../add_user.php');
    exit();
}