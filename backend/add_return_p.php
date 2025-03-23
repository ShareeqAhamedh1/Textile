<?php
include 'conn.php';

$vendor_id = $_POST['vendor_id'] ?? '';
$product_id = $_POST['product_id'] ?? '';
$qnty = $_POST['qnty'] ?? '';
$note = $_POST['note'] ?? '';

if ($vendor_id && $product_id && $qnty) {
    $stmt = $conn->prepare("INSERT INTO tbl_item_return (p_id, vendor_id, ret_qty, extra_note) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $product_id, $vendor_id, $qnty, $note);
    $stmt->execute();
    echo "success";
} else {
    http_response_code(400);
    echo "Invalid data";
}
?>
