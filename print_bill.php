<?php
include './backend/conn.php';

$billId = $_REQUEST['bill_id'];
$sqlGrm = "SELECT * FROM tbl_order_grm WHERE id='$billId'";
$rsGrm = $conn->query($sqlGrm);
$rowGrm = $rsGrm->fetch_assoc();

$payment_type_id = $rowGrm['payment_type'];
$order_ref      = $rowGrm['order_ref'];
$order_date     = $rowGrm['order_date'];
$payment_type   = getPayment($payment_type_id);
$cus_id         = $rowGrm['customer_id'];
$discount_price_bill = $rowGrm['discount_price'];

$cash_took = $rowGrm['cash_took'];

if ($cus_id != 0) {
    $cus_name    = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_name');
    $cus_phone   = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_phone');
    $cus_email   = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_email');
    $cus_address = getDataBack($conn, 'tbl_customer', 'c_id', $cus_id, 'c_address');
} else {
    $cus_name = "Walk-in Customer";
    $cus_phone = $cus_email = $cus_address = "";
}

$tot_qnty = 0;
$total = 0;
$total_discount = 0;
$subtotal = 0;
$returnAmount = 0;
$cashReturnAmount = 0;

// Fetch order details
$sql_ord = "SELECT * FROM tbl_order WHERE grm_ref='$billId'";
$rs_ord = $conn->query($sql_ord);

$items = [];

if ($rs_ord->num_rows > 0) {
    while ($rowOrd = $rs_ord->fetch_assoc()) {
        $pid      = $rowOrd['product_id'];
        $p_name   = getDataBack($conn, 'tbl_product', 'id', $pid, 'name');
        $p_price  = getDataBack($conn, 'tbl_product', 'id', $pid, 'price');
	$barcode   = getDataBack($conn, 'tbl_product', 'id', $pid, 'barcode');
        $quantity = $rowOrd['quantity'];
        $discount = $rowOrd['discount'] ?? 0;
        $line_total = ($p_price * $quantity) - $discount;

        $is_returned = false;
        $is_cash_refund = false;

        // Check if item is returned or refunded in cash
        $sqlReturn = "SELECT * FROM tbl_return_exchange WHERE or_id='" . $rowOrd['id'] . "'";
        $rsReturn = $conn->query($sqlReturn);
        if ($rsReturn->num_rows > 0) {
            $rowExchange = $rsReturn->fetch_assoc();
            if ($rowExchange['ret_or_ex_st'] == 1) {
                $returnAmount += $line_total;
                $is_returned = true;
            } else if ($rowExchange['ret_or_ex_st'] == 0) {
                $cashReturnAmount += $line_total;
                $is_cash_refund = true;
            }
        }

        $items[] = [
            'name' => $p_name . ($is_returned ? ' (Returned)' : ($is_cash_refund ? ' (Cash Refund)' : '')),
            'quantity' => $quantity,
            'unit_price' => $p_price,
            'discount' => $discount,
            'total' => $line_total,
            'is_returned' => $is_returned,
            'is_cash_refund' => $is_cash_refund
        ];

        $subtotal += $p_price * $quantity;
        $total += $line_total;
        $total_discount += $discount;
        $tot_qnty += $quantity;
    }
}

// Apply bill discount
$totalAfterReturns = $total - $discount_price_bill - $returnAmount;
$finalTotal = max($totalAfterReturns - $cashReturnAmount, 0);
$balanceReturn = max(($returnAmount + $cashReturnAmount) - ($total - $discount_price_bill), 0);
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
#items{
	font-weight: 600;
	font-size:12px;
}
    @page {
      size: auto;
      margin: 0;
    }
  }
  </style>
</head>
<body>
<br>
<br>
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
          <td><?= $item['name'] ?> / <?= $barcode ?></td>
          <td>Rs <?= number_format($item['unit_price']) ?>/-</td>
          <td>Rs <?= number_format($item['discount']) ?>/-</td>
          <td>Rs <?= number_format($item['is_returned'] || $item['is_cash_refund'] ? -$item['total'] : $item['total']) ?>/-</td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <div class="totals">
    <div><strong>Total Quantity:<?= $tot_qnty ?></strong> </div>
    <div><strong>Subtotal: Rs <?= number_format($subtotal) ?>/-</strong></div>

    <?php if ($total_discount > 0 || $discount_price_bill > 0) { ?>
      <div><strong>Total Discount: Rs <?= number_format($total_discount + $discount_price_bill) ?>/-</strong></div>
    <?php } ?>

    <?php if ($returnAmount > 0) { ?>
      <div><strong>Return Amount: -Rs <?= number_format($returnAmount) ?>/-</strong></div>
    <?php } ?>

    <?php if ($cashReturnAmount > 0) { ?>
      <div><strong>Cash Refund: -Rs <?= number_format($cashReturnAmount) ?>/-</strong></div>
    <?php } ?>

    <div><strong>Final Total: Rs <?= number_format($finalTotal) ?>/-</strong></div>

    <?php if ($balanceReturn > 0) { ?>
      <div><strong>Balance to Return: Rs <?= number_format($balanceReturn) ?>/-</strong></div>
    <?php } ?>
    <?php if($cash_took > 0){ ?>
      <div><strong>Cash Recived</strong> Rs <?= number_format($cash_took) ?>/-</div>
      <div><strong>Balance Paid</strong> Rs <?= number_format($cash_took - $finalTotal) ?>/-</div>
    <?php } ?>
  </div>
  <div class="footer">
    <div>
    <p>Exchange of any item in its original condition with receipt is possible within 7 days </p>
    <p>Thank you! Come again.</p></div>
  </div>
<br>
<br>
  <script>
  window.onload = function() {
  // Set the onafterprint event before calling print
  window.onafterprint = function() {
      window.location.href = "pos.php"; // Redirect after printing
  };

  // Delay to ensure the page is fully loaded before printing
  setTimeout(function() {
      window.print();
  }, 500);
};

  </script>
</body>
</html>
