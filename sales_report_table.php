<?php
include 'backend/conn.php';
$p_id = $_REQUEST['p_id'];

// Fetch all related GRM references for this product
$order_ref_pos = [];
$sql_pos = "SELECT DISTINCT grm_ref FROM tbl_order WHERE product_id='$p_id'";
$rs_pos = $conn->query($sql_pos);
while ($row_pos = $rs_pos->fetch_assoc()) {
    $order_ref_pos[] = $row_pos['grm_ref'];
}

// Fetch total entered stock
$tot_entered_stock = 0;
$sql_exp = "SELECT COALESCE(SUM(quantity), 0) AS qnty FROM tbl_expiry_date WHERE product_id='$p_id'";
$rs_exp = $conn->query($sql_exp);
if ($row_stock = $rs_exp->fetch_assoc()) {
    $tot_entered_stock = (int) $row_stock['qnty'];
}

// Fetch total sold stock
$tot_stock_sold = 0;
$sql_pos = "SELECT COALESCE(SUM(quantity), 0) AS qty FROM tbl_order WHERE product_id='$p_id'";
$rs_pos = $conn->query($sql_pos);
if ($row_pos = $rs_pos->fetch_assoc()) {
    $tot_stock_sold = (int) $row_pos['qty'];
}

// Get product name
$pname = getDataBack($conn, 'tbl_product', 'id', $p_id, 'name');

// Compute remaining stock
$balance_stock = max($tot_entered_stock - $tot_stock_sold, 0);

?>

<table class="table datatable" id="sales_report_id">
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Total Sold (POS)</th>
            <th>Total Stock Entered</th>
            <th>Balance Stock</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?= htmlspecialchars($pname) ?></td>
            <td><?= number_format($tot_stock_sold) ?></td>
            <td><?= number_format($tot_entered_stock) ?></td>
            <td><?= number_format($balance_stock) ?></td>
        </tr>
    </tbody>
</table>

<br>
<h4>&nbsp; Related Bills From POS</h4>
<br>

<table class="table datanew">
    <thead>
        <tr>
            <th>Reference Number</th>
            <th>Customer Name</th>
            <th>Date</th>
            <th>Payment Type</th>
            <th>Total Bill</th>
            <th>Total</th>
            <th>View Details</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($order_ref_pos as $grm_id) {
            // Fetch order details
            $sql = "SELECT g.id, g.order_ref, g.customer_id, g.order_date, g.payment_type, g.discount_price, c.c_name
                    FROM tbl_order_grm g
                    LEFT JOIN tbl_customer c ON g.customer_id = c.c_id
                    WHERE g.id = '$grm_id'
                    ORDER BY g.id DESC";
            $rs = $conn->query($sql);
            if ($rs->num_rows > 0) {
                $row = $rs->fetch_assoc();
                $ref = $row['id'];

                // Initialize totals
                $total_price = 0;
                $total_discount = 0;
                $returnAmount = 0;

                // Fetch all items for this order reference
                $sql_items = "SELECT * FROM tbl_order WHERE grm_ref='$ref'";
                $rs_items = $conn->query($sql_items);

                if ($rs_items->num_rows > 0) {
                    while ($row_item = $rs_items->fetch_assoc()) {
                        $id = $row_item['id'];
                        $p_id = $row_item['product_id'];
                        $qty = $row_item['quantity'];
                        $p_price = getDataBack($conn, 'tbl_product', 'id', $p_id, 'price') * $qty;
                        $discount = $row_item['discount'] ?? 0;
                        $discount = $discount * $qty;
                        $p_price -= $discount;
                        $total_discount += $discount;

                        // Check if the item is returned
                        $sqlReturn = "SELECT * FROM tbl_return_exchange WHERE or_id='$id'";
                        $rsReturn = $conn->query($sqlReturn);
                        if ($rsReturn->num_rows > 0) {
                            while ($rowExchange = $rsReturn->fetch_assoc()) {
                                $returnAmount += $p_price; // Mark as returned
                            }
                        } else {
                            $total_price += $p_price; // Add to total only if not returned
                        }
                    }
                }

                // Apply additional discount if provided
                if ($row['discount_price'] > 0) {
                    $total_price -= $row['discount_price'];
                    $total_discount += $row['discount_price'];
                }

                // Logic for amount to be paid or refunded
                if ($total_price > $returnAmount) {
                    $finalTotal = $total_price - $returnAmount;  // Customer needs to pay
                    $balanceReturn = 0;
                } elseif ($returnAmount > $total_price) {
                    $finalTotal = 0;
                    $balanceReturn = $returnAmount - $total_price; // Amount to return to customer
                } else {
                    $finalTotal = 0;
                    $balanceReturn = 0; // No payment needed
                }
        ?>
                <tr>
                    <td><?= htmlspecialchars($row['order_ref']) ?></td>
                    <td><?= htmlspecialchars($row['c_name'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['order_date']) ?></td>
                    <td><?= getPayment($row['payment_type']) ?></td>
                    <td><?= number_format($finalTotal, 2) ?></td>
                    <td><?= number_format($finalTotal, 2) ?></td>
                    <td>
                        <a href="print_bill.php?bill_id=<?= $row['id'] ?>" target="_blank">
                            <span style="color:#f74e05;font-weight:bold;">Print Bill</span>
                        </a>
                    </td>
                </tr>

                <?php
                // Get stock sold for this product in this order
                $sql_pos = "SELECT COALESCE(SUM(quantity), 0) AS qty FROM tbl_order WHERE product_id='$p_id' AND grm_ref='$grm_id'";
                $rs_pos = $conn->query($sql_pos);
                if ($rs_pos->num_rows > 0) {
                    $row_pos = $rs_pos->fetch_assoc();
                    $stock_only_item = $row_pos['qty'];
                ?>
                    <tr>
                        <td style="font-weight:bold;" colspan="4">
                            Total Sold <?= htmlspecialchars($pname) ?> on this bill:
                            <span style="color:#6e8c0a;font-size:18px;">(<?= number_format($stock_only_item) ?>)</span>
                        </td>
                    </tr>
                <?php } ?>
        <?php
            } // Closing bracket for first `if ($rs->num_rows > 0)`
        } // Closing bracket for `foreach`
        ?>
    </tbody>
</table>
<br>
