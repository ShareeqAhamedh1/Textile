<?php
include 'layouts/header.php';
include 'layouts/sidebar.php';

$u_id = $_SESSION['u_id'];

// -------------------------------------------------------------------------------------
// 1) Pagination Setup (no JOIN used here)
// -------------------------------------------------------------------------------------
$limit  = 5; // Products per page
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// Get total products count
$sql_count = "SELECT COUNT(*) AS total FROM tbl_product";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_records = (int)$row_count['total'];
$total_pages   = ($total_records > 0) ? ceil($total_records / $limit) : 1;

// Fetch recent products (no JOIN)
$sql = "SELECT name AS product_name, price
        FROM tbl_product
        ORDER BY id DESC
        LIMIT $offset, $limit";
$rs = $conn->query($sql);

// -------------------------------------------------------------------------------------
// 2) Calculate Total Stock Value (minus returns) using existing helper functions
//    - Calls currentStockCount($conn, $p_id) and getReturnValue($conn, $p_id)
// -------------------------------------------------------------------------------------
$stock_value = 0;
$sql_products = "SELECT id, price FROM tbl_product";
$rs_prod = $conn->query($sql_products);
while ($rowProd = $rs_prod->fetch_assoc()) {
    $p_id  = $rowProd['id'];
    $price = $rowProd['price'];

    // Normal stock calculation
    $stock_value += $price * currentStockCount($conn, $p_id);

    // Subtract return value for that product
    $returnValue = getReturnValue($conn, $p_id);
    $stock_value -= $returnValue;
}

// -------------------------------------------------------------------------------------
// 3) Calculate Total Product Cost (minus returns)
//    - Removes the LEFT JOIN; sums expiry-date quantities in a separate query.
// -------------------------------------------------------------------------------------
$total_cost_price = 0;

// First, get all products
$sql_total_cost_products = "SELECT id, cost_price FROM tbl_product";
$res_total_cost_products = $conn->query($sql_total_cost_products);

while ($prod = $res_total_cost_products->fetch_assoc()) {
    $p_id       = $prod['id'];
    $cost_price = (float)$prod['cost_price'];

    // Sum total quantity from tbl_expiry_date for this product (no join)
    $sql_qty = "SELECT COALESCE(SUM(quantity), 0) AS total_quantity
                FROM tbl_expiry_date
                WHERE product_id = '$p_id'";
    $res_qty = $conn->query($sql_qty);
    $row_qty = $res_qty->fetch_assoc();
    $qty = (int)($row_qty['total_quantity']);

    // Total cost ignoring returns
    $productCost = $cost_price * $qty;

    // Subtract cost of returned items using existing function
    $returnedCost = getReturnCost($conn, $p_id);
    $productCost -= $returnedCost;
    if ($productCost < 0) {
        $productCost = 0;
    }

    $total_cost_price += $productCost;
}

// -------------------------------------------------------------------------------------
// 4) Calculate Total Sales Value (minus returns + discount)
//    - We'll do: sum up orders separately, sum returns separately, and subtract
// -------------------------------------------------------------------------------------
$rawSalesTotal = 0;
$totalItemDisc = 0;
$totalBillDisc = 0;

// We’ll gather all orders first (no JOIN)
$order_data = [];   // key = grm_ref
$processedRef = []; // track if we've added the bill discount for a ref

// 4A) Fetch all orders from tbl_order
$sql_orders_all = "SELECT * FROM tbl_order";
$res_orders_all = $conn->query($sql_orders_all);

