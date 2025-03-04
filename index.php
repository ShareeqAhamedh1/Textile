<?php
include 'layouts/header.php';
include 'layouts/sidebar.php';



$u_id = $_SESSION['u_id'];

// Pagination Variables
$limit  = 5; // Products per page
$page   = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get Total Products Count
$sql_count = "SELECT COUNT(*) AS total FROM tbl_product";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_records = $row_count['total'];
$total_pages   = ceil($total_records / $limit);

// Fetch Products with Pagination
$sql = "SELECT name AS product_name, price
        FROM tbl_product
        ORDER BY id DESC
        LIMIT $offset, $limit";
$rs = $conn->query($sql);

// 1) Calculate Total Stock Value (minus returns)
$stock_value = 0;

$sql_products = "SELECT id, price FROM tbl_product";
$rs_prod = $conn->query($sql_products);
while ($rowProd = $rs_prod->fetch_assoc()) {
    $p_id  = $rowProd['id'];
    $price = $rowProd['price'];

    // Normal stock calculation
    $stock_value += $price * currentStockCount($conn, $p_id);

    // Subtract the total monetary value of any returned items
    // for *that* product
    $returnValue = getReturnValue($conn, $p_id);
    $stock_value -= $returnValue;
}

// 2) Calculate Total Product Cost (minus returns)
$total_cost_price = 0;
$sql_total_cost = "
    SELECT p.id, p.cost_price,
           COALESCE(SUM(e.quantity), 0) AS total_quantity
    FROM tbl_product p
    LEFT JOIN tbl_expiry_date e ON p.id = e.product_id
    GROUP BY p.id, p.cost_price
";
$rs_total_cost = $conn->query($sql_total_cost);
while ($row = $rs_total_cost->fetch_assoc()) {
    $p_id       = $row['id'];
    $cost_price = $row['cost_price'];
    $qty        = $row['total_quantity'];

    // Total cost ignoring returns
    $productCost = $cost_price * $qty;

    // Subtract cost of returned items
    $returnedCost = getReturnCost($conn, $p_id);
    $productCost -= $returnedCost;
    if ($productCost < 0) {
        $productCost = 0;
    }

    $total_cost_price += $productCost;
}

// 3) Total Sales Value (minus returns + discount)
//    Instead of net_quantity approach in the query, we do raw sales - returns.
$tot_bill_dis = 0;
// First: sum up raw (quantity * price) from all orders
// plus item discount, bill discount
$sql_sales = "
    SELECT
        o.id,
        o.grm_ref,
        o.product_id,
        o.quantity,
        o.discount AS item_discount,
        p.price,
        g.discount_price AS bill_discount
    FROM tbl_order o
    JOIN tbl_product p ON o.product_id = p.id
    JOIN tbl_order_grm g ON o.grm_ref = g.id
";
$rs_sales = $conn->query($sql_sales);
$order_data   = [];
$processedRef = [];

while ($rowSale = $rs_sales->fetch_assoc()) {
    $grm_ref      = $rowSale['grm_ref'];
    $qty          = $rowSale['quantity'];
    $price        = $rowSale['price'];
    $item_discount= $rowSale['item_discount'];
    $bill_discount= $rowSale['bill_discount'];

    if (!isset($order_data[$grm_ref])) {
        $order_data[$grm_ref] = [
            'gross_value' => 0,  // sum of price*quantity for that ref
            'item_discount' => 0,
            'bill_discount' => 0
        ];
    }
    $order_data[$grm_ref]['gross_value']    += ($qty * $price);
    $order_data[$grm_ref]['item_discount']  += $item_discount;

    // Only add bill discount once per ref
    if (!isset($processedRef[$grm_ref])) {
        $order_data[$grm_ref]['bill_discount'] += $bill_discount;
        $processedRef[$grm_ref] = true;
    }
}

// Summation: raw sales minus total discounts
$rawSalesTotal   = 0;
$totalItemDisc   = 0;
$totalBillDisc   = 0;

foreach ($order_data as $ref => $vals) {
    $rawSalesTotal += $vals['gross_value'];
    $totalItemDisc += $vals['item_discount'];
    $totalBillDisc += $vals['bill_discount'];
}

// Now rawSalesTotal = sum( price*qty ) of all orders
// totalItemDisc, totalBillDisc are the sum of all discounts

// Next: get the sum of *all* returned items across all orders
$sql_return_all = "
    SELECT SUM(t_o.quantity * p.price) AS totalReturns
    FROM tbl_return_exchange t_re
    JOIN tbl_order t_o ON t_o.id = t_re.or_id
    JOIN tbl_product p ON p.id = t_o.product_id
