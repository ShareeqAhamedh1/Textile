<?php
include 'layouts/header.php';
include 'layouts/sidebar.php';

$u_id=$_SESSION['u_id'];
// Pagination Variables
$limit = 5; // Products per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get Total Products Count
$sql_count = "SELECT COUNT(*) AS total FROM tbl_product";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

// Fetch Products with Pagination
$sql = "SELECT name AS product_name, price FROM tbl_product ORDER BY id DESC LIMIT $offset, $limit";
$rs = $conn->query($sql);

// Calculate Total Stock Value
$stock_value = 0;
$sql_products = "SELECT id, price FROM tbl_product";
$rs_prod = $conn->query($sql_products);
while ($rowProd = $rs_prod->fetch_assoc()) {
    $p_id = $rowProd['id'];
    $stock_value += $rowProd['price'] * currentStockCount($conn, $p_id);
}

// Initialize Variables to Prevent Undefined Errors
$tot_bill_dis_today = 0;
$total_price_before_discount = 0;
$total_item_discount = 0;
$total_bill_discount = 0;
$processed_grm_refs = [];

$today_date = date("Y-m-d");

// Fetch Today's Sales Total
$sql_today_sales = "
    SELECT o.grm_ref, o.product_id, o.quantity, o.discount, p.price, g.discount_price
    FROM tbl_order o
    JOIN tbl_product p ON o.product_id = p.id
    JOIN tbl_order_grm g ON o.grm_ref = g.id
    WHERE DATE(g.order_date) = '$today_date'";

$rs_today_sales = $conn->query($sql_today_sales);

while ($row = $rs_today_sales->fetch_assoc()) {
    $grm_ref = $row['grm_ref'];
    $quantity = $row['quantity'];
    $price = $row['price'];
    $discount = $row['discount'];
    $bill_discount = $row['discount_price'];

    // Calculate total price BEFORE discount
    $total_price_before_discount += ($quantity * $price);

    // Accumulate total item discount directly
    $total_item_discount += $discount;

    // Ensure bill discount is applied only once per grm_ref
    if (!isset($processed_grm_refs[$grm_ref])) {
        $total_bill_discount += $bill_discount;
        $processed_grm_refs[$grm_ref] = true;
    }
}

// Final Calculation
$tot_bill_dis_today = $total_price_before_discount - ($total_item_discount + $total_bill_discount);

// Fetch Total Sales Value
$tot_bill_dis = 0;
$sql_orders = "
    SELECT o.grm_ref, o.quantity, o.discount, p.price, g.discount_price
    FROM tbl_order o
    JOIN tbl_product p ON o.product_id = p.id
    JOIN tbl_order_grm g ON o.grm_ref = g.id";

$rs_orders = $conn->query($sql_orders);
$order_totals = [];
$processed_orders = [];

while ($row_order = $rs_orders->fetch_assoc()) {
    $grm_ref = $row_order['grm_ref'];
    $quantity = $row_order['quantity'];
    $price = $row_order['price'];
    $discount = $row_order['discount'];
    $bill_discount = $row_order['discount_price'];

    if (!isset($order_totals[$grm_ref])) {
        $order_totals[$grm_ref] = ['total_price' => 0, 'discount' => 0, 'bill_discount' => 0];
    }

    $order_totals[$grm_ref]['total_price'] += ($quantity * $price);
    $order_totals[$grm_ref]['discount'] += $discount;

    if (!isset($processed_orders[$grm_ref])) {
        $order_totals[$grm_ref]['bill_discount'] += $bill_discount;
        $processed_orders[$grm_ref] = true;
    }
}

// Final Discount Calculation
foreach ($order_totals as $order) {
    $total_price = $order['total_price'];
    $discount = $order['discount'];
    $bill_discount = $order['bill_discount'];

    $tot_bill_dis += ($total_price - ($discount + $bill_discount));
}

// Fetch Today's Expenses
$tot_expenses_today = 0;
$sql_today_expenses = "SELECT SUM(amount) AS total FROM tbl_expenses WHERE DATE(expense_date) = '$today_date' AND cash_in_out=2";
$rs_today_expenses = $conn->query($sql_today_expenses);
$row_expense = $rs_today_expenses->fetch_assoc();
$tot_expenses_today = $row_expense['total'] ?? 0;

// Calculate Till Balance for Today
$till_balance_today = $tot_bill_dis_today - $tot_expenses_today;

// Fetch Payment Breakdown for Today (Fixed Calculation)
$total_payments_today = [
    'cash' => 0,
    'online' => 0,
    'bank' => 0,
    'credit' => 0
];

$sql_payment_today = "
    SELECT
        g.payment_type,
        SUM((p.price * o.quantity) - o.discount - g.discount_price) AS total_received
    FROM tbl_order o
    JOIN tbl_order_grm g ON g.id = o.grm_ref
    JOIN tbl_product p ON o.product_id = p.id
    WHERE DATE(g.order_date) = '$today_date'
    GROUP BY g.payment_type";

