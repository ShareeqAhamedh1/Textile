<?php
include 'conn.php';

$product_id = $_POST['id'];
$category_id = $_POST['category_id'];
$sub_cat_id = $_POST['sub_cat_id'];
$quantity = $_POST['quantity'];
$unit = $_POST['unit_id'];
$barcode = $_POST['barcode'];
$cprice = $_POST['hscode'] ?? null;
$price = $_POST['price'];
$status = $_POST['status'];
$vendor_id = $_POST['vendor_id'];
$st_description = $_POST['st_description'] ?? null;

if (empty($category_id) || empty($sub_cat_id) || empty($quantity) || empty($unit) || empty($price) || empty($status) || empty($vendor_id)) {
    $_SESSION['error_cus'] = 'Required fields are missing.';
    header('Location: ../edit_product.php?id=' . $product_id);
    exit();
}

// Extract hs_code details
$values = explode(',', $cprice);
$stock_hs_price = $values[0] ?? null;
$grm_ref = $values[1] ?? null;

// Update tbl_product
$sqlUpdate = "UPDATE tbl_product
              SET category_id = ?, sub_category_id = ?, unit = ?, barcode = ?, quantity = ?, price = ?, cost_price = ?, grm_ref = ?, status = ?, vendor_id = ?
              WHERE id = ?";

$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("iiisiddisii", $category_id, $sub_cat_id, $unit, $barcode, $quantity, $price, $stock_hs_price, $grm_ref, $status, $vendor_id, $product_id);

if ($stmtUpdate->execute()) {
    // Update tbl_stock
    $sqlStock = "INSERT INTO tbl_stock (p_id, stock) VALUES (?, ?)";
    $stmtStock = $conn->prepare($sqlStock);
    $stmtStock->bind_param("ii", $product_id, $quantity);
    $stmtStock->execute();

    // Update tbl_quantity
    $sqlQuantity = "INSERT INTO tbl_quantity (p_id, quantity) VALUES (?, ?)";
    $stmtQuantity = $conn->prepare($sqlQuantity);
    $stmtQuantity->bind_param("ii", $product_id, $quantity);
    $stmtQuantity->execute();

    // Check if record exists in tbl_expiry_date
    $sqlExpiryCheck = "SELECT id FROM tbl_expiry_date WHERE product_id = ? AND grm_ref = ?";
    $stmtExpiryCheck = $conn->prepare($sqlExpiryCheck);
    $stmtExpiryCheck->bind_param("ii", $product_id, $grm_ref);
    $stmtExpiryCheck->execute();
    $result = $stmtExpiryCheck->get_result();

    if ($result->num_rows > 0) {
        // If record exists, update it
        $sqlExpiryUpdate = "UPDATE tbl_expiry_date 
                            SET quantity = ?, barcode = ?, user_id = ?
                            WHERE product_id = ? AND grm_ref = ?";
        $stmtExpiryUpdate = $conn->prepare($sqlExpiryUpdate);
        $stmtExpiryUpdate->bind_param("iisii", $quantity, $barcode, $_SESSION['u_id'], $product_id, $grm_ref);
        $stmtExpiryUpdate->execute();
    } else {
        // If record doesn't exist, insert a new entry
        $sqlExpiryUpdate = "UPDATE tbl_expiry_date 
        SET quantity = ?, barcode = ?, user_id = ?
        WHERE product_id = ?"; 

$stmtExpiryUpdate = $conn->prepare($sqlExpiryUpdate);
$stmtExpiryUpdate->bind_param("iisi", $quantity, $barcode, $_SESSION['u_id'], $product_id);
$stmtExpiryUpdate->execute();

    }

    $_SESSION['success'] = 'Product updated successfully';
    header('Location: ../productlist.php');
    exit();
} else {
    $_SESSION['error_cus'] = true;
    header('Location: ../edit_product.php?id=' . $product_id);
    exit();
}
?>
