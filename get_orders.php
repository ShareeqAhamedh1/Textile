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
            $total =0;
            $totDiscount=0;
            // Calculate Discounted Total in a Single Query
            $sqlS = "SELECT * FROM tbl_order
                     WHERE grm_ref='$ref'";

            $rsS = $conn->query($sqlS);
            if($rsS->num_rows > 0){
              while($rowS=$rsS->fetch_assoc()){
                $id= $rowS['id'];
              $pid = $rowS['product_id'];
              $qty = $rowS['quantity'];
              $discount = $rowS['discount'];

              $priceP = getDataBack($conn,'tbl_product','id',$pid,'price');
              $sqlReturn ="SELECT * FROM tbl_return_exchange WHERE or_id='$id'";
              $rsReturn = $conn->query($sqlReturn);
              if($rsReturn->num_rows == 0){
                $total +=$priceP * $qty;
              }
              $totDiscount +=$discount;
            }
            }
            $totDiscount +=$row['discount_price'];
            ?>
            <td>
    <?php if ($totDiscount > 0): ?>
        <div class="fw-bold text-primary" style="font-size: 1rem;">
            LKR <?= number_format($total - $totDiscount, 2) ?> <!-- Final Price -->
        </div>
        <div class="text-muted" style="font-size: 0.9rem;">
            <s>LKR <?= number_format($total, 2) ?></s> <!-- Before Discount -->
            <span class="text-success ms-2">(-LKR <?= number_format($totDiscount, 2) ?> Discount)</span>
        </div>
    <?php else: ?>
        <div class="fw-bold text-primary" style="font-size: 1.2rem;">
            LKR <?= number_format($total, 2) ?> <!-- Normal price when no discount -->
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
                <a onclick="del_order(<?= $ref ?>)" class="me-3 confirm-text" href="javascript:void(0);">
                    <img src="assets/img/icons/delete.svg" alt="Delete">
                </a>
            </td>
        </tr>
    <?php
    }
}
?>
