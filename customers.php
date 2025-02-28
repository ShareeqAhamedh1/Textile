<?php
include 'backend/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Report</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
  <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">


</head>
<body>
  <div class="container mt-5">
    <a href="pos_grm.php" class="btn btn-outline-dark btn-lg px-4 py-2 fw-bold shadow-sm">
       <i class="fas fa-dashboard me-2"></i> Dashboard
    </a>
    <h2 class="mb-4">Customer Report</h2>

    <!-- Customer Payments Button -->
    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#paymentModal">
      Customer Payments
    </button>

    <table class="table table-bordered table-striped">
      <thead class="thead-dark">
        <tr>
          <th>#</th>
          <th>Customer Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>City</th>
          <th>Credit Amount (LKR)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT * FROM tbl_customer";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0){
          $i = 1;
          while($row = mysqli_fetch_assoc($result)){
            $customer_id = $row['c_id'];
            $credit = 0;

            $customerPaid =0;
            $sqlPaid = "SELECT * FROM tbl_customer_payments WHERE c_id='$customer_id'";
            $rsPaid  = $conn->query($sqlPaid);
            if($rsPaid->num_rows > 0){
              while($rowPaid = $rsPaid->fetch_assoc()){
                $customerPaid  +=$rowPaid['cp_amount'];
              }
            }

            $sql_credit = "SELECT og.id, og.discount_price
                           FROM tbl_order_grm og
                           WHERE og.customer_id = '$customer_id' AND og.payment_type = 3";
            $result_credit = mysqli_query($conn, $sql_credit);

            if(mysqli_num_rows($result_credit) > 0){
              while($order = mysqli_fetch_assoc($result_credit)){
                $order_id = $order['id'];
                $order_total = 0;

                $sql_order = "SELECT o.quantity, o.discount, p.price
                              FROM tbl_order o
                              JOIN tbl_product p ON o.product_id = p.id
                              WHERE o.grm_ref = '$order_id'";
                $result_order = mysqli_query($conn, $sql_order);

                if(mysqli_num_rows($result_order) > 0){
                  while($item = mysqli_fetch_assoc($result_order)){
                    $line_total = ($item['quantity'] * $item['price']) - $item['discount'];
                    $order_total += $line_total;
                  }
                }
                $order_total -= $order['discount_price'];
                $credit += $order_total;

              }
            }
            ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['c_name']); ?></td>
              <td><?php echo htmlspecialchars($row['c_phone']); ?></td>
              <td><?php echo htmlspecialchars($row['c_email']); ?></td>
              <td><?php echo htmlspecialchars($row['c_city']); ?></td>
              <td><?php echo number_format($credit-$customerPaid, 2); ?></td>
              <td>
                <button class="btn btn-primary btn-sm view-orders" data-id="<?php echo $customer_id; ?>" data-name="<?php echo htmlspecialchars($row['c_name']); ?>">
                  View Orders
                </button>
              </td>
            </tr>
            <?php
          }
        } else {
          echo "<tr><td colspan='7' class='text-center'>No customers found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <!-- CUSTOMER PAYMENT MODAL -->
  <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Customer Payment</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="paymentForm">
            <div class="form-group">
              <label>Select Customer</label><br>
              <select class="form-control select2 w-100" name="customer_id" id="customerSelect" required>
                <option value="">Select Customer</option>
                <?php
                $sql_customers = "SELECT c_id, c_name FROM tbl_customer";
                $result_customers = mysqli_query($conn, $sql_customers);
                while($customer = mysqli_fetch_assoc($result_customers)){
                  echo "<option value='".$customer['c_id']."'>".$customer['c_name']."</option>";
                }
                ?>
              </select>
            </div>
            <div class="form-group">
              <label>Description</label>
              <input type="text" class="form-control" name="cp_description">
            </div>
            <div class="form-group">
              <label>Amount Received (LKR)</label>
              <input type="number" class="form-control" name="cp_amount" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-success">Submit Payment</button>
          </form>
          <div id="paymentMessage" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- AJAX for Customer Payment -->
  <!-- MODAL -->
  <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="orderModalLabel">Customer Orders</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <h5>Customer: <span id="customerName"></span></h5>
          <div id="orderDetails">
            <p class="text-center text-muted">Loading orders...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- AJAX -->


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Bootstrap JS -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

  <script>
    $(document).ready(function(){
      $(".view-orders").click(function(){
        var customerId = $(this).data("id");
        var customerName = $(this).data("name");
        $("#customerName").text(customerName);
        $("#orderDetails").html("<p class='text-center text-muted'>Loading orders...</p>");

        $.ajax({
          url: "ajax/fetch.php",
          type: "POST",
          data: { customer_id: customerId },
          success: function(response){
            $("#orderDetails").html(response);
          }
        });

        $("#orderModal").modal("show");
      });
    });
  </script>
<script>
  $(document).ready(function(){
    $(".select2").select2();

    $("#paymentForm").submit(function(e){
      e.preventDefault();
      $.ajax({
        url: "backend/save_payment.php",
        type: "POST",
        data: $(this).serialize(),
        success: function(response){
          $("#paymentMessage").html(response);
          $("#paymentForm")[0].reset();
          $(".select2").val(null).trigger("change"); // Reset Select2
        }
      });
    });
  });
</script>
</body>
</html>
