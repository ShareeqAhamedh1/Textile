<?php include './backend/conn.php'; ?>

<?php
  if (isset($_REQUEST['sel_date_f'])) {
    $date_sel_one = $_REQUEST['sel_date_f'];
    $date_sel_two = $_REQUEST['sel_date_t'];
  } else {
    $date_sel_one = date("Y-m-d");
    $date_sel_two = date("Y-m-d");
  }

  $prod_id = array();

  // Query to get distinct product IDs in the specified date range
  $sql_order_pos = "SELECT DISTINCT product_id FROM tbl_order WHERE DATE(bill_date) BETWEEN '$date_sel_one' AND '$date_sel_two'";
  $rs_order_pos = $conn->query($sql_order_pos);

  if ($rs_order_pos->num_rows > 0) {
    while ($rowOrderpos = $rs_order_pos->fetch_assoc()) {
      $prod_id[] = $rowOrderpos['product_id'];
    }
  }

  // Remove duplicates from the product IDs array
  $prod_id = array_unique($prod_id);

  // Prepare for storing sales data by product and date
  $sales_data = array();

  // Query for the sales data of all products in the date range
  $sql_sales_data = "
    SELECT product_id, DATE(bill_date) AS sale_date, SUM(quantity) AS total_quantity
    FROM tbl_order
    WHERE DATE(bill_date) BETWEEN '$date_sel_one' AND '$date_sel_two'
    GROUP BY product_id, sale_date
  ";
  $rs_sales_data = $conn->query($sql_sales_data);

  // Organize sales data by product_id and date
  while ($row_sales = $rs_sales_data->fetch_assoc()) {
    $sales_data[$row_sales['product_id']][$row_sales['sale_date']] = $row_sales['total_quantity'];
  }
?>

<div class="card-header pb-0 d-flex justify-content-between align-items-center">
   <h5 class="card-title mb-0">Sales Report From <?= $date_sel_one ?> To <?= $date_sel_two ?></h5>
</div>

<table class="table datatable" id="sales_report_uni_id">
  <thead>
    <tr>
      <th>Product Name</th>
      <?php
        // Loop to generate date headers
        $start_date = new DateTime($date_sel_one);
        $end_date = new DateTime($date_sel_two);
        $current_date = clone $start_date;
        while ($current_date <= $end_date) {
      ?>
        <th><?= $current_date->format('Y-m-d') ?></th>
      <?php
          $current_date->add(new DateInterval('P1D'));
        }
      ?>
    </tr>
  </thead>
  <tbody>
    <?php
    foreach ($prod_id as $id_call) {
      // Get product name
      $p_name = getDataBack($conn, 'tbl_product', 'id', $id_call, 'name');
    ?>
      <tr>
        <td><?= $p_name ?></td>

        <?php
        // Loop through each date in the selected range
        $start_date = new DateTime($date_sel_one);
        $end_date = new DateTime($date_sel_two);
        $current_date = clone $start_date;

        while ($current_date <= $end_date) {
          $inc_date = $current_date->format('Y-m-d');

          // Check if there is sales data for the current product on the current date
          $qnty = isset($sales_data[$id_call][$inc_date]) ? $sales_data[$id_call][$inc_date] : 0;
          echo "<td>$qnty</td>";

          // Move to the next date
          $current_date->add(new DateInterval('P1D'));
        }
        ?>
      </tr>
    <?php } ?>
  </tbody>
</table>

<script type="text/javascript">
  // Define total quantity and total sales variables dynamically if needed
  let total_quantity = 0; // Calculate the total quantity dynamically if needed
  let total_sales = 0; // Calculate the total sales dynamically if needed

  document.getElementById('tot_qnty').innerHTML = total_quantity;
  document.getElementById('tot_sales').innerHTML = "Rs." + total_sales.toLocaleString() + "/-";
</script>
