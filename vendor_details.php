<?php
include './layouts/header.php';
include './layouts/sidebar.php';

if (!isset($_GET['vendor_id'])) {
    echo "<div class='alert alert-danger'>Vendor ID is missing.</div>";
    exit;
}

$vendor_id = intval($_GET['vendor_id']); // Prevent SQL injection
$filter_day = $_GET['day'] ?? '';
$filter_month = $_GET['month'] ?? '';



// Fetch vendor details
$sqlVendor = "SELECT vendor_name, phone, address FROM tbl_vendors WHERE vendor_id = ?";
$stmt = $conn->prepare($sqlVendor);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$stmt->bind_result($vendor_name, $phone, $address);
$stmt->fetch();
$stmt->close();
?>

<div class="page-wrapper">
    <div class="content">
        <div class="container">
            <div class="card p-4 shadow-lg">
                <h2 class="text-primary">Vendor Details</h2>
                <p><strong>Name:</strong> <?= htmlspecialchars($vendor_name) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($phone) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($address) ?></p>
            </div>

         

            <!-- Vendor Payments -->
            <div class="card p-4 shadow-lg mt-4">
    <h3 class="text-dark">Payments Made to Vendor</h3>

    <!-- JavaScript Filter: Select Date -->
    <div class="mb-3">
        <label for="paymentFilterDate" class="form-label">Filter by Payment Date:</label>
        <input type="date" id="paymentFilterDate" class="form-control">
    </div>

    <table class="table table-striped table-bordered" id="paymentsTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sqlPayments = "SELECT expense_date, amount, description FROM tbl_expenses WHERE vendor_id = ?";
            $stmt = $conn->prepare($sqlPayments);
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $stmt->bind_result($date, $amount, $description);

            $hasPayments = false;
            while ($stmt->fetch()) {
                $hasPayments = true;
            ?>
                <tr data-payment-date="<?= htmlspecialchars($date) ?>">
                    <td><?= htmlspecialchars($date) ?></td>
                    <td><?= number_format($amount, 0) ?></td>
                    <td><?= htmlspecialchars($description) ?></td>
                </tr>
            <?php }
            $stmt->close();

            if (!$hasPayments) {
                echo "<tr><td colspan='3' class='text-center text-danger'>No payments recorded.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

            <!-- Purchased Products -->
            <div class="card p-4 shadow-lg mt-4">
    <h3 class="text-dark">Purchased Products</h3>

    <!-- JavaScript Filter: Select Date -->
    <div class="mb-3">
        <label for="filterDate" class="form-label">Filter by Purchase Date:</label>
        <input type="date" id="filterDate" class="form-control">
    </div>

    <table class="table table-striped table-bordered" id="productsTable">
        <thead>
            <tr>
                <th>Barcode</th>
                <th>Category</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Cost</th>
                <th>Purchase Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sqlProducts = "SELECT 
            pu.purchase_id, 
            p.barcode, 
            pi.quantity, 
            pi.unit_price, 
            c.name AS category_name, 
            p.name AS product_name, 
            pu.purchase_date 
        FROM tbl_purchase_items pi
        INNER JOIN tbl_purchases pu ON pi.purchase_id = pu.purchase_id
        INNER JOIN tbl_product p ON pi.product_id = p.id
        LEFT JOIN tbl_category c ON p.category_id = c.id
        WHERE pu.vendor_id = ?
        ORDER BY pu.purchase_date DESC";
$stmt = $conn->prepare($sqlProducts);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$stmt->bind_result($purchase_id, $barcode, $quantity, $unit_price, $category_name, $product_name, $purchase_date);

$hasProducts = false;
while ($stmt->fetch()) {
    $hasProducts = true;
?>
    <tr data-date="<?= htmlspecialchars($purchase_date) ?>">
        <td><?= htmlspecialchars($barcode) ?></td>
        <td><?= htmlspecialchars($category_name) ?></td>
        <td><?= htmlspecialchars($product_name) ?></td>
        <td><?= htmlspecialchars($quantity) ?></td>
        <td><?= number_format($unit_price, 0) ?></td>
        <td><?= number_format($unit_price * $quantity, 0) ?></td>
        <td><?= htmlspecialchars($purchase_date) ?></td>
        <td>
            <?php if ($u_id == 1) { ?>
                <a class="me-3" href="editvendorpurchase.php?vendor_id=<?= $vendor_id ?>&purchase_id=<?= $purchase_id ?>">
                    <img src="assets/img/icons/edit.svg" alt="img">
                </a>
                <a onclick="del_purchase(<?= $vendor_id ?>)" class="me-3 confirm-text" href="javascript:void(0);">
                    <img src="assets/img/icons/delete.svg" alt="Delete">
                </a>
            <?php } 
          ?>
                    </td>
                </tr>
            <?php }
            $stmt->close();

            if (!$hasProducts) {
                echo "<tr><td colspan='7' class='text-center text-danger'>No products found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

            <div class="card p-4 shadow-lg mt-4">
                <h3 class="text-dark">Financial Summary</h3>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Total Purchases</th>
                            <th>Total Payments Made</th>
                            <th>Total Discounts</th>
                            <th>Net Amount Owed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        function fetchSum($conn, $sql, $vendor_id) {
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $vendor_id);
                            $stmt->execute();
                            $stmt->bind_result($sum);
                            $stmt->fetch();
                            $stmt->close();
                            return $sum ?? 0;
                        }

                        $total_purchases = fetchSum($conn, "SELECT SUM(total_amount) FROM tbl_purchases WHERE vendor_id = ?", $vendor_id);
                        $total_payments = fetchSum($conn, "SELECT SUM(amount) FROM tbl_expenses WHERE vendor_id = ? AND category = 'vendor'", $vendor_id);
                        $total_discounts = fetchSum($conn, "SELECT SUM(discount_amount) FROM tbl_vendor_discounts WHERE vendor_id = ?", $vendor_id);

                        $net_amount_owed = $total_purchases - $total_payments - $total_discounts;
                        ?>
                        <tr>
                            <td><?= number_format($total_purchases, 0) ?></td>
                            <td><?= number_format($total_payments, 0) ?></td>
                            <td><?= number_format($total_discounts, 0) ?></td>
                            <td><?= number_format($net_amount_owed,0) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include './layouts/footer.php'; ?>
<script>
    document.getElementById("filterDate").addEventListener("input", function () {
        let selectedDate = this.value;
        let rows = document.querySelectorAll("#productsTable tbody tr");

        rows.forEach(row => {
            let rowDate = row.getAttribute("data-date");
            if (!selectedDate || rowDate === selectedDate) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });

    document.getElementById("paymentFilterDate").addEventListener("input", function () {
        let selectedDate = this.value;
        let rows = document.querySelectorAll("#paymentsTable tbody tr");

        rows.forEach(row => {
            let rowDate = row.getAttribute("data-payment-date");
            if (!selectedDate || rowDate === selectedDate) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });

    function del_purchase(id) {
			// alert(id)
			Swal.fire({
				title: "Are you sure?",
				text: "You won't be able to revert this!",
				type: "warning",
				showCancelButton: !0,
				confirmButtonColor: "#3085d6",
				cancelButtonColor: "#d33",
				confirmButtonText: "Yes, delete it!",
				confirmButtonClass: "btn btn-primary",
				cancelButtonClass: "btn btn-danger ml-1",
				buttonsStyling: !1,
			}).then(function (t) {

				t.value &&
				$.ajax({
						method: "POST",
						url: "./backend/del_purchase.php",
						data:{prod_id: id},
						success: function(dataResult){
							var dataResult = JSON.parse(dataResult);
							if(dataResult.statusCode==200){
							}
						}
						});

				t.value &&
					Swal.fire({
						type: "success",
						title: "Deleted!",
						text: "Your file has been deleted.",
						confirmButtonClass: "btn btn-success",
					});



			});
		}
</script>