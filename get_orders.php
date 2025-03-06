<?php
include './backend/conn.php';

// Get the search query from the AJAX request
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Modify the SQL query to include a search filter for customer name or reference number
$sql = "SELECT g.*, c.c_name, c.c_phone
        FROM tbl_order_grm g
        LEFT JOIN tbl_customer c ON g.customer_id = c.c_id
        WHERE g.order_ref LIKE '%$search_query%' OR c.c_name LIKE '%$search_query%' OR c.c_phone LIKE '%$search_query%'
        ORDER BY g.id DESC";

$rs = $conn->query($sql);

// Output the table rows as HTML
if ($rs->num_rows > 0) {
    while ($row = $rs->fetch_assoc()) {
        $orderStatus = $row['order_st'];
        if($orderStatus == 0){
          $orSt="DRAFT";
        }
        else {
          $orSt="Completed";
        }

        $ref = intval($row['id']); // Ensure ID is always an integer
        $customer = htmlspecialchars($row['c_name'] ?? 'N/A');
        $customerPhone = htmlspecialchars($row['c_phone'] ?? 'N/A');
        ?>
        <tr>
            <td><?= htmlspecialchars($row['order_ref']) ?> - '<?= $orSt ?>' </td>
            <td><?= $customer ?></td>
            <td><?= $customerPhone ?></td>
            <td><?= htmlspecialchars($row['order_date']) ?></td>
            <td style="color: <?= ($row['payment_type'] == "3") ? 'red' : 'black' ?>;">
    <?= htmlspecialchars(getPayment($row['payment_type'])) ?>
</td>

<?php

    $total         = 0;
    $returnedValue = 0;
    $totDiscount   = 0;

    $sqlS = "SELECT * FROM tbl_order WHERE grm_ref='$ref'";
    $rsS  = $conn->query($sqlS);

    if ($rsS && $rsS->num_rows > 0) {
        while ($rowS = $rsS->fetch_assoc()) {
            $id       = $rowS['id'];
            $pid      = $rowS['product_id'];
            $qty      = $rowS['quantity'];
            $discount = $rowS['discount'];
            $priceP   = getDataBack($conn, 'tbl_product', 'id', $pid, 'price');

            $linePrice = $priceP * $qty;

            $sqlReturn = "SELECT * FROM tbl_return_exchange WHERE or_id = '$id'";
            $rsReturn  = $conn->query($sqlReturn);

            if ($rsReturn && $rsReturn->num_rows > 0) {
                $returnedValue += $linePrice;
            } else {
                $total += $linePrice;
            }

            $totDiscount += $discount;
        }
    }

    $totDiscount += $row['discount_price'];

    $billValue = $total - $totDiscount;
    if ($billValue < 0) {
        $billValue = 0; // prevent negative if discounts exceed total
    }

    $cashPaid = 0;
    $refund   = 0;

    if ($billValue > $returnedValue) {
        $cashPaid = $billValue - $returnedValue;
    } elseif ($returnedValue > $billValue) {
        $refund = $returnedValue - $billValue;
    }
?>

<td>
    <div>
        <strong>Total Bill Value:</strong>
        LKR <?= number_format($billValue, 2) ?>
    </div>

    <?php if ($returnedValue > 0): ?>
        <div>
            <strong>Returned Item Value:</strong>
            LKR <?= number_format($returnedValue, 2) ?>
        </div>
    <?php endif; ?>

    <?php if ($totDiscount > 0): ?>
        <div>
            <strong>Discount Applied:</strong>
            LKR <?= number_format($totDiscount, 2) ?>
        </div>
    <?php endif; ?>

    <?php if ($cashPaid > 0): ?>
        <div>
            <strong>Cash Paid By Customer:</strong>
            LKR <?= number_format($cashPaid, 2) ?>
        </div>
    <?php elseif ($refund > 0): ?>
        <div>
            <strong>Refund to Customer:</strong>
            LKR <?= number_format($refund, 2) ?>
        </div>
    <?php else: ?>
        <div>
            <strong>Cash Paid / Refund:</strong> LKR 0.00
        </div>
    <?php endif; ?>
</td>






            <td>
                <a class="me-3" href="backend/gotopos.php?grm_id=<?= $ref ?>" >
                    <button type="button" class="btn btn-secondary btn-sm" name="button">VIEW</button>
                </a>
                <a href="print_bill.php?bill_id=<?= $ref ?>" target="_blank">
                    <span style="color:#f74e05;font-weight:bold;">Print Bill</span>
                </a>
            </td>
            <td>
              <?php if($orderStatus == 0){ ?>
                <a onclick="del_order(<?= $ref ?>)" class="me-3 confirm-text" href="javascript:void(0);">
                    <img src="assets/img/icons/delete.svg" alt="Delete">
                </a>
              <?php } ?>

            </td>
        </tr>
    <?php
    }
}
?>
