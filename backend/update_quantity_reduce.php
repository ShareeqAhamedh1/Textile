<?php

include './conn.php';

$user_id = mysqli_real_escape_string($conn, $_SESSION['u_id']);
$product_id = mysqli_real_escape_string($conn, $_REQUEST['product_id']);
$quantity_to_reduce = mysqli_real_escape_string($conn, $_REQUEST['quantity']); // Quantity to reduce

// Fetch the current stock of the product
$sql = "SELECT * FROM tbl_product WHERE id='$product_id' ";
$rs = $conn->query($sql);
if ($rs->num_rows > 0) {
    $row = $rs->fetch_assoc();
    $p_id = $row['id'];
    $grm_ref = $row['grm_ref'];
    $barcode = $row['barcode'];

    // Insert a new record with a negative quantity to indicate a reduction
    $sqlAddReduction = "INSERT INTO tbl_expiry_date (product_id, quantity, barcode, grm_ref, user_id) 
                        VALUES ('$p_id', -$quantity_to_reduce, '$barcode', '$grm_ref', '$user_id')";

    $rsAddReduction = $conn->query($sqlAddReduction);

    if ($rsAddReduction) {
        echo json_encode(array("statusCode" => 200, "message" => "Quantity reduced successfully"));
        exit();
    } else {
        echo json_encode(array("statusCode" => 300, "message" => "Error reducing quantity"));
        exit();
    }
} else {
    $_SESSION['invalid_product'] = true;
    header("location:../productlist.php");
    exit();
}