// 4B) For each order, get the product price + the order_grm discount
while ($ord = $res_orders_all->fetch_assoc()) {
    $oid       = $ord['id'];
    $grm_ref   = $ord['grm_ref'];
    $p_id      = $ord['product_id'];
    $qty       = (int)$ord['quantity'];
    $item_disc = (float)$ord['discount'];

    // Get product price (no join)
    $sql_p = "SELECT price FROM tbl_product WHERE id = '$p_id'";
    $res_p = $conn->query($sql_p);
    $row_p = $res_p->fetch_assoc();
    $price = ($row_p) ? (float)$row_p['price'] : 0;

    // Get order_grm row (no join)
    $sql_g = "SELECT discount_price FROM tbl_order_grm WHERE id = '$grm_ref'";
    $res_g = $conn->query($sql_g);
    $row_g = $res_g->fetch_assoc();
    $bill_discount = $row_g ? (float)$row_g['discount_price'] : 0;

    // Initialize order_data if needed
    if (!isset($order_data[$grm_ref])) {
        $order_data[$grm_ref] = [
            'gross_value' => 0,
            'item_discount' => 0,
            'bill_discount' => 0
        ];
    }

    // Add raw (price * quantity)
    $order_data[$grm_ref]['gross_value'] += ($price * $qty);

    // Add item discount
    $order_data[$grm_ref]['item_discount'] += $item_disc;

    // Add bill discount once per ref
    if (!isset($processedRef[$grm_ref])) {
        $order_data[$grm_ref]['bill_discount'] += $bill_discount;
        $processedRef[$grm_ref] = true;
    }
}

// Summation: raw sales minus total discounts
foreach ($order_data as $ref => $vals) {
    $rawSalesTotal += $vals['gross_value'];
    $totalItemDisc += $vals['item_discount'];
    $totalBillDisc += $vals['bill_discount'];
}

// Next: get total returns (no join)
$totalReturns = 0;
$sql_return_all = "SELECT * FROM tbl_return_exchange";
$rs_ret_all = $conn->query($sql_return_all);

while ($retRow = $rs_ret_all->fetch_assoc()) {
    // retRow[ 'or_id' ] => find that order
    $or_id = $retRow['or_id'];
    $sql_o = "SELECT product_id, quantity FROM tbl_order WHERE id = '$or_id'";
    $res_o = $conn->query($sql_o);
    $row_o = $res_o->fetch_assoc();
    if (!$row_o) continue;

    $p_id = $row_o['product_id'];
    $qty  = (int)$row_o['quantity'];

    // find product price
    $sql_p = "SELECT price FROM tbl_product WHERE id = '$p_id'";
    $res_p = $conn->query($sql_p);
    $row_p = $res_p->fetch_assoc();
    $price = ($row_p) ? (float)$row_p['price'] : 0;

    $totalReturns += ($price * $qty);
}

// final total
$tot_bill_dis = $rawSalesTotal - $totalReturns - ($totalItemDisc + $totalBillDisc);
if ($tot_bill_dis < 0) {
    $tot_bill_dis = 0;
}

// -------------------------------------------------------------------------------------
// 5) Today's Sales (minus returns + discount) for orders dated today
// -------------------------------------------------------------------------------------
$today_date       = date("Y-m-d");
$todayRawSales    = 0;
$todayItemDisc    = 0;
$todayBillDisc    = 0;
$today_orders     = [];
$processedRefToday= [];

// We'll re-fetch all orders
$sql_orders_all2 = "SELECT * FROM tbl_order";
$res_orders_all2 = $conn->query($sql_orders_all2);

