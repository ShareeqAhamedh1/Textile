<?php

include '../backend/conn.php';

if (isset($_POST['customer_id'])) {
    $customer_id = $_POST['customer_id'];
    $_SESSION['c_id'] = $_POST['customer_id'];

    // Get customer phone number
    $sql = "SELECT c_phone FROM tbl_customer WHERE c_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();

    $phone = $customer['c_phone'];

    // Get outstanding balance
    $credit = 0;
    $customerPaid = 0;

    // Get total payments made
    $sqlPaid = "SELECT SUM(cp_amount) as total_paid FROM tbl_customer_payments WHERE c_id = ?";
    $stmt = $conn->prepare($sqlPaid);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $customerPaid = $row['total_paid'] ?? 0;
    }
    $stmt->close();

    // Get total credit
    $sqlCredit = "SELECT og.id, og.discount_price
                  FROM tbl_order_grm og
                  WHERE og.customer_id = ? AND og.payment_type = 3";
    $stmt = $conn->prepare($sqlCredit);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($order = $result->fetch_assoc()) {
        $order_id = $order['id'];
        $order_total = 0;

        $sqlOrder = "SELECT o.quantity, o.discount, p.price
                     FROM tbl_order o
                     JOIN tbl_product p ON o.product_id = p.id
                     WHERE o.grm_ref = ?";
        $stmtOrder = $conn->prepare($sqlOrder);
        $stmtOrder->bind_param("i", $order_id);
        $stmtOrder->execute();
        $resultOrder = $stmtOrder->get_result();

        while ($item = $resultOrder->fetch_assoc()) {
            $line_total = ($item['quantity'] * $item['price']) - $item['discount'];
            $order_total += $line_total;
        }
        $stmtOrder->close();

        $order_total -= $order['discount_price'];
        $credit += $order_total;
    }
    $stmt->close();

    $outstandingBalance = $credit - $customerPaid;

    echo json_encode([
        "status" => "success",
        "phone" => $phone,
        "balance" => $outstandingBalance
    ]);
} else {
    echo json_encode(["status" => "error"]);
}
?>