";
$rs_ret_all = $conn->query($sql_return_all);
$totalReturns = 0;
if ($rs_ret_all && $rs_ret_all->num_rows > 0) {
    $rowRetAll = $rs_ret_all->fetch_assoc();
    $totalReturns = $rowRetAll['totalReturns'] ?: 0;
}

// So final "Total Sales Value" is raw sales - returns - (discounts)
$tot_bill_dis = $rawSalesTotal - $totalReturns - ($totalItemDisc + $totalBillDisc);
if ($tot_bill_dis < 0) {
    $tot_bill_dis = 0; // just in case
}

// 4) Today's Sales (similar approach: raw - returns - discount), but only for today's orders
$today_date = date("Y-m-d");

// (A) Get raw sales for today's orders
$sql_today_sales = "
    SELECT
        o.id,
        o.grm_ref,
        o.quantity,
        o.discount AS item_discount,
        p.price,
        g.discount_price AS bill_discount,
        g.order_date
    FROM tbl_order o
    JOIN tbl_product p ON o.product_id = p.id
    JOIN tbl_order_grm g ON o.grm_ref = g.id
    WHERE DATE(g.order_date) = '$today_date'
";

$rs_today_sales = $conn->query($sql_today_sales);
$today_orders   = [];
$processedRef   = [];

while ($rowTS = $rs_today_sales->fetch_assoc()) {
    $grm_ref      = $rowTS['grm_ref'];
    $qty          = $rowTS['quantity'];
    $price        = $rowTS['price'];
    $item_discount= $rowTS['item_discount'];
    $bill_discount= $rowTS['bill_discount'];

    if (!isset($today_orders[$grm_ref])) {
        $today_orders[$grm_ref] = [
            'gross_value' => 0,
            'item_discount' => 0,
            'bill_discount' => 0
        ];
    }
    $today_orders[$grm_ref]['gross_value']   += ($qty * $price);
    $today_orders[$grm_ref]['item_discount'] += $item_discount;

    if (!isset($processedRef[$grm_ref])) {
        $today_orders[$grm_ref]['bill_discount'] += $bill_discount;
        $processedRef[$grm_ref] = true;
    }
}

// Sum them
$todayRawSales   = 0;
$todayItemDisc   = 0;
$todayBillDisc   = 0;

foreach ($today_orders as $ref => $vals) {
    $todayRawSales += $vals['gross_value'];
    $todayItemDisc += $vals['item_discount'];
    $todayBillDisc += $vals['bill_discount'];
}

// (B) Get total returns for today's orders
$sql_returns_today = "
    SELECT SUM(t_o.quantity * p.price) AS todayReturns
    FROM tbl_return_exchange t_re
    JOIN tbl_order t_o ON t_o.id = t_re.or_id
    JOIN tbl_order_grm g ON g.id = t_o.grm_ref
    JOIN tbl_product p ON p.id = t_o.product_id
    WHERE DATE(g.order_date) = '$today_date'
";
$rs_ret_today = $conn->query($sql_returns_today);
$todayReturns = 0;
if ($rs_ret_today && $rs_ret_today->num_rows > 0) {
    $rowRetToday = $rs_ret_today->fetch_assoc();
    $todayReturns = $rowRetToday['todayReturns'] ?: 0;
}

// So today's final net sales
$tot_bill_dis_today = $todayRawSales - $todayReturns - ($todayItemDisc + $todayBillDisc);
if ($tot_bill_dis_today < 0) {
    $tot_bill_dis_today = 0;
}

$sql_daily_cash_in = "SELECT SUM(amount) AS total FROM tbl_expenses 
                      WHERE DATE(expense_date) = CURDATE() 
                      AND cash_in_out = 1";

$rs_daily_cash_in = $conn->query($sql_daily_cash_in);
$total_daily_cash_in = ($rs_daily_cash_in->num_rows > 0) ? $rs_daily_cash_in->fetch_assoc()['total'] : 0;

// 5) Today's Expenses (unchanged)
$sql_non_vendor_expenses = "SELECT SUM(amount) AS total
    FROM tbl_expenses
    WHERE vendor_id =0
      AND cash_in_out = 2
      AND DATE(expense_date) = '$today_date'";

$rs_non_vendor = $conn->query($sql_non_vendor_expenses);
$non_vendor_total = $rs_non_vendor->fetch_assoc()['total'] ?? 0;

$sql_vendor_cash_payments = "SELECT SUM(vp.amount) AS total
    FROM tbl_vendor_payments vp
    INNER JOIN tbl_expenses e ON vp.expense_id = e.expense_id
    WHERE vp.payment_method = 'cash'
      AND DATE(e.expense_date) = '$today_date'