while ($ord2 = $res_orders_all2->fetch_assoc()) {
    $oid        = $ord2['id'];
    $grm_ref    = $ord2['grm_ref'];
    $p_id       = $ord2['product_id'];
    $qty        = (int)$ord2['quantity'];
    $item_disc  = (float)$ord2['discount'];

    // find product price
    $sql_p2 = "SELECT price FROM tbl_product WHERE id = '$p_id'";
    $rp2 = $conn->query($sql_p2);
    $pp2 = $rp2->fetch_assoc();
    $price = ($pp2) ? (float)$pp2['price'] : 0;

    // find order_grm
    $sql_g2 = "SELECT discount_price, order_date FROM tbl_order_grm WHERE id = '$grm_ref'";
    $rg2 = $conn->query($sql_g2);
    $gg2 = $rg2->fetch_assoc();
    if (!$gg2) {
        continue;
    }

    $bill_discount = (float)$gg2['discount_price'];
    // check if today's date
    $ord_date_str  = substr($gg2['order_date'], 0, 10);
    if ($ord_date_str !== $today_date) {
        continue; // skip if not today's order
    }

    // group data by grm_ref
    if (!isset($today_orders[$grm_ref])) {
        $today_orders[$grm_ref] = [
            'gross_value' => 0,
            'item_discount' => 0,
            'bill_discount' => 0
        ];
    }

    $today_orders[$grm_ref]['gross_value']   += ($price * $qty);
    $today_orders[$grm_ref]['item_discount'] += $item_disc;

    if (!isset($processedRefToday[$grm_ref])) {
        $today_orders[$grm_ref]['bill_discount'] += $bill_discount;
        $processedRefToday[$grm_ref] = true;
    }
}

// Summation for today's sales
foreach ($today_orders as $ref => $vals) {
    $todayRawSales += $vals['gross_value'];
    $todayItemDisc += $vals['item_discount'];
    $todayBillDisc += $vals['bill_discount'];
}

// total returns for today's orders
$todayReturns = 0;
$sql_ret_ex2 = "SELECT * FROM tbl_return_exchange";
$rs_ret_ex2  = $conn->query($sql_ret_ex2);

while ($rt2 = $rs_ret_ex2->fetch_assoc()) {
    $or_id = $rt2['or_id'];
    // find matching order
    $sql_o2 = "SELECT product_id, quantity, grm_ref FROM tbl_order WHERE id = '$or_id'";
    $ro2 = $conn->query($sql_o2);
    $oo2 = $ro2->fetch_assoc();
    if (!$oo2) continue;

    $p_id  = $oo2['product_id'];
    $qty   = (int)$oo2['quantity'];
    $g_ref = $oo2['grm_ref'];

    // check if that order is from today
    $sql_g3 = "SELECT order_date FROM tbl_order_grm WHERE id = '$g_ref'";
    $rg3 = $conn->query($sql_g3);
    $gg3 = $rg3->fetch_assoc();
    if (!$gg3) continue;

    $g_date = substr($gg3['order_date'], 0, 10);
    if ($g_date !== $today_date) {
        continue;
    }

    // sum (price * qty)
    $sql_p3 = "SELECT price FROM tbl_product WHERE id = '$p_id'";
    $rp3 = $conn->query($sql_p3);
    $pp3 = $rp3->fetch_assoc();
    $price = ($pp3) ? (float)$pp3['price'] : 0;

    $todayReturns += ($price * $qty);
}

// final net for today's sales
$tot_bill_dis_today = $todayRawSales - $todayReturns - ($todayItemDisc + $todayBillDisc);
if ($tot_bill_dis_today < 0) {
    $tot_bill_dis_today = 0;
}

// -------------------------------------------------------------------------------------
// 6) Today's Cash In & Expenses (no joins)
// -------------------------------------------------------------------------------------
$todayExpenses = 0;
$today_date_ymd = date("Y-m-d");

// 6A) Daily cash_in from tbl_expenses
$total_daily_cash_in = 0;
$sql_cash_in_day = "SELECT amount, expense_date, cash_in_out
                    FROM tbl_expenses
                    WHERE DATE(expense_date) = CURDATE()
                      AND cash_in_out = 1";
$r_cash_in_day = $conn->query($sql_cash_in_day);
while($ci = $r_cash_in_day->fetch_assoc()){
    $total_daily_cash_in += (float)$ci['amount'];
}

// 6B) Today's Non-Vendor Expenses + Vendor Cash
$non_vendor_total = 0;
$vendor_cash_total = 0;

