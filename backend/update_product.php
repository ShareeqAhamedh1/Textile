<?php

include 'conn.php';

// Check authentication
if (!isset($_SESSION['u_id'])) {
    $_SESSION['error_cus'] = 'You must be logged in to edit products.';
    header('Location: ../login.php');
    exit();
}

// Validate product ID
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$product_id || $product_id < 1) {
    $_SESSION['error_cus'] = 'Invalid product ID';
    header('Location: ../productlist.php');
    exit();
}

// Validate required fields
$required = ['category_id', 'name', 'unit_id', 'barcode',
            'cost_price', 'price', 'status', 'vendor_id', 'hs_code'];
$missing = array_diff($required, array_keys($_POST));

if (!empty($missing)) {
    $_SESSION['error_cus'] = 'Missing required fields: ' . implode(', ', $missing);
    header("Location: ../editproduct.php?id=$product_id");
    exit();
}

// Sanitize input data
$user_id = $_SESSION['u_id'];
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$name = htmlspecialchars(trim($_POST['name']));
$unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
$barcode = filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING);
$cost_price = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT);
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
$vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);
$hs_code = filter_input(INPUT_POST, 'hs_code', FILTER_SANITIZE_STRING);

// Validate numeric values
if ($cost_price <= 0 || $price <= 0) {
    $_SESSION['error_cus'] = 'Invalid numeric values provided';
    header("Location: ../editproduct.php?id=$product_id");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Verify product exists and get current data
    $stmt = $conn->prepare("SELECT grm_ref, barcode FROM tbl_product WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Product not found');
    }
    
    $product = $result->fetch_assoc();
    
    // Verify barcode hasn't been tampered with
    if ($product['barcode'] !== $barcode) {
        throw new Exception('Invalid product modification attempt');
    }

    // Update stock GRM entry
    $stmt = $conn->prepare("UPDATE tbl_stock_grm 
                          SET stock_ref = ?, stock_hs_price = ?
                          WHERE id = ?");
    $stmt->bind_param("sdi", $hs_code, $cost_price, $product['grm_ref']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update stock GRM entry');
    }

    // Update main product
    $stmt = $conn->prepare("UPDATE tbl_product SET
        category_id = ?, name = ?, unit = ?, price = ?,
        cost_price = ?, status = ?, vendor_id = ?
        WHERE id = ?");
    
    $stmt->bind_param("isiidiii", 
        $category_id, $name, $unit_id,
        $price, $cost_price, $status, $vendor_id, $product_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product');
    }

    // Commit transaction
    $conn->commit();

    // Handle redirect
    if (isset($_POST['redir']) && $_POST['redir'] == 1) {
        header('Location: ../update_stock.php');
    } else {
        header('Location: ../productlist.php');
    }
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_cus'] = $e->getMessage();
    header("Location: ../editproduct.php?id=$product_id");
    exit();
}
?>
