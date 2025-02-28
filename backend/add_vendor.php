<?php
include 'conn.php';

// Get form data from the request
$name = $_REQUEST['name'];
$contact = $_REQUEST['contact'];
$address = $_REQUEST['address'];


// Insert the expense into the database
$sqlAdd = "INSERT INTO tbl_vendors (vendor_name, phone, address) 
           VALUES ('$name', '$contact', '$address')";
$rsAdd = $conn->query($sqlAdd);

// Check if the insertion was successful
if ($rsAdd > 0) {
    $_SESSION['suc_expense'] = true;
    header('location:../addproduct.php');
    exit();
} else {
    $_SESSION['error_expense'] = true;
    header('location:../addproduct.php');
    exit();
}
?>
