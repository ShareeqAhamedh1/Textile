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
$discount_price_bill = $rowGrm['discount_price']; // Bill Discount

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

$tot_qnty = 0;
$total = 0;
$total_discount = 0; // Reset total discount
$subtotal = 0; // Subtotal before discount
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
      width: 80mm;
      font-size: 12px;
      margin: 0;
      padding: 0;
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
    .items-table th {
      background: #f2f2f2;
      font-weight: bold;
    }
    .totals {
      margin-top: 10px;
      padding-top: 5px;
      border-top: 2px solid black;
    }
    .footer {
      margin-top: 10px;
      font-size: 11px;
    }
    @page {
      size: auto;
      margin: 0;
    }
  }
  </style>
</head>
<body>
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
        <th style="width: 10%;">Qty</th>
        <th style="width: 35%;">Product</th>
        <th style="width: 15%;">Unit</th>
        <th style="width: 15%;">Discount</th>
        <th style="width: 25%;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql_ord = "SELECT * FROM tbl_order WHERE grm_ref='$billId'";
      $rs_ord = $conn->query($sql_ord);

      if ($rs_ord->num_rows > 0) {
        while ($rowOrd = $rs_ord->fetch_assoc()) {
          $pid      = $rowOrd['product_id'];
          $p_name   = getDataBack($conn, 'tbl_product', 'id', $pid, 'name');
          $p_price  = getDataBack($conn, 'tbl_product', 'id', $pid, 'price');
          $quantity = $rowOrd['quantity'];
          $discount = $rowOrd['discount'] ?? 0;

          // Calculate total for this item
          $line_total = ($p_price * $quantity) - $discount;
          $subtotal += $p_price * $quantity; // Add to subtotal (before discount)
          $total += $line_total; // Add final amount after discount
          $total_discount += $discount; // Add item discount
          $tot_qnty += $quantity;
      ?>
      <tr>
        <td><?= $quantity ?></td>
        <td><?= $p_name ?></td>
        <td>Rs <?= number_format($p_price) ?>/-</td>
        <td>Rs <?= number_format($discount) ?>/-</td>
        <td>Rs <?= number_format($line_total) ?>/-</td>
      </tr>
      <?php } } ?>
    </tbody>
  </table>

  <div class="totals">
    <div><strong>Total Quantity:</strong> <?= $tot_qnty ?></div>
    <div><strong>Subtotal:</strong> Rs <?= number_format($subtotal) ?>/-</div>

    <?php if ($total_discount > 0 || $discount_price_bill > 0) { ?>
      <div><strong>Total Discount (Items):</strong> Rs <?= number_format($total_discount) ?>/-</div>
      <div><strong>Bill Discount:</strong> Rs <?= number_format($discount_price_bill) ?>/-</div>
      <?php
      $total_discount += $discount_price_bill; // Finally add bill discount to total discount
      ?>
      <div><strong>Final Discount:</strong> Rs <?= number_format($total_discount) ?>/-</div>
    <?php } ?>

    <div><strong>Final Total:</strong> Rs <?= number_format($total - $discount_price_bill) ?>/-</div>
  </div>

  <div class="footer">
    <p>Exchange of any item in its original condition with receipt is possible within 7 days.</p>
    <p><strong>Thank you! Come again.</strong></p>
  </div>

  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
        window.onafterprint = function() {
          window.close();
        };
      }, 500);
    };
  </script>
</body>
</html>
