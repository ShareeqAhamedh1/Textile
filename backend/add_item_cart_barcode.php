<?php
include 'conn.php';

$grm_id = $_SESSION['grm_ref'] ?? null;

if (!$grm_id) {
    echo json_encode(["statusCode" => 400, "message" => "Invalid session"]);
    exit;
}

$bcode = $_REQUEST['bcode'] ?? null;

// 🚨 Validate barcode input
if (!$bcode) {
    echo json_encode(["statusCode" => 400, "message" => "Barcode is required"]);
    exit;
}

// 🚨 Get product ID from barcode
$p_id = getDataBack($conn, 'tbl_product', 'barcode', $bcode, 'id');

if (!$p_id) {
    echo json_encode(["statusCode" => 400, "message" => "Product not found for barcode: $bcode"]);
    exit;
}

$qty = 1;
$currentStock = currentStockCount($conn, $p_id);

// 🚨 Stock Validations
if ($currentStock === 0) {
    echo json_encode(["statusCode" => 400, "message" => "Out of stock"]);
    exit;
}

if ($qty > $currentStock) {
    echo json_encode(["statusCode" => 400, "message" => "Only $currentStock items left in stock"]);
    exit;
}

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
