<?php
include 'conn.php';

$vendor_id = mysqli_real_escape_string($conn, $_POST['vendor_id']);
$vendor_name = mysqli_real_escape_string($conn, $_POST['vendor_name']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$address = mysqli_real_escape_string($conn, $_POST['address']);

// Update vendor in the database
$sql = "UPDATE tbl_vendors SET vendor_name='$vendor_name', phone='$phone', address='$address' WHERE vendor_id='$vendor_id'";
if ($conn->query($sql)) {
    echo "<script>alert('Vendor updated successfully!'); window.location.href='../vendorlist.php';</script>";
} else {
    echo "<script>alert('Error updating vendor!'); window.history.back();</script>";
}
?>