// Get all expenses (no join)
$sql_exp_all = "SELECT * FROM tbl_expenses";
$rs_exp_all  = $conn->query($sql_exp_all);
$expenses    = [];
while($e = $rs_exp_all->fetch_assoc()){
    $expenses[] = $e;
}

// Also get vendor payments separately
$sql_vp_all = "SELECT * FROM tbl_vendor_payments";
$rs_vp_all  = $conn->query($sql_vp_all);
$vendor_payments = [];
while($vp = $rs_vp_all->fetch_assoc()){
    $vendor_payments[] = $vp;
}

// Summation
foreach($expenses as $ex){
    // date check
    $exDate = substr($ex['expense_date'], 0, 10);
    if($exDate === $today_date_ymd){
        // Non-vendor => vendor_id=0, cash_in_out=2
        if(((int)$ex['vendor_id'] === 0) && ((int)$ex['cash_in_out'] === 2)){
            $non_vendor_total += (float)$ex['amount'];
        }
    }
}

// For vendor payments by cash
foreach($vendor_payments as $vp){
    // find its corresponding expense row
    foreach($expenses as $ex){
        if($ex['expense_id'] == $vp['expense_id']){
            // check date
            $exDate = substr($ex['expense_date'], 0, 10);
            if($exDate === $today_date_ymd && $vp['payment_method'] == 'cash'){
                $vendor_cash_total += (float)$vp['amount'];
            }
        }
    }
}
$tot_expenses_today = $non_vendor_total + $vendor_cash_total;

// 6C) Additional "cash_in_total" check for vendor_id IS NULL
$cash_in_total = 0;
foreach($expenses as $ex){
    $exDate = substr($ex['expense_date'], 0, 10);
    if($exDate === $today_date_ymd && (int)$ex['cash_in_out'] === 1){
        // vendor_id must be null or empty
        // your DB might store NULL or 0. Adjust if needed:
        if (empty($ex['vendor_id'])) {
            $cash_in_total += (float)$ex['amount'];
        }
    }
}

// -------------------------------------------------------------------------------------
// 7) Payment Breakdown (raw - returns) for today
//    We'll sum by payment_type from tbl_order_grm using separate queries
// -------------------------------------------------------------------------------------
$total_payments_today = ['cash' => 0, 'online' => 0, 'bank' => 0, 'credit' => 0];

// We'll re-loop orders from today; group them by payment_type
$sql_today_orders_3 = "SELECT * FROM tbl_order";
$res_today_orders_3 = $conn->query($sql_today_orders_3);

while($oRow = $res_today_orders_3->fetch_assoc()){
    $oid       = $oRow['id'];
    $grm_ref   = $oRow['grm_ref'];
    $p_id      = $oRow['product_id'];
    $qty       = (int)$oRow['quantity'];
    $item_disc = (float)$oRow['discount'];

    // find the grm row
    $sql_g4 = "SELECT payment_type, discount_price, order_date
               FROM tbl_order_grm WHERE id = '$grm_ref'";
    $rg4 = $conn->query($sql_g4);
    $gg4 = $rg4->fetch_assoc();
    if(!$gg4) continue;

    $g_date       = substr($gg4['order_date'], 0, 10);
    if($g_date !== $today_date) {
        continue;
    }

    $ptype        = (int)$gg4['payment_type']; // 0= cash, 1=online, 2=bank, 3=credit
    $billDiscount = (float)$gg4['discount_price'];

    // get product price
    $sql_pp = "SELECT price FROM tbl_product WHERE id='$p_id'";
    $rp_pp  = $conn->query($sql_pp);
    $row_pp = $rp_pp->fetch_assoc();
    $price  = $row_pp ? (float)$row_pp['price'] : 0;

    // raw order
    $rawOrderValue = $price * $qty;

    // returns for this order?
    $returnsForThisOrder = 0;
    $sql_ret_this = "SELECT * FROM tbl_return_exchange WHERE or_id='$oid'";
    $res_ret_this = $conn->query($sql_ret_this);
    while($rt = $res_ret_this->fetch_assoc()){
        // if partial returns exist, handle accordingly
        $returnsForThisOrder += ($price * $qty);
    }

    // net
    $netOrder = $rawOrderValue - ($item_disc + $billDiscount) - $returnsForThisOrder;
    if($netOrder < 0){
        $netOrder = 0;
    }

    switch($ptype){
        case 0: $total_payments_today['cash']   += $netOrder; break;
        case 1: $total_payments_today['online'] += $netOrder; break;
        case 2: $total_payments_today['bank']   += $netOrder; break;
        case 3: $total_payments_today['credit'] += $netOrder; break;
    }
}

