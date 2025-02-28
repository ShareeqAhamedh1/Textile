<?php  
include './backend/conn.php'; 

// Handle date selection
if (isset($_REQUEST['sel_date_f'])) {
    $date_sel_one = $_REQUEST['sel_date_f'];
    $date_sel_two = $_REQUEST['sel_date_t'];
} else {
    $date_sel_one = date("Y-m-d");
    $date_sel_two = date("Y-m-d");
}

// Fetch orders with product details
$sql_order_pos = "SELECT o.*, p.name AS product_name 
                  FROM tbl_order o
                  JOIN tbl_product p ON o.product_id = p.id
                  WHERE DATE(o.bill_date) BETWEEN '$date_sel_one' AND '$date_sel_two' 
                  ORDER BY o.bill_date DESC";

$rs_order_pos = $conn->query($sql_order_pos);

// Initialize Totals
$tot_bill = 0;
$tot_bill_dis = 0;
$tot_qnty = 0;

?>

<div class="row">
    <div class="col-4 d-flex">
        <div class="dash-count">
            <div class="dash-counts">
                <h4 id="tot_qnty"></h4>
                <h5>Total Quantity</h5>
                <p>(From <?= htmlspecialchars($date_sel_one) ?> To <?= htmlspecialchars($date_sel_two) ?>) </p>
            </div>
            <div class="dash-imgs">
                <i data-feather="activity"></i>
            </div>
        </div>
    </div>
    <div class="col-4 d-flex">
        <div class="dash-count das1">
            <div class="dash-counts">
                <h4 id="tot_sales"></h4>
                <h5>Total Sales Value</h5>
                <p>(From <?= htmlspecialchars($date_sel_one) ?> To <?= htmlspecialchars($date_sel_two) ?>) </p>
            </div>
            <div class="dash-imgs">
                <i data-feather="dollar-sign"></i>
            </div>
        </div>
    </div>
    <div class="col-4 d-flex">
        <div class="dash-count das2">
            <div class="dash-counts">
                <h4 id="tot_sales_b_discount"></h4>
                <h5>Total Sales Value Before Discount</h5>
                <p>(From <?= htmlspecialchars($date_sel_one) ?> To <?= htmlspecialchars($date_sel_two) ?>) </p>
            </div>
            <div class="dash-imgs">
                <i data-feather="dollar-sign"></i>
            </div>
        </div>
    </div>
</div>

<div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Sales Report From <?= htmlspecialchars($date_sel_one) ?> To <?= htmlspecialchars($date_sel_two) ?></h5>
</div>

<table class="table datatable" id="sales_report_id">
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Unit Price</th>
            <th>After Discount</th>
            <th>Total Sold Qnty</th>
            <th>Total Value</th>
            <th>Bill Date</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($rs_order_pos->num_rows > 0) {
            while ($row_order_pos = $rs_order_pos->fetch_assoc()) {
                $product_name = htmlspecialchars($row_order_pos['product_name']);
                $quantity = (int) $row_order_pos['quantity'];
                $unit_price = (float) $row_order_pos['m_price'];
                $discount = (float) $row_order_pos['discount'];
                $discount_type = $row_order_pos['discount_type'];
                $bill_date = date("Y-m-d", strtotime($row_order_pos['bill_date']));

                // Calculate Discounted Price
                $discount_amount = ($discount_type == "p") ? ($unit_price * $discount) / 100 : $discount;
                $final_price = max(0, $unit_price - $discount_amount);

                // Totals Calculation
                $tot_qnty += $quantity;
                $tot_bill_dis += $quantity * $final_price;
                $tot_bill += $quantity * $unit_price;
        ?>
                <tr>
                    <td><?= $product_name ?></td>
                    <td>Rs.<?= number_format($unit_price, 2) ?>/-</td>
                    <td>Rs.<?= number_format($final_price, 2) ?>/-</td>
                    <td><?= $quantity ?></td>
                    <td>Rs.<?= number_format($quantity * $final_price, 2) ?>/-</td>
                    <td><?= $bill_date ?></td>
                </tr>
        <?php
            }
        } else { ?>
            <tr>
                <td colspan="6" class="text-center">
                    <strong style="font-size: 25px;">NO DATA FOUND</strong>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<script type="text/javascript">
    document.getElementById('tot_qnty').innerHTML = "<?= number_format($tot_qnty) ?>";
    document.getElementById('tot_sales').innerHTML = "Rs.<?= number_format($tot_bill_dis, 2) ?>/-";
    document.getElementById('tot_sales_b_discount').innerHTML = "Rs.<?= number_format($tot_bill, 2) ?>/-";
</script>
