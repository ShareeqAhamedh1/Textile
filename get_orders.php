<?php
include './backend/conn.php';

// Get search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch order details along with customer details
$sql = "SELECT g.*, c.c_name, c.c_phone, c.c_id
        FROM tbl_order_grm g
        LEFT JOIN tbl_customer c ON g.customer_id = c.c_id
        WHERE g.order_ref LIKE '%$search_query%'
        OR c.c_name LIKE '%$search_query%'
        OR c.c_phone LIKE '%$search_query%'
        ORDER BY g.id DESC";

$rs = $conn->query($sql);

if ($rs->num_rows > 0) {
    while ($row = $rs->fetch_assoc()) {
        $orderStatus = $row['order_st'];
        $orSt = ($orderStatus == 0) ? "DRAFT" : "Completed";

        $ref = intval($row['id']);
        $customer = htmlspecialchars($row['c_name'] ?? 'N/A');
        $customerPhone = htmlspecialchars($row['c_phone'] ?? 'N/A');
        $payType = $row['payment_type'];
        $c_id = $row['c_id'];
        $cashTook = floatval($row['cash_took']); // Fetch cash took

        // Initialize totals
        $total = 0;
        $returnedValue = 0;
        $totDiscount = 0;
        $billDisc = 0;
        $billDiscTot=0;

        // Fetch order items
        $sqlS = "SELECT * FROM tbl_order WHERE grm_ref='$ref'";
        $rsS = $conn->query($sqlS);

        if ($rsS->num_rows > 0) {
            while ($rowS = $rsS->fetch_assoc()) {
                $id = $rowS['id'];
                $pid = $rowS['product_id'];
                $qty = $rowS['quantity'];
                $discountPerItem = floatval($rowS['discount']);
                $priceP = floatval(getDataBack($conn, 'tbl_product', 'id', $pid, 'price'));

                $linePrice = $priceP * $qty;
                $lineDiscount = $discountPerItem * $qty;
                $lineTotal = $linePrice - $lineDiscount;

                // Check if item is returned
                $sqlReturn = "SELECT * FROM tbl_return_exchange WHERE or_id = '$id'";
                $rsReturn = $conn->query($sqlReturn);

                if ($rsReturn->num_rows > 0) {
                    $returnedValue += $lineTotal;
                    $billDisc += $lineDiscount;
                } else {
                    $total += $lineTotal;
                }
                $totDiscount += $lineDiscount;
            }
        }

        // Apply global discount
        $billDiscTot = floatval($row['discount_price']);

        // Calculate final amounts
        $billValue = max($total-$billDiscTot, 0);
        $balanceReturn = max($returnedValue - $billValue, 0);
        $finalTotal = max($billValue - $returnedValue, 0);
        $totDiscount +=$billDiscTot;

        ?>
        <tr>
            <td><?= htmlspecialchars($row['order_ref']) ?> - <?= $orSt ?></td>
            <td><?= $customer ?></td>
            <td><?= $customerPhone ?></td>
            <td><?= htmlspecialchars($row['order_date']) ?></td>
            <td style="color: <?= ($payType == "3") ? 'red' : 'black' ?>;">
                <?= htmlspecialchars(getPayment($payType)) ?>
            </td>

            <td>
    <div class="border p-3 rounded bg-light shadow-sm" style="font-size: 1rem;">
        <p class="mb-2">
            <span class="text-muted">New Bill Value:</span>
            <span class="fw-bold text-primary">LKR <?= number_format($billValue, 2) ?></span>
        </p>

        <?php if ($returnedValue > 0): ?>
            <p class="mb-2">
                <span class="fw-bold">Returned Items Value:</span>
                <span class="text-danger">LKR <?= number_format($returnedValue, 2) ?></span>
            </p>
        <?php endif; ?>

        <hr class="my-2">

        <?php
            // Calculate remaining balance after cash paid
            if($payType == 3){
              $creditAmount = max($finalTotal - $cashTook, 0);
            }
            else {
              $creditAmount = 0;
            }
        ?>

        <?php if ($creditAmount > 0): ?>
            <p class="mb-2">
                <span class="fw-bold">Credit Amount (Pending Payment):</span>
                <span class="text-danger">LKR <?= number_format($creditAmount, 2) ?></span>
            </p>
        <?php endif; ?>

        <?php if ($payType !=3 && $finalTotal > 0): ?>
              <p class="mb-2 fw-bold text-success">Customer Paid:LKR <?= number_format($finalTotal, 2) ?>  </p>
        <?php endif; ?>


        <?php if ($cashTook > 0): ?>
            <p class="mb-2">
                <span class="fw-bold text-secondary">Cash Took:</span>
                <span class="text-info">LKR <?= number_format($cashTook, 2) ?></span>
            </p>
        <?php endif; ?>

        <?php if ($totDiscount > 0): ?>
            <p class="mb-2 text-muted">
                <small>
                    <s class="text-secondary">LKR <?= number_format($billValue + $totDiscount, 2) ?></s>
                    <span class="text-success ms-2">Total Discount: -LKR <?= number_format($totDiscount, 2) ?></span>
                </small>
            </p>
        <?php endif; ?>
    </div>
</td>



            <td>
                <a class="me-3" href="backend/gotopos.php?grm_id=<?= $ref ?>">
                    <button type="button" class="btn btn-secondary btn-sm">VIEW</button>
                </a>
                <a href="print_bill.php?bill_id=<?= $ref ?>" target="_blank">
                    <span style="color:#f74e05;font-weight:bold;">Print Bill</span>
                </a>
            </td>

            <td>
                <?php if ($orderStatus == 0): ?>
                    <a onclick="del_order(<?= $ref ?>)" class="me-3 confirm-text" href="javascript:void(0);">
                        <img src="assets/img/icons/delete.svg" alt="Delete">
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php
    }
}
?>
