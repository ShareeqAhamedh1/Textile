<?php
include 'conn.php';

$id = $_REQUEST['v_id'];

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // First, delete dependent records in tbl_purchases
    $sqlDeletePurchases = "DELETE FROM tbl_purchases WHERE vendor_id = '$id'";
    $conn->query($sqlDeletePurchases);

    // Then, delete the vendor from tbl_vendors
    $sqlDeleteVendor = "DELETE FROM tbl_vendors WHERE vendor_id = '$id'";
    $rsDelVendor = $conn->query($sqlDeleteVendor);

    // Check if vendor deletion was successful
    if ($rsDelVendor) {
        // Commit the transaction
        $conn->commit();
        $_SESSION['suc_ad_del'] = true;
        echo json_encode(array("statusCode" => 200));
    } else {
        // Rollback the transaction if vendor deletion failed
        $conn->rollback();
        echo json_encode(array("statusCode" => 500, "message" => "Failed to delete vendor"));
    }
} catch (Exception $e) {
    // Rollback the transaction in case of any error
    $conn->rollback();
    echo json_encode(array("statusCode" => 500, "message" => "Error: " . $e->getMessage()));
}

exit();
?>
