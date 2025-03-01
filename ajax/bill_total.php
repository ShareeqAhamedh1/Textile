<?php
    include '../backend/conn.php';
    $grm_id = $_SESSION['grm_ref'];
    $total_price = 0;
    $total_discount = 0;
    $returnAmount = 0;
    $cashReturnAmount = 0;

    $sql = "SELECT * FROM `tbl_order` WHERE grm_ref='$grm_id' ORDER BY id DESC";
    $rs = $conn->query($sql);

    if ($rs->num_rows > 0) {
        while ($row = $rs->fetch_assoc()) {
            $id = $row['id'];
            $p_id = $row['product_id'];
            $qty = $row['quantity'];
            $p_name = getDataBack($conn, 'tbl_product', 'id', $p_id, 'name');
            $p_price = getDataBack($conn, 'tbl_product', 'id', $p_id, 'price') * $qty;
            $discount = $row['discount'] ?? 0;
            $total_discount += $discount;
            $p_price -= $discount;
            $total_price += $p_price;

            // Check if the item is returned
            $sqlReturn = "SELECT * FROM tbl_return_exchange WHERE or_id='$id'";
            $rsReturn = $conn->query($sqlReturn);
            if ($rsReturn->num_rows > 0) {
                $rowExchange = $rsReturn->fetch_assoc();
                $ret_status = $rowExchange['ret_or_ex_st'];
                if ($ret_status == 1) {
                    $returnAmount += $p_price; // Item return
                } else if ($ret_status == 0) {
                    $cashReturnAmount += $p_price; // Cash refund
                }
            }
        }

        // Apply additional discount if provided
        if (isset($_REQUEST['disc_price'])) {
            $extra_discount = $_REQUEST['disc_price'];
            $original_price = $total_price;
            $total_price -= $extra_discount;
            $total_discount += $extra_discount;
        } else {
            $extra_discount = 0;
            $original_price = $total_price;
        }

        // Calculate the final total correctly
        $totalAfterReturns = $total_price - $returnAmount;
        $finalTotal = max($totalAfterReturns - $cashReturnAmount, 0);
        $balanceReturn = max(($returnAmount + $cashReturnAmount) - $total_price, 0);
        ?>

        <!-- Display total, return amount, and balance with small but clear fonts -->
        <div class="border p-2 rounded bg-light" style="font-size: 0.9rem;">
            <p class="mb-1">
                <span class="text-muted">Total Bill:</span>
                <span class="fw-bold text-primary">LKR <?= number_format($total_price, 2) ?></span>
            </p>

            <?php if ($returnAmount > 0): ?>
                <p class="mb-1">
                    <span class="fw-bold">Item Return Value:</span>
                    <span>LKR <?= number_format($returnAmount, 2) ?></span>
                </p>
            <?php endif; ?>

            <?php if ($cashReturnAmount > 0): ?>
                <p class="mb-1">
                    <span class="fw-bold">Cash Refund:</span>
                    <span>LKR <?= number_format($cashReturnAmount, 2) ?></span>
                </p>
            <?php endif; ?>

            <hr class="my-1">

            <p class="mb-1">
                <span class="text-muted">Final Amount to Pay:</span>
                <span class="fw-bold <?= ($finalTotal == 0) ? 'text-success' : 'text-danger' ?>">
                    LKR <?= number_format($finalTotal, 2) ?>
                </span>
            </p>

            <?php if ($balanceReturn > 0): ?>
                <p class="mb-1">
                    <span class="fw-bold">Balance to Return:</span>
                    <span>LKR <?= number_format($balanceReturn, 2) ?></span>
                </p>
            <?php endif; ?>

            <?php if ($total_discount > 0): ?>
                <p class="mb-1 text-muted">
                    <small>
                        <s class="text-secondary">LKR <?= number_format($original_price + $total_discount, 2) ?></s>
                        <span class="text-success ms-2">Total Discount: -LKR <?= number_format($total_discount, 2) ?></span>
                    </small>
                </p>
            <?php endif; ?>
        </div>

        <script type="text/javascript">
            document.getElementById('totPrice').value = <?= $total_price ?>;
            document.getElementById('addedValueTxt').value = <?= $total_price ?>;
        </script>

        <?php
    } else { ?>
        <p class="mb-1" style="font-size: 0.9rem;">Total: <span class="fw-bold">LKR 0</span></p>
    <?php } ?>
