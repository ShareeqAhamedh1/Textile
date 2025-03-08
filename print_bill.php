<?php
include './backend/conn.php';

$billId = $_REQUEST['bill_id'];
$sqlGrm = "SELECT * FROM tbl_order_grm WHERE id='$billId'";
$rsGrm  = $conn->query($sqlGrm);
$rowGrm = $rsGrm->fetch_assoc();

$payment_type_id     = $rowGrm['payment_type'];
$order_ref           = $rowGrm['order_ref'];
$order_date          = $rowGrm['order_date'];
$payment_type        = getPayment($payment_type_id);
$cus_id              = $rowGrm['customer_id'];
$discount_price_bill = $rowGrm['discount_price'];
$cash_took           = $rowGrm['cash_took'];

// Get customer details
if ($cus_id != 0) {
    $cus_name    = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_name');
    $cus_phone   = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_phone');
    $cus_email   = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_email');
    $cus_address = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_address');
} else {
    $cus_name    = "Walk-in Customer";
    $cus_phone   = "";
    $cus_email   = "";
    $cus_address = "";
}

$tot_qnty         = 0;
$subtotal         = 0; // Sum of (price * qty) *before* subtracting discount
$total            = 0; // Running total net of discounts/returns
$total_discount   = 0; // Sum of all item-level discounts
$returnAmount     = 0; // Sum of returned items (net price - discount)
$cashReturnAmount = 0; // Sum of items refunded in cash

// Fetch order details
$sql_ord = "SELECT * FROM tbl_order WHERE grm_ref='$billId'";
$rs_ord  = $conn->query($sql_ord);

$items = [];

if ($rs_ord->num_rows > 0) {
    while ($rowOrd = $rs_ord->fetch_assoc()) {
        $pid        = $rowOrd['product_id'];
        $p_name     = getDataBack($conn, 'tbl_product', 'id', $pid, 'name');
        $p_price    = (float)getDataBack($conn, 'tbl_product', 'id', $pid, 'price');
        $barcode    = getDataBack($conn, 'tbl_product', 'id', $pid, 'barcode');
        $quantity   = (int)$rowOrd['quantity'];

        // If tbl_order.discount is discount per item, multiply by qty:
        $discountPerItem = (float)($rowOrd['discount'] ?? 0);
        $lineDiscount    = $discountPerItem * $quantity; // total discount for this line
        $linePrice       = $p_price * $quantity;
        $line_total      = $linePrice - $lineDiscount;

        // Check if item is returned or refunded in cash
        $is_returned    = false;
        $is_cash_refund = false;
        $sqlReturn      = "SELECT * FROM tbl_return_exchange WHERE or_id='" . $rowOrd['id'] . "'";
        $rsReturn       = $conn->query($sqlReturn);

        if ($rsReturn->num_rows > 0) {
            $rowExchange = $rsReturn->fetch_assoc();
            // ret_or_ex_st == 1 => returned item
            if ($rowExchange['ret_or_ex_st'] == 1) {
                $returnAmount += $line_total;
                $is_returned   = true;
            }
            // ret_or_ex_st == 0 => refunded in cash
            else if ($rowExchange['ret_or_ex_st'] == 0) {
                $cashReturnAmount += $line_total;
                $is_cash_refund   = true;
            }
        }

        // Build the display name with note on returns/refunds
        $displayName = $p_name;
        if ($is_returned) {
            $displayName .= ' (Returned)';
        } else if ($is_cash_refund) {
            $displayName .= ' (Cash Refund)';
        }

        // Collect row info for display
        $items[] = [
            'name'       => $displayName,
            'barcode'    => $barcode,
            'quantity'   => $quantity,
            'unit_price' => $p_price,
            'discount'   => $lineDiscount,    // total discount for this line
            'total'      => $line_total,      // net line total
            'is_returned'=> $is_returned,
            'is_cash_refund' => $is_cash_refund
        ];

        // Accumulate totals
        $subtotal       += $linePrice;
        $total          += $line_total;
        $total_discount += $lineDiscount;
        $tot_qnty       += $quantity;
    }
}

// --------------------------------------
// Now adjust final totals with bill-level discount
// --------------------------------------
$totalAfterReturns = $total
                    - $discount_price_bill  // subtract extra discount at the bill level
                    - $returnAmount;        // subtract returned items
