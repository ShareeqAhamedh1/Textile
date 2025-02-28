<?php
include 'conn.php';

$id = $_REQUEST['prod_id'];

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // First, delete the dependent rows in tbl_purchase_items
    $sqlDeleteItems = "DELETE FROM tbl_purchase_items WHERE product_id = '$id'";
    $conn->query($sqlDeleteItems);

    // Then, delete the product from tbl_product
    $sqlDeleteProduct = "DELETE FROM tbl_product WHERE id = '$id'";
    $rsDelAd = $conn->query($sqlDeleteProduct);

    // Check if the product deletion was successful
    if ($rsDelAd) {
        // Commit the transaction
        $conn->commit();
        $_SESSION['suc_ad_del'] = true;
        echo json_encode(array("statusCode" => 200));
    } else {
        // Rollback the transaction if product deletion failed
        $conn->rollback();
        echo json_encode(array("statusCode" => 500, "message" => "Failed to delete product"));
    }
} catch (Exception $e) {
    // Rollback the transaction in case of any error
    $conn->rollback();
    echo json_encode(array("statusCode" => 500, "message" => "Error: " . $e->getMessage()));
}
exit();
?>
