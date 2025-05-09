<?php
include 'conn.php';



$grm_id = $_SESSION['grm_ref'] ?? null;

if (!$grm_id) {
    echo json_encode(["statusCode" => 400, "message" => "Invalid session"]);
    exit;
}

$p_id = $_REQUEST['p_id'] ?? null;
$qty = $_REQUEST['qty'] ?? null;

// Ensure p_id and qty are valid numbers
if (!is_numeric($p_id)) {
    echo json_encode(["statusCode" => 400, "message" => "Invalid product ID"]);
    exit;
}

$currentStock = currentStockCount($conn, $p_id);


// ✅ Insert order if stock is available
$sql = "INSERT INTO tbl_order (product_id, quantity, grm_ref) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $p_id, $qty, $grm_id);

if ($stmt->execute()) {
    echo json_encode(["statusCode" => 200, "message" => "Item added successfully"]);
} else {
    echo json_encode(["statusCode" => 500, "message" => "Database error"]);
}

$stmt->close();
$conn->close();
?>