$rs_payment_today = $conn->query($sql_payment_today);
while ($row = $rs_payment_today->fetch_assoc()) {
    switch ($row['payment_type']) {
        case 0: $total_payments_today['cash'] = $row['total_received']; break;
        case 1: $total_payments_today['online'] = $row['total_received']; break;
        case 2: $total_payments_today['bank'] = $row['total_received']; break;
        case 3: $total_payments_today['credit'] = $row['total_received']; break;
    }
}

// Fetch Total Cost of All Products (Fixed Undefined Variable)
$total_cost_price = 0;
$sql_total_cost = "
    SELECT p.id, p.cost_price,
           COALESCE(SUM(e.quantity), 0) AS total_quantity
    FROM tbl_product p
    LEFT JOIN tbl_expiry_date e ON p.id = e.product_id
    GROUP BY p.id, p.cost_price";

$rs_total_cost = $conn->query($sql_total_cost);
while ($row = $rs_total_cost->fetch_assoc()) {
    $total_cost_price += $row['cost_price'] * $row['total_quantity'];
}

// Calculate Till Balance Including Payments
$till_balance = $total_payments_today['cash'] - $tot_expenses_today;

?>



<style>
    .dashboard-card {
        transition: transform 0.3s ease;
        border: none;
        border-radius: 15px;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    .card-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 24px;
    }
    .metric-title {
        font-size: 0.9rem;
        color: #6c757d;
    }
    .metric-value {
        font-size: 1.5rem;
        font-weight: 600;
    }
    .section-title {
        font-weight: 600;
        color: #2c3e50;
        border-left: 4px solid #007bff;
        padding-left: 1rem;
        margin: 1.5rem 0;
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <!-- Dashboard Metrics Grid -->
        <div class="row g-4">
            <?php if($u_id == 1): ?>
            <!-- Financial Overview Section -->
            <div class="col-12">
                <h4 class="section-title">Financial Overview</h4>
                <div class="row g-4">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-primary text-white me-3">
                                        <i class="ri-archive-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Total Stock Value</div>
                                        <div class="metric-value text-primary">
                                            Rs.<?= number_format($stock_value) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-success text-white me-3">
                                        <i class="ri-money-dollar-circle-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Total Cost</div>
                                        <div class="metric-value text-success">
                                            Rs.<?= number_format($total_cost_price) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-info text-white me-3">
                                        <i class="ri-line-chart-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Total Sales Value</div>
                                        <div class="metric-value text-info">
                                            Rs.<?= number_format($tot_bill_dis) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Overview Section -->
            <div class="col-12">
                <h4 class="section-title">Payments Overview</h4>
                <div class="row g-4">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-header bg-dark text-white">
                                <h6 class="mb-0">Cash Flow</h6>
                            </div>
                            <div class="card-body">
                                <div class="metric-title">Received via Cash</div>
                                <div class="metric-value">Rs.<?= number_format($total_payments_today['cash']) ?></div>
                                <div class="metric-title mt-2">Till Balance</div>
                                <div class="metric-value text-success">
                                    Rs.<?= number_format($till_balance) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-warning text-white me-3">
                                        <i class="ri-global-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Online Payments</div>
                                        <div class="metric-value text-warning">
                                            Rs.<?= number_format($total_payments_today['online']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-danger text-white me-3">
                                        <i class="ri-bank-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Bank Transfers</div>
                                        <div class="metric-value text-danger">
                                            Rs.<?= number_format($total_payments_today['bank']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Daily Summary Section -->
            <div class="col-12">
                <h4 class="section-title">Daily Summary</h4>
                <div class="row g-4">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-primary text-white me-3">
                                        <i class="ri-shopping-bag-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Today's Sales</div>
                                        <div class="metric-value text-primary">
                                            Rs.<?= number_format($tot_bill_dis_today) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-success text-white me-3">
                                        <i class="ri-money-dollar-circle-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Today's Expenses</div>
                                        <div class="metric-value text-success">
                                            Rs.<?= number_format($tot_expenses_today) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-center gap-3">
                            <a href="productlist.php" class="btn btn-primary btn-lg px-4">
                                <i class="ri-list-check me-2"></i>View Products
                            </a>
                            <a href="vendorlist.php" class="btn btn-secondary btn-lg px-4">
                                <i class="ri-store-line me-2"></i>View Vendors
                            </a>
                            <a href="manage_expenses.php" class="btn btn-warning btn-lg px-4">
                                <i class="ri-money-dollar-circle-line me-2"></i>Manage Expenses
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Products Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">Recently Added Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product Name</th>
                                        <th class="text-end">Price (Rs)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rs->num_rows > 0): ?>
                                        <?php while ($row = $rs->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                <td class="text-end"><?= number_format($row['price'], 2) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
