<?php
include 'conn.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    if (!isset($_POST['vendor_id']) || empty($_POST['vendor_id'])) {
        throw new Exception('Vendor selection is required');
    }
    if (!isset($_POST['purchase_id']) || empty($_POST['purchase_id'])) {
        throw new Exception('Purchase ID is required');
    }
    if (!isset($_POST['items']) || empty($_POST['items'])) {
        throw new Exception('No items in purchase');
    }

    $vendorId = $_POST['vendor_id'];
    $purchaseId = $_POST['purchase_id'];
    $items = json_decode($_POST['items'], true);

    if (!is_array($items) || count($items) === 0) {
        throw new Exception('Invalid items data');
    }

    $conn->begin_transaction();

    // Prepare statements for updating item quantities and expiry dates
    $updateItemStmt = $conn->prepare("UPDATE tbl_purchase_items 
                                      SET quantity = ? 
                                      WHERE purchase_id = ? AND product_id = ?");
    
    $updateProductStockStmt = $conn->prepare("UPDATE tbl_product 
                                              SET quantity = quantity + ? 
                                              WHERE id = ?");

    // Prepare expiry update statement (to adjust quantity in tbl_expiry_date)
    $updateExpiryStmt = $conn->prepare("UPDATE tbl_expiry_date 
                                        SET quantity = ? 
                                        WHERE product_id = ? AND vendor_id = ? AND purchase_id= ?");

    foreach ($items as $item) {
        // Update purchase item quantities
        $updateItemStmt->bind_param("iii", $item['quantity'], $purchaseId, $item['id']);
        $updateItemStmt->execute();

        // Update product stock in tbl_product
        $updateProductStockStmt->bind_param("ii", $item['quantity'], $item['id']);
        $updateProductStockStmt->execute();

        // Update the expiry date quantities in tbl_expiry_date
        $updateExpiryStmt->bind_param("iiii", $item['quantity'], $item['id'], $vendorId,$purchaseId);
        $updateExpiryStmt->execute();
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Purchase updated successfully';
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
} finally {
    if ($conn) $conn->close();
}

echo json_encode($response);
