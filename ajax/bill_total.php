<?php
    include '../backend/conn.php';
    $grm_id = $_SESSION['grm_ref'];
    $total_price = 0; // Only includes new items
    $total_discount = 0;
    $returnAmount = 0;

    $sql = "SELECT * FROM `tbl_order` WHERE grm_ref='$grm_id' ORDER BY id DESC";
    $rs = $conn->query($sql);

    if ($rs->num_rows > 0) {
        while ($row = $rs->fetch_assoc()) {
            $id = $row['id'];
            $p_id = $row['product_id'];
            $qty = $row['quantity'];
            $p_price = getDataBack($conn, 'tbl_product', 'id', $p_id, 'price') * $qty;
            $discount = $row['discount'] ?? 0;
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

        // Apply additional discount if provided
        if (isset($_REQUEST['disc_price']) && $_REQUEST['disc_price'] !== '') {
    $extra_discount = $_REQUEST['disc_price'];

    if (strpos($extra_discount, '%') !== false) {
        // Extract numeric value and calculate percentage discount
        $discount_percent = floatval(str_replace('%', '', $extra_discount));
        $extra_discount = ($discount_percent / 100) * $total_price;
    } else {
        // Ensure it's a valid numeric value
        $extra_discount = floatval($extra_discount);
    }

    // Apply discount
    $total_price -= $extra_discount;
    $total_discount += $extra_discount;
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

        <!-- UI Display -->
        <div class="border p-2 rounded bg-light" style="font-size: 0.9rem;">
            <p class="mb-1">
                <span class="text-muted">Total New Items Bill:</span>
                <span class="fw-bold text-primary">LKR <?= number_format($total_price, 2) ?></span>
            </p>

            <?php if ($returnAmount > 0): ?>
                <p class="mb-1">
                    <span class="fw-bold">Item Return Value:</span>
                    <span>LKR <?= number_format($returnAmount, 2) ?></span>
                </p>
            <?php endif; ?>

            <hr class="my-1">

            <?php if ($finalTotal > 0): ?>
                <p class="mb-1">
                    <span class="text-muted">Amount to Pay:</span>
                    <span class="fw-bold text-danger">LKR <?= number_format($finalTotal, 2) ?></span>
                </p>
            <?php elseif ($balanceReturn > 0): ?>
                <p class="mb-1">
                    <span class="fw-bold">Balance to Return:</span>
                    <span class="text-success">LKR <?= number_format($balanceReturn, 2) ?></span>
                </p>
            <?php else: ?>
                <p class="mb-1 fw-bold text-success">No Payment Required</p>
            <?php endif; ?>

            <?php if ($total_discount > 0): ?>
                <p class="mb-1 text-muted">
                    <small>
                        <s class="text-secondary">LKR <?= number_format($total_price + $total_discount, 2) ?></s>
                        <span class="text-success ms-2">Total Discount: -LKR <?= number_format($total_discount, 2) ?></span>
                    </small>
                </p>
            <?php endif; ?>
        </div>

        <script type="text/javascript">
            document.getElementById('totPrice').value = <?= $total_price ?>;
            document.getElementById('addedValueTxt').value = <?= $total_price ?>;
            document.getElementById('discount_amount').value=<?= $extra_discount ?>
        </script>

<?php
    } else { ?>
        <p class="mb-1" style="font-size: 0.9rem;">Total: <span class="fw-bold">LKR 0</span></p>
<?php } ?>
