<?php
include 'layouts/header.php';
include 'layouts/sidebar.php';

// Initialize variables
$opening_balance_display = 0;
$currDate=date('Y-m-d');
// Fetch yesterday's closing balance as today's opening balance
$sql_opening_balance = "SELECT * FROM tbl_expenses WHERE expense_date = '$currDate' AND category='Opening Balance'";
$rs_opening_balance = $conn->query($sql_opening_balance);
if ($rs_opening_balance->num_rows > 0) {
    $row = $rs_opening_balance->fetch_assoc();
    $opening_balance_display = $row['amount'] ?? 0;
}

$total_daily = 0;
$closing_balance = 0;
$opening_balance = 0;

// Fetch yesterday's closing balance as today's opening balance
$sql_opening_balance = "SELECT SUM(amount * (CASE WHEN cash_in_out = 1 THEN 1 ELSE -1 END)) AS closing_balance FROM tbl_expenses WHERE DATE(expense_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$rs_opening_balance = $conn->query($sql_opening_balance);
if ($rs_opening_balance->num_rows > 0) {
    $row = $rs_opening_balance->fetch_assoc();
    $opening_balance = $row['closing_balance'] ?? 0;
}

// SQL for total expenses today
$sql_daily = "SELECT SUM(amount) AS total FROM tbl_expenses WHERE DATE(expense_date) = CURDATE() AND cash_in_out=2";
$rs_daily = $conn->query($sql_daily);
$total_daily = ($rs_daily->num_rows > 0) ? $rs_daily->fetch_assoc()['total'] : 0;

// Fetch today's expenses only
$sql_expenses = "SELECT e.*, v.vendor_name, e.cash_in_out FROM tbl_expenses e LEFT JOIN tbl_vendors v ON e.vendor_id = v.vendor_id WHERE DATE(e.expense_date) = CURDATE() ORDER BY e.expense_date DESC";
$rs_expenses = $conn->query($sql_expenses);

// Calculate closing balance
$closing_balance = $opening_balance;
if ($rs_expenses->num_rows > 0) {
    while ($row = $rs_expenses->fetch_assoc()) {
        if ($row['cash_in_out'] == 1) {
            $closing_balance += $row['amount'];
        } elseif ($row['cash_in_out'] == 2) {
            $closing_balance -= $row['amount'];
        }
    }
}
?>