$finalTotal = max($totalAfterReturns - $cashReturnAmount, 0); // also subtract cash refunds
// If the return total is greater than the net total, show "balance" to return
$balanceReturn = max(
    ($returnAmount + $cashReturnAmount) - ($total - $discount_price_bill),
    0
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=80mm, initial-scale=1.0">
  <title>Invoice #<?= $order_ref ?></title>
  <style>
  @media print {
    body {
      width: 78mm;
      font-size: 12px;
      margin: 0;
      padding-left: 15px;
      font-family: 'Courier New', Courier, monospace;
    }
    .logo-container img {
      width: 60mm;
      height: auto;
      max-height: 30mm;
    }
    .header, .store-details, .invoice-details, .totals, .customer-details, .footer {
      text-align: center;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
    }
    .items-table th, .items-table td {
      padding: 4px;
      border-bottom: 1px dashed #000;
      text-align: center;
    }
    .totals {
      margin-top: 10px;
      padding-top: 5px;
      border-top: 2px solid black;
    }
    #items {
      font-weight: 600;
      font-size: 12px;
    }
    @page {
      size: auto;
      margin: 0;
    }
  }
  </style>
</head>
<body>
  <br><br>
  <div class="header">
    <div class="logo-container">
      <img src="logo/b_k_logo.png" alt="Store Logo">
    </div>
    <div class="store-details">
      <div>No.115 Nuwara Eliya Road, Gampola</div>
      <div>Phone: 077 9003566</div>
    </div>
  </div>

  <div class="invoice-details">
    <div><strong>Invoice #: <?= $order_ref ?></strong></div>
    <div>Date: <?= $order_date ?></div>
    <div>Payment: <?= $payment_type ?></div>
  </div>

  <div class="customer-details">
    <strong>Customer Details:</strong>
    <div><?= $cus_name ?></div>
    <div><?= $cus_address ?></div>
    <div><?= $cus_phone ?></div>
    <div><?= $cus_email ?></div>
  </div>

  <table class="items-table">
    <thead>
      <tr>
        <th>Qty</th>
        <th>Product</th>
        <th>Unit</th>
        <th>Discount</th>
        <th>Total</th>
      </tr>
    </thead>
    <br>
    <tbody>
      <?php foreach ($items as $item) { ?>
        <tr id="items">
          <td><?= $item['quantity'] ?></td>
          <!-- Display product name & barcode -->
          <td><?= $item['name'] ?> / <?= $item['barcode'] ?></td>
          <td>Rs <?= number_format($item['unit_price'], 2) ?>/-</td>
          <!-- Show the total discount for the line (already multiplied by qty) -->
          <td>Rs <?= number_format($item['discount'], 2) ?>/-</td>
          <!-- If returned/refunded, display as negative -->
          <td>Rs <?= number_format(($item['is_returned'] || $item['is_cash_refund']) ? -$item['total'] : $item['total'], 2) ?>/-</td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <div class="totals">
    <div><strong>Total Quantity: <?= $tot_qnty ?></strong></div>
    <div><strong>Subtotal: Rs <?= number_format($subtotal, 2) ?>/-</strong></div>

    <?php if ($total_discount > 0 || $discount_price_bill > 0) { ?>
      <div><strong>Total Discount: Rs <?= number_format($total_discount + $discount_price_bill, 2) ?>/-</strong></div>
    <?php } ?>

    <?php if ($returnAmount > 0) { ?>
      <div><strong>Return Amount: -Rs <?= number_format($returnAmount, 2) ?>/-</strong></div>
    <?php } ?>

    <?php if ($cashReturnAmount > 0) { ?>
      <div><strong>Cash Refund: -Rs <?= number_format($cashReturnAmount, 2) ?>/-</strong></div>
    <?php } ?>

    <div><strong>Final Total: Rs <?= number_format($finalTotal, 2) ?>/-</strong></div>

    <?php if ($balanceReturn > 0) { ?>
      <div><strong>Balance to Return: Rs <?= number_format($balanceReturn, 2) ?>/-</strong></div>
    <?php } ?>

    <?php if ($cash_took > 0) {
        $balancePaid = $cash_took - $finalTotal;
    ?>
      <div><strong>Cash Received: </strong> Rs <?= number_format($cash_took, 2) ?>/-</div>
      <div><strong>Balance Paid: </strong> Rs <?= number_format($balancePaid, 2) ?>/-</div>
    <?php } ?>
  </div>

  <div class="footer">
    <p>Exchange of any item in its original condition (with receipt) is possible within 7 days.</p>
    <p>Thank you! Come again.</p>
  </div>

  <br><br>
  <script>
    window.onload = function() {
      // Set the onafterprint event before calling print
      window.onafterprint = function() {
          window.location.href = "pos.php"; // Redirect after printing
      };
      // Slight delay to ensure content is fully loaded
      setTimeout(function() {
          window.print();
      }, 500);
    };
  </script>
</body>
</html>
