<?php
include 'conn.php';

// Check authentication
if (!isset($_SESSION['u_id'])) {
    $_SESSION['error_cus'] = 'You must be logged in to add products.';
    header('Location: ../login.php');
    exit();
}

// Validate required fields
$required = ['category_id', 'name', 'quantity', 'unit_id', 'barcode', 
            'cost_price', 'price', 'status', 'vendor_id'];
$missing = array_diff($required, array_keys($_POST));

// if (!empty($missing)) {
//     $_SESSION['error_cus'] = 'Missing required fields: ' . implode(', ', $missing);
//     header('Location: ../addproduct.php');
//     exit();
// }

// Sanitize input data
$user_id = $_SESSION['u_id'];
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$name = htmlspecialchars(trim($_POST['name']));
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
$barcode = filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING);
$cost_price = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT);
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
$vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);
$hs_code = htmlspecialchars(trim($_POST['hs_code']));
// Validate numeric values
if ($quantity < 0 || $cost_price <= 0 || $price <= 0) {
    $_SESSION['error_cus'] = 'Invalid numeric values provided';
    header('Location: ../addproduct.php');
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Check barcode uniqueness
    $stmt = $conn->prepare("SELECT id FROM tbl_product WHERE barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Barcode already exists');
    }

    // Create stock GRM entry
    $stmt = $conn->prepare("INSERT INTO tbl_stock_grm (stock_ref, stock_hs_price, user_id) VALUES (?, ?, ?)");
    $stmt->bind_param("sdi", $hs_code, $cost_price, $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to create stock GRM entry');
    }
    $grm_ref = $conn->insert_id;

    // Insert main product
    $stmt = $conn->prepare("INSERT INTO tbl_product (
        category_id, name, unit, barcode, quantity, price,
        cost_price, status, vendor_id, grm_ref
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("isisiidiii", 
        $category_id, $name, $unit_id, $barcode, $quantity,
        $price, $cost_price, $status, $vendor_id, $grm_ref
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create product');
    }
    $product_id = $conn->insert_id;

    // Insert expiry date record
    $stmt = $conn->prepare("INSERT INTO tbl_expiry_date (product_id, quantity, barcode, user_id, grm_ref)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisii", $product_id, $quantity, $barcode, $user_id, $grm_ref);
    $stmt->execute();

    // Insert stock record
    $stmt = $conn->prepare("INSERT INTO tbl_stock (p_id, stock) VALUES (?, ?)");
    $stmt->bind_param("ii", $product_id, $quantity);
    $stmt->execute();

    // Insert quantity record
    $stmt = $conn->prepare("INSERT INTO tbl_quantity (p_id, quantity) VALUES (?, ?)");
    $stmt->bind_param("ii", $product_id, $quantity);
    $stmt->execute();

    $conn->commit();

    // Handle redirect
    if (isset($_POST['redir']) && $_POST['redir'] == 1) {
        $_SESSION['barcode_new'] = $barcode;
        header('Location: ../update_stock.php');
    } else {
        header('Location: ../addproduct.php');
    }
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_cus'] = $e->getMessage();
    header('Location: ../addproduct.php');
    exit();
}
?>