// -------------------------------------------------------------------------------------
// 8) Return Payments (for today) - no join
// -------------------------------------------------------------------------------------
$total_amount_return = 0;
$sqlReturn = "SELECT * FROM tbl_return_exchange
              WHERE DATE(order_created) = CURDATE()";
$resultReturn = $conn->query($sqlReturn);

while($re = $resultReturn->fetch_assoc()){
    // find matching order
    $or_id = $re['or_id'];
    $sql_oo = "SELECT product_id, discount, quantity
               FROM tbl_order
               WHERE id='$or_id'";
    $res_oo = $conn->query($sql_oo);
    $row_oo = $res_oo->fetch_assoc();
    if(!$row_oo) continue;

    $p_id      = $row_oo['product_id'];
    $o_discount= (float)$row_oo['discount'];
    $o_qty     = (int)$row_oo['quantity'];

    // find product price
    $sql_pp2 = "SELECT price FROM tbl_product WHERE id='$p_id'";
    $res_pp2 = $conn->query($sql_pp2);
    $row_pp2 = $res_pp2->fetch_assoc();
    $p_price  = $row_pp2 ? (float)$row_pp2['price'] : 0;

    // According to your snippet:
    // IF(o.discount IS NOT NULL, (p.price - o.discount), p.price)
    if ($o_discount != 0) {
        $val = $p_price - $o_discount;
    } else {
        $val = $p_price;
    }
    $total_amount_return += ($val * $o_qty);
}

// -------------------------------------------------------------------------------------
// 9) Till Balance
//    = (cash received + any cash_in) - total expenses - today's return + total_daily_cash_in
// -------------------------------------------------------------------------------------
$till_balance = ($total_payments_today['cash'] + $cash_in_total)
              - $tot_expenses_today
              - $total_amount_return
              + $total_daily_cash_in;

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
                    <!-- Cash Flow -->
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

                    <!-- Online Payments -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body d-flex align-items-center">
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

                    <!-- Bank Transfers -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-primary text-white me-3">
                                    <i class="ri-bank-line"></i>
                                </div>
                                <div>
                                    <div class="metric-title">Bank Transfers</div>
                                    <div class="metric-value text-primary">
                                        Rs.<?= number_format($total_payments_today['bank'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Payments -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body d-flex align-items-center">
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

                    <!-- Return Payments -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-secondary text-white me-3">
                                    <i class="ri-refund-2-line"></i>
                                </div>
                                <div>
                                    <div class="metric-title">Return Payments</div>
                                    <div class="metric-value text-secondary">
                                        Rs.<?= number_format($total_amount_return, 2) ?>
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
                                <?php
                                $visible_pages = 5; // Number of visible page links
                                $start_page = max(1, $page - floor($visible_pages / 2));
                                $end_page = min($total_pages, $start_page + $visible_pages - 1);

                                // Ensure at least $visible_pages are shown
                                if (($end_page - $start_page) < ($visible_pages - 1)) {
                                    $start_page = max(1, $end_page - $visible_pages + 1);
                                }

                                // Previous Button
                                if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1; ?>">&laquo; Prev</a>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1; ?>">Next &raquo;</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'layouts/footer.php'; ?>
