<?php
include 'layouts/header.php';
include 'layouts/sidebar.php';

// Initialize variables
$total_daily = 0;
$total_monthly = 0;
$closing_balance = 0;
$opening_balance = 0;

// Fetch yesterday's closing balance as today's opening balance
$sql_opening_balance = "SELECT closing_balance FROM tbl_closing_balance ORDER BY date DESC LIMIT 1";
$rs_opening_balance = $conn->query($sql_opening_balance);
if ($rs_opening_balance->num_rows > 0) {
    $opening_balance = $rs_opening_balance->fetch_assoc()['closing_balance'];
}

// SQL for total expenses today
$sql_daily = "SELECT SUM(amount) AS total FROM tbl_expenses WHERE DATE(expense_date) = CURDATE()";
$rs_daily = $conn->query($sql_daily);
$total_daily = ($rs_daily->num_rows > 0) ? $rs_daily->fetch_assoc()['total'] : 0;

// Fetch today's expenses only
$sql_expenses = "SELECT e.*, v.vendor_name FROM tbl_expenses e LEFT JOIN tbl_vendors v ON e.vendor_id = v.vendor_id WHERE DATE(e.expense_date) = CURDATE() ORDER BY e.expense_date DESC";
$rs_expenses = $conn->query($sql_expenses);
?>

<div class="page-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title">Opening Balance</h5>
                        <h3>Rs. <?= number_format($opening_balance, 2) ?>/-</h3>
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

        <!-- Expense List -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Today's Expenses</h4>
                    </div>
                    <div class="card-body">
                        <table class="table datanew">
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
                                <?php if ($rs_expenses->num_rows > 0): ?>
                                    <?php while ($rows = $rs_expenses->fetch_assoc()): ?>
                                        <?php
                                            if ($rows['cash_in_out'] == 1) {
                                                $closing_balance += $rows['amount'];
                                            } elseif ($rows['cash_in_out'] == 2) {
                                                $closing_balance -= $rows['amount'];
                                            }
                                        ?>
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
                                    <td><strong>Rs. <?= number_format($opening_balance + $closing_balance, 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
