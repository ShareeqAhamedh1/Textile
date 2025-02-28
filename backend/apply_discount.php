<?php
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = $_POST['vendor_id'];
    $discount_amount = floatval($_POST['discount_amount']);

    if ($discount_amount > 0) {
        // Insert the discount record
        $sqlInsert = "INSERT INTO tbl_vendor_discounts (vendor_id, discount_amount, discount_date) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sqlInsert);
        $stmt->bind_param("id", $vendor_id, $discount_amount);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["success" => true, "message" => "Discount applied successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid discount amount"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>