<div class="page-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title">Opening Balance</h5>
                        <h3>Rs. <?= number_format($opening_balance_display, 2) ?>/-</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Daily Expenses</h5>
                        <h3>Rs. <?= number_format($total_daily, 2) ?>/-</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
    <div class="col-lg-6 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Add New Expense</h4>
            </div>
            <div class="card-body">
                <form action="backend/addExpence.php" method="POST">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-control" onchange="updateCashInOut(this.value)" required>
                            <option value="">Select Type</option>
                            <option value="Opening Balance">Opening Balance</option>
                            <option value="petty_cash">Petty Cash</option>
                            <option value="vendor">Vendor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description_petty" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="cash_in_out" class="form-label">Cash IN/OUT</label>
                        <select name="cash_in_out" id="cash_in_out" class="form-control" required>
                            <option value="2">CASH OUT</option>
                            <option value="1">CASH IN</option>
                        </select>
                    </div>

                    <!-- Vendor Dropdown -->
                    <div class="mb-3" id="vendorDropdown" style="display:none;">
                        <label for="vendor_id" class="form-label">Select Vendor</label>
                        <select name="vendor_id" id="vendor_id" class="form-control">
                            <?php
                            // Fetch all vendors
                            $sql_vendors = "SELECT * FROM tbl_vendors";
                            $result_vendors = $conn->query($sql_vendors);
                            if ($result_vendors->num_rows > 0) {
                                while ($vendor = $result_vendors->fetch_assoc()) {
                                    echo "<option value='" . $vendor['vendor_id'] . "'>" . $vendor['vendor_name'] . "</option>";
                                }
                            } else {
                                echo "<option value=''>No Vendors Available</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Payment Type Dropdown -->
                    <div class="mb-3" id="paymentTypeDropdown" style="display:none;">
                        <label for="payment_type" class="form-label">Select Payment Type</label>
                        <select name="payment_type" id="payment_type" class="form-control">
                            <option value="">-- Select Payment Type --</option>
                            <option value="cash">Cash</option>
                            <option value="onlinePayment">Online Payment</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Expense Date</label>
                        <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Add Expense</button>
                </form>
            </div>
        </div>
    </div>
</div>


        <!-- Filter by Date or Month -->
        <div class="row mt-4">
    <div class="col-md-4">
        <label for="filter_from" class="form-label">From</label>
        <input type="date" id="filter_from" class="form-control" value="<?= $filter_from ?>">
    </div>
    <div class="col-md-4">
        <label for="filter_to" class="form-label">To</label>
        <input type="date" id="filter_to" class="form-control" value="<?= $filter_to ?>">
    </div>
    <div class="col-md-4">
        <label for="filter_month" class="form-label">Select Month</label>
        <input type="month" id="filter_month" class="form-control" value="<?= $filter_month ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button id="filterBtn" class="btn btn-primary me-2">Search</button>
        <button id="clearFilter" class="btn btn-secondary">Reset</button>
    </div>
</div>
        <!-- Expense List -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Today's Expenses</h4>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rs_expenses->data_seek(0); // Reset pointer to fetch again ?>
                                <?php if ($rs_expenses->num_rows > 0): ?>
                                    <?php while ($rows = $rs_expenses->fetch_assoc()): ?>
                                        <tr>
                                            <td>Rs. <?= number_format($rows['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($rows['description'], ENT_QUOTES) ?></td>
                                            <td><span class="badge bg-info"> <?= ucfirst($rows['category']) ?></span></td>
                                            <td><?= date('Y-m-d', strtotime($rows['expense_date'])) ?></td>
                                            <td><?= ($rows['category'] == 'vendor' && $rows['vendor_name']) ? $rows['vendor_name'] : "-" ?></td>
                                            <td>
                                                <?php if ($rows['cash_in_out'] == 1): ?>
                                                    <span class="badge bg-success">CASH IN</span>
                                                <?php elseif ($rows['cash_in_out'] == 2): ?>
                                                    <span class="badge bg-danger">CASH OUT</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">UNKNOWN</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <td colspan="5" class="text-end"><strong>Closing Balance:</strong></td>
                                    <td><strong>Rs. <?= number_format($closing_balance, 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExpenseModalLabel">Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editExpenseForm">
                <div class="modal-body">
                    <input type="hidden" id="expense_id" name="expense_id">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="text" class="form-control" id="amount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="vendor">Vendor</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="expense_date" name="expense_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="vendor_name" class="form-label">Vendor Name</label>
                        <input type="text" class="form-control" id="vendor_name" name="vendor_name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php include 'layouts/footer.php'; ?>


<script type="text/javascript">
$(document).ready(function () {
    // Open modal and populate data
    $(".editExpenseBtn").click(function () {
        let expenseId = $(this).data("id");
        let amount = $(this).data("amount");
        let description = $(this).data("description");
        let category = $(this).data("category");
        let date = $(this).data("date");
        let vendor = $(this).data("vendor");

        $("#expense_id").val(expenseId);
        $("#amount").val(amount);
        $("#description").val(description);
        $("#category").val(category);
        $("#expense_date").val(date);
        $("#vendor_name").val(vendor);

        $("#editExpenseModal").modal("show");
    });

    // Submit form via AJAX (Optional)
    $("#editExpenseForm").submit(function (e) {
        e.preventDefault();

        $.ajax({
    type: "POST",
    url: "backend/update_expense.php",
    data: $("#editExpenseForm").serialize(),
    success: function (response) {
        try {
            let res = JSON.parse(response.trim()); // Trim any unwanted whitespace
            console.log(res); // Debugging: Log response

            if (res.statusCode === 400) {
                alert(res.message);
            } else if (res.statusCode === 500) {
                alert(res.message); // Shows SQL or DB error
            } else {
                alert("Expense Updated Successfully!");
                location.reload(); // Reload page to reflect changes
            }
        } catch (error) {
            console.error("Invalid JSON Response:", response);
            alert("Error: Invalid response from server.");
        }
    },
    error: function (xhr, status, error) {
        console.error("AJAX Error:", status, error);
        alert("Request failed. Please try again.");
    }
});

    });
});
</script>

<script>


   document.getElementById("filterBtn").addEventListener("click", function () {
    var filterFrom = document.getElementById("filter_from").value;
    var filterTo = document.getElementById("filter_to").value;
    var filterMonth = document.getElementById("filter_month").value;

    var url = "?";
    if (filterFrom) url += "filter_from=" + filterFrom + "&";
    if (filterTo) url += "filter_to=" + filterTo + "&";
    if (filterMonth) url += "filter_month=" + filterMonth + "&";

    window.location.href = url.slice(0, -1); // Remove last '&'
});

document.getElementById("clearFilter").addEventListener("click", function () {
    window.location.href = "manage_expenses.php";
});
    //     // Filter button click event
    //     document.getElementById("filterBtn").addEventListener("click", function () {
    //     var filterDate = document.getElementById("filter_date").value;
    //     window.location.href = "?filter_date=" + filterDate;
    // });

    // // Clear filter button click event
    // document.getElementById("clearFilter").addEventListener("click", function () {
    //     window.location.href = "expenses.php";
    // });

    document.getElementById("category").addEventListener("change", function () {
    const vendorDropdown = document.getElementById("vendorDropdown");
    const paymentTypeDropdown = document.getElementById("paymentTypeDropdown");

    if (this.value === "vendor") {
        vendorDropdown.style.display = "block";
        paymentTypeDropdown.style.display = "block";
        // Add required attributes
        document.querySelector("[name='vendor_id']").required = true;
        document.querySelector("[name='payment_type']").required = true;
    } else {
        vendorDropdown.style.display = "none";
        paymentTypeDropdown.style.display = "none";
        // Remove required attributes
        document.querySelector("[name='vendor_id']").required = false;
        document.querySelector("[name='payment_type']").required = false;
    }
});


    function del_expense(id) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    method: "POST",
                    url: "./backend/deleteExpense.php",
                    data: { exp_id: id },
                    success: function(response) {
                        var dataResult = JSON.parse(response);
                        if (dataResult.statusCode == 200) {
                            Swal.fire({
                                icon: "success",
                                title: "Deleted!",
                                text: "Your expense has been deleted.",
                                confirmButtonClass: "btn btn-success"
                            }).then(() => {
                                location.reload(); // Refresh the page
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error!",
                                text: "Failed to delete expense.",
                                confirmButtonClass: "btn btn-danger"
                            });
                        }
                    }
                });
            }
        });
    }
    function updateDescription(inputValue) {
      if(inputValue == "Opening Balance"){
        let todayDate = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
        document.getElementById('description_petty').value = inputValue + " - " + todayDate;
      }
      else {
        document.getElementById('description_petty').value = "";
      }
}

function updateCashInOut(value) {
    var cashInOutSelect = document.getElementById("cash_in_out");
    var descriptionInput = document.getElementById("description_petty");

    if (value === "Opening Balance") {
        cashInOutSelect.value = "1"; // Select CASH IN
        let todayDate = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
        descriptionInput.value = value + " - " + todayDate; // Use 'value' instead of 'inputValue'
    } else {
        cashInOutSelect.value = "2"; // Select CASH OUT
        descriptionInput.value = ""; // Clear description for other categories
    }
}

</script>
