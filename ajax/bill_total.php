<?php
    include '../backend/conn.php';
    $grm_id = $_SESSION['grm_ref'];
    $total_price = 0;
    $total_discount = 0; // Track total discount across all items

    $sql = "SELECT * FROM `tbl_order` WHERE grm_ref='$grm_id' ORDER BY id DESC";
    $rs = $conn->query($sql);

    if ($rs->num_rows > 0) {
        while ($row = $rs->fetch_assoc()) {
            $id = $row['id'];

            $sqlReturn = "SELECT * FROM tbl_return_exchange WHERE or_id='$id'";
            $rsReturn = $conn->query($sqlReturn);
            if ($rsReturn->num_rows == 0) {
                $p_id = $row['product_id'];
                $qty = $row['quantity'];
                $p_name = getDataBack($conn, 'tbl_product', 'id', $p_id, 'name');
                $p_price = getDataBack($conn, 'tbl_product', 'id', $p_id, 'price') * $qty;
                $discount = $row['discount'] ?? 0; // Fetch stored discount, default to 0

                // Track the total discount
                $total_discount += $discount;

                // Calculate final price after discount
                $p_price = $p_price - $discount;
                $total_price += $p_price;
            }
        }

        // Apply additional discount if provided
        if (isset($_REQUEST['disc_price'])) {
            $extra_discount = $_REQUEST['disc_price'];
            $original_price = $total_price; // Store original price before extra discount
            $total_price -= $extra_discount;
            $total_discount += $extra_discount; // Add extra discount to total discount
        } else {
            $extra_discount = 0;
            $original_price = $total_price;
        }
        ?>

        <!-- Display the total price and discounts -->
        <div class="border p-3 rounded bg-light">
            <p class="h5 mb-0">
                <span class="text-muted me-2">Total:</span>
                <span class="fw-bold text-primary" style="font-size: 1.3rem;">
                    LKR <?= number_format($total_price, 2) ?>
                </span>
            </p>

            <?php if ($total_discount > 0): ?>
                <p class="mb-0 text-muted">
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
        <!-- This section correctly shows if no orders exist -->
        <p class="h5 mb-0">Total: <span class="fw-bold">LKR 0</span></p>
    <?php } ?>
