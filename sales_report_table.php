<?php
include 'backend/conn.php';
$p_id = $_REQUEST['p_id'];

$from_date = $_REQUEST['f_date'] ?? 0;
$to_date = $_REQUEST['t_date'] ?? 0;

$sqlVendorReturns ="SELECT SUM(ret_qty) AS tot_qty_ret FROM tbl_item_return WHERE p_id='$p_id'";
$rsVendorsReturns =$conn->query($sqlVendorReturns);
$vendorRetQty =0;
if($rsVendorsReturns->num_rows > 0){
  $rowVenRet =$rsVendorsReturns->fetch_assoc();
  $vendorRetQty =$rowVenRet['tot_qty_ret'];
}
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
$sql_sold = "SELECT COALESCE(SUM(quantity), 0) AS qty FROM tbl_order WHERE product_id='$p_id'";
$rs_sold = $conn->query($sql_sold);
if ($row_sold = $rs_sold->fetch_assoc()) {
    $tot_stock_sold = (int) $row_sold['qty'];
}

// Fetch total returned items
$total_returned = 0;
$sql_return = "SELECT COALESCE(SUM(o.quantity), 0) AS returned_qty
               FROM tbl_return_exchange r
               INNER JOIN tbl_order o ON r.or_id = o.id
               WHERE o.product_id='$p_id'";
$rs_return = $conn->query($sql_return);
if ($row_return = $rs_return->fetch_assoc()) {
    $total_returned = (int) $row_return['returned_qty'];
}

// Calculate accurate sold stock after returns
$actual_stock_sold = max($tot_stock_sold - $total_returned, 0);

// Get product name
$pname = getDataBack($conn, 'tbl_product', 'id', $p_id, 'name');

// Compute remaining stock
$balance_stock = currentStockCount($conn,$p_id);

?>

<table class="table datatable" id="sales_report_id">
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Total Sold (POS)</th>
            <th>Total Returned</th>
            <th>Total Vendor Returns</th>
            <th>Final Sold Quantity</th>
            <th>Total Stock Entered</th>
            <th>Balance Stock</th>
            <th>Sales Value</th>
            <th>Cost Value</th>
            <th>Profit/Loss</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?= htmlspecialchars($pname) ?></td>
            <td><?= number_format($tot_stock_sold) ?></td>
            <td><?= number_format($total_returned) ?></td>
            <td> <?= $vendorRetQty ?> </td>
            <td><?= number_format($actual_stock_sold) ?></td>
            <td><?= number_format($tot_entered_stock) ?></td>
            <td><?= number_format($balance_stock) ?></td>
            <td> <?= getProductPrice($conn,$p_id) ?> </td>
            <td> <?= getProductCost($conn,$p_id) ?> </td>
            <td>LKR <?= number_format(getProductPrice($conn,$p_id) - getProductCost($conn,$p_id),2) ?> </td>
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
            <th>Net Payable</th>
            <th>View Details</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($order_ref_pos as $grm_id) {
            // Fetch order details
            $sql = "SELECT g.*, c.c_name, c.c_phone, c.c_id
                    FROM tbl_order_grm g
                    LEFT JOIN tbl_customer c ON g.customer_id = c.c_id
                    WHERE g.id = '$grm_id'
                    ORDER BY g.id DESC";
            $rs = $conn->query($sql);
            if ($rs->num_rows > 0) {
                $row = $rs->fetch_assoc();
                $ref = intval($row['id']);

                $customer = htmlspecialchars($row['c_name'] ?? 'N/A');
                $payType = $row['payment_type'];
                $cashTook = floatval($row['cash_took']);

                // Initialize totals
                $total = 0;
                $returnedValue = 0;
                $totDiscount = 0;
                $billDiscTot = floatval($row['discount_price']);
                $total_sold_qty = 0;
                $total_return_qty = 0;

                // Fetch order items with correct return deduction
                $sqlS = "SELECT o.*,
                                (SELECT COALESCE(SUM(quantity), 0) FROM tbl_return_exchange r WHERE r.or_id = o.id) AS returned_qty
                         FROM tbl_order o
                         WHERE o.grm_ref='$ref'";
                $rsS = $conn->query($sqlS);

                if ($rsS->num_rows > 0) {
                    while ($rowS = $rsS->fetch_assoc()) {
                        $id = $rowS['id'];
                        $pid = $rowS['product_id'];
                        $qty = $rowS['quantity'];
                        $returned_qty = (int) $rowS['returned_qty'];
                        $discountPerItem = floatval($rowS['discount']);
                        $priceP = floatval(getDataBack($conn, 'tbl_product', 'id', $pid, 'price'));

                        $linePrice = $priceP * $qty;
                        $lineDiscount = $discountPerItem * $qty;
                        $lineTotal = $linePrice - $lineDiscount;

                        $total_sold_qty += $qty;
                        $total_return_qty += $returned_qty;

                        if ($returned_qty > 0) {
                            $returnedValue += $lineTotal;
                        } else {
                            $total += $lineTotal;
                        }

                        $totDiscount += $lineDiscount;
                    }
                }

                // Final bill calculations
                $billValue = max($total - $billDiscTot, 0);
                $finalTotal = max($billValue - $returnedValue, 0);
                $totDiscount += $billDiscTot;
                $creditAmount = ($payType == 3) ? max($finalTotal - $cashTook, 0) : 0;
        ?>
                <tr>
                    <td><?= htmlspecialchars($row['order_ref']) ?></td>
                    <td><?= $customer ?></td>
                    <td><?= htmlspecialchars($row['order_date']) ?></td>
                    <td><?= getPayment($payType) ?></td>
                    <td><?= number_format($billValue, 2) ?></td>
                    <td><?= number_format($finalTotal, 2) ?></td>
                    <td>
                        <a href="print_bill.php?bill_id=<?= $ref ?>" target="_blank">
                            <span style="color:#f74e05;font-weight:bold;">Print Bill</span>
                        </a>
                    </td>
                </tr>

                <tr>
                    <td colspan="4">
                        <strong>Total Sold <?= htmlspecialchars($pname) ?> on this bill:</strong>
                        <span style="color:#6e8c0a;font-size:18px;">(<?= number_format($total_sold_qty - $total_return_qty) ?>)</span>
                    </td>
                </tr>
        <?php
            }
        } ?>
    </tbody>
</table>
<br>
