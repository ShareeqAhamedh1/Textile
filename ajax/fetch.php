<?php
include '../backend/conn.php';

if(isset($_POST['customer_id'])){
    $customer_id = $_POST['customer_id'];

    $customerPaid =0;
    $totalCredit =0;
    $sqlPaid = "SELECT * FROM tbl_customer_payments WHERE c_id='$customer_id'";
    $rsPaid  = $conn->query($sqlPaid);
    if($rsPaid->num_rows > 0){
      while($rowPaid = $rsPaid->fetch_assoc()){
        $customerPaid  +=$rowPaid['cp_amount'];
      }
    }

    $sql_orders = "SELECT og.id, og.order_ref, og.order_date, og.payment_type, og.discount_price
                   FROM tbl_order_grm og
                   WHERE og.customer_id = '$customer_id'";
    $result_orders = mysqli_query($conn, $sql_orders);

    if(mysqli_num_rows($result_orders) > 0){
        echo '<table class="table table-bordered">';
        echo '<thead class="thead-light"><tr><th>Order Ref</th><th>Date</th><th>Payment Type</th><th>Total Bill (LKR)</th></tr></thead>';
        echo '<tbody>';

        while($order = mysqli_fetch_assoc($result_orders)){
            $order_id = $order['id'];
            $total = 0;

            $sql_items = "SELECT o.quantity, o.discount, p.price
                          FROM tbl_order o
                          JOIN tbl_product p ON o.product_id = p.id
                          WHERE o.grm_ref = '$order_id'";
            $result_items = mysqli_query($conn, $sql_items);

            if(mysqli_num_rows($result_items) > 0){
                while($item = mysqli_fetch_assoc($result_items)){
                    $line_total = ($item['quantity'] * $item['price']) - $item['discount'];
                    $total += $line_total;
                }
            }
            $total -= $order['discount_price'];

            $payment_types = ["Cash", "Online Pay", "Bank Transfer", "Credit"];
            $payment_type_text = $payment_types[$order['payment_type']];
            if($payment_type_text =="Credit"){
              $totalCredit +=$total;
            }

            echo "<tr>
                    <td>{$order['order_ref']}</td>
                    <td>{$order['order_date']}</td>
                    <td>{$payment_type_text}</td>
                    <td>" . number_format($total, 2) . "</td>
                  </tr>";
        }
        ?>
        <tr class="table-warning">
  <td colspan="3" class="text-left font-weight-bold">Customer Total Credit:</td>
  <td class="text-danger font-weight-bold">LKR <?= number_format($totalCredit, 2) ?></td>
</tr>
<tr class="table-success">
  <td colspan="3" class="text-left font-weight-bold">Customer Total Paid:</td>
  <td class="text-success font-weight-bold">LKR <?= number_format($customerPaid, 2) ?></td>
</tr>
<tr class="table-info">
  <td colspan="3" class="text-left font-weight-bold">Customer Credit Balance:</td>
  <td class="text-primary font-weight-bold">LKR <?= number_format($totalCredit - $customerPaid, 2) ?></td>
</tr>

        <?php

        echo '</tbody></table>';
    } else {
        echo '<p class="text-center text-muted">No orders found.</p>';
    }
}
?>
