<?php
include 'conn.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    if (!isset($_POST['vendor_id']) || empty($_POST['vendor_id'])) {
        throw new Exception('Vendor selection is required');
    }
    if (!isset($_POST['purchase_date']) || empty($_POST['purchase_date'])) {
        throw new Exception('Purchase date is required');
    }
    if (!isset($_POST['items']) || empty($_POST['items'])) {
        throw new Exception('No items in purchase');
    }

    $vendorId = $_POST['vendor_id'];
    $purchaseDate = date('Y-m-d', strtotime($_POST['purchase_date']));
    $items = json_decode($_POST['items'], true);
    $amountPaid = $_POST['amount_paid'] ?? 0;
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';

    if (!is_array($items) || count($items) === 0) {
        throw new Exception('Invalid items data');
    }

    $conn->begin_transaction();

    // Calculate total amount
    $totalAmount = array_reduce($items, function($sum, $item) {
        return $sum + ($item['price'] * $item['quantity']);
    }, 0);

    // Insert into tbl_purchases
    $stmt = $conn->prepare("INSERT INTO tbl_purchases 
                          (vendor_id, purchase_date, total_amount, amount_paid) 
                          VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isdd", $vendorId, $purchaseDate, $totalAmount, $amountPaid);
    $stmt->execute();
    $purchaseId = $conn->insert_id;
    $stmt->close();

    // Prepare statements for inserting into related tables
    $purchaseItemStmt = $conn->prepare("INSERT INTO tbl_purchase_items 
                          (purchase_id, product_id, quantity, unit_price, grm_ref) 
                          VALUES (?, ?, ?, ?, ?)");

    $expiryStmt = $conn->prepare("INSERT INTO tbl_expiry_date 
                          (product_id, quantity, barcode, grm_ref,vendor_id,purchase_Id) 
                          VALUES (?, ?, ?, ?,?,?)");

    $updateStmt = $conn->prepare("UPDATE tbl_product 
                                     SET quantity = quantity + ? 
                                     WHERE id = ?");

    foreach ($items as $item) {
        // Fetch barcode and grm_ref from tbl_product
        $productStmt = $conn->prepare("SELECT barcode, grm_ref FROM tbl_product WHERE id = ?");
        $productStmt->bind_param("i", $item['id']);
        $productStmt->execute();
        $productStmt->bind_result($barcode, $grmRef);
        $productStmt->fetch();
        $productStmt->close();

        if (!$grmRef) $grmRef = "N/A"; // Handle missing grm_ref
        if (!$barcode) throw new Exception("Product barcode not found for ID: " . $item['id']);

        // Insert into purchase items (tbl_purchase_items)
        $purchaseItemStmt->bind_param("iiidi", 
            $purchaseId, 
            $item['id'], 
            $item['quantity'], 
            $item['price'],
            $grmRef 
        );
        $purchaseItemStmt->execute();

        // Insert into expiry date (tbl_expiry_date)
        $expiryStmt->bind_param("iisiii", 
            $item['id'], 
            $item['quantity'], 
            $barcode,  
            $grmRef ,
            $vendorId,
            $purchaseId  
        );
        $expiryStmt->execute();

        // Update product stock
        $updateStmt->bind_param("ii", $item['quantity'], $item['id']);
        $updateStmt->execute();
    }

    // Insert into tbl_purchase_payments if amountPaid > 0
    if ($amountPaid > 0) {
        $paymentStmt = $conn->prepare("INSERT INTO tbl_purchase_payments 
                                       (purchase_id, payment_date, amount, payment_method) 
                                       VALUES (?, ?, ?, ?)");
        $paymentStmt->bind_param("isds", $purchaseId, $purchaseDate, $amountPaid, $paymentMethod);
        $paymentStmt->execute();
        $paymentStmt->close();
    }

    // Close statements
    $purchaseItemStmt->close();
    $expiryStmt->close();
    $updateStmt->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Purchase recorded successfully';
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
} finally {
    if ($conn) $conn->close();
}

echo json_encode($response);
