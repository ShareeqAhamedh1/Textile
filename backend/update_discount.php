<?php
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['order_id']);
    $discount = floatval($_POST['discount']);

    $sql = "UPDATE tbl_order SET discount = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $discount, $id);

    if ($stmt->execute()) {
        echo 200;
    } else {
        echo "Error updating discount";
    }
    $stmt->close();
}
?>
