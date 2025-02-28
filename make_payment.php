<?php
include './backend/conn.php';

// Check if customer_id is provided
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    die("Invalid request!");
}

$customer_id = intval($_GET['customer_id']);

// Fetch customer details with outstanding credit payments
$sql = "SELECT * FROM tbl_order_grm WHERE customer_id = ? AND payment_type = 3 AND pay_st != 2";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No pending credit payments for this customer.");
}

// If a valid credit record exists, update payment details
$update_sql = "UPDATE tbl_order_grm SET payment_type = ?, pay_st = 2 WHERE customer_id = ? AND payment_type = 3";
$update_stmt = $conn->prepare($update_sql);

// **Choose a new payment method (Cash by default)**
$new_payment_type = 0; // Change this if you want another default payment method

$update_stmt->bind_param("ii", $new_payment_type, $customer_id);

if ($update_stmt->execute()) {
    echo "<script>
            alert('Payment completed successfully.');
            window.location.href = 'customer_management.php'; // Redirect to main page
          </script>";
} else {
    echo "<script>alert('Error updating payment status.');</script>";
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>
