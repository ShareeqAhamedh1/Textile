<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $cp_description = $_POST['cp_description'];
    $cp_amount = $_POST['cp_amount'];

    if (!empty($customer_id) && !empty($cp_description) && !empty($cp_amount)) {
        $sql = "INSERT INTO tbl_customer_payments (c_id, cp_description, cp_amount)
                VALUES ('$customer_id', '$cp_description', '$cp_amount')";

        if (mysqli_query($conn, $sql)) {
            echo '<div class="alert alert-success">Payment added successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error adding payment.</div>';
        }
    } else {
        echo '<div class="alert alert-warning">All fields are required!</div>';
    }
}
?>