";
$rs_vendor_cash = $conn->query($sql_vendor_cash_payments);
$vendor_cash_total = $rs_vendor_cash->fetch_assoc()['total'] ?? 0;

$tot_expenses_today = $non_vendor_total + $vendor_cash_total;

// 6) Cash Inflow
$sql_cash_in = "
    SELECT SUM(amount) AS total
    FROM tbl_expenses
    WHERE vendor_id IS NULL
      AND cash_in_out = 1
      AND DATE(expense_date) = '$today_date'
";
$rs_cash_in = $conn->query($sql_cash_in);
$cash_in_total = $rs_cash_in->fetch_assoc()['total'] ?? 0;

// 7) Payment Breakdown (Raw - returns) for *today*
$total_payments_today = ['cash' => 0, 'online' => 0, 'bank' => 0, 'credit' => 0];

// We can do a simpler approach: sum up each order's total, then subtract returns
// by referencing the result for that order. But let's do a single query approach:
$sql_payment_today = "
    SELECT
        g.payment_type,
        SUM(o.quantity * p.price) AS rawOrderValue,
        SUM(o.discount) AS totalItemDiscount,
        g.discount_price AS billDiscount,
        (
          SELECT SUM(t_o.quantity * p2.price)
          FROM tbl_return_exchange t_re
          JOIN tbl_order t_o ON t_o.id = t_re.or_id
          JOIN tbl_product p2 ON p2.id = t_o.product_id
          WHERE t_re.or_id = o.id
        ) AS returnsForThisOrder
    FROM tbl_order o
    JOIN tbl_order_grm g ON o.grm_ref = g.id
    JOIN tbl_product p   ON o.product_id = p.id
    WHERE DATE(g.order_date) = '$today_date'
    GROUP BY g.id, g.payment_type
";
$rs_payment_today = $conn->query($sql_payment_today);
while ($rowP = $rs_payment_today->fetch_assoc()) {
    $ptype           = (int)$rowP['payment_type'];
    $rawOrderValue   = (float)$rowP['rawOrderValue'];
    $totalItemDisc   = (float)$rowP['totalItemDiscount'];
    $billDiscount    = (float)$rowP['billDiscount'];
    $returnsForOrder = (float)$rowP['returnsForThisOrder'];

    // The net for this order is raw - discount - returns
    $netOrder = $rawOrderValue - ($totalItemDisc + $billDiscount) - $returnsForOrder;
    if ($netOrder < 0) {
        $netOrder = 0; // just in case
    }

    switch ($ptype) {
        case 0: $total_payments_today['cash']   += $netOrder; break;
        case 1: $total_payments_today['online'] += $netOrder; break;
        case 2: $total_payments_today['bank']   += $netOrder; break;
        case 3: $total_payments_today['credit'] += $netOrder; break;
    }
}

// 8) Till Balance: (cash received + any cash-in) - total expenses
$till_balance = ($total_payments_today['cash'] + $cash_in_total) - $tot_expenses_today +$total_daily_cash_in;
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
                                            Rs.<?= number_format($stock_value, 2) ?>
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
                                        <div class="metric-title">Total Product Cost</div>
                                        <div class="metric-value text-success">
                                            Rs.<?= number_format($total_cost_price, 2) ?>
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
                                            Rs.<?= number_format($tot_bill_dis, 2) ?>
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
                                <div class="metric-value">
                                    Rs.<?= number_format($total_payments_today['cash'], 2) ?>
                                </div>
                                <div class="metric-title mt-2">Till Balance</div>
                                <div class="metric-value text-success">
                                    Rs.<?= number_format($till_balance, 2) ?>
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
                                            Rs.<?= number_format($total_payments_today['online'], 2) ?>
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
                                    <div class="card-icon bg-warning text-white me-3">
                                        <i class="ri-bank-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Bank Transfers</div>
                                        <div class="metric-value text-warning">
                                            Rs.<?= number_format($total_payments_today['bank'], 2) ?>
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
                                        <i class="ri-bank-card-2-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-title">Credit Payments</div>
                                        <div class="metric-value text-danger">
                                            Rs.<?= number_format($total_payments_today['credit'], 2) ?>
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
                                            Rs.<?= number_format($tot_bill_dis_today, 2) ?>
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
                                            Rs.<?= number_format($tot_expenses_today, 2) ?>
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
                                    <?php if ($rs && $rs->num_rows > 0): ?>
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
                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination justify-content-end">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
