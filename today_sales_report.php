<?php
include './backend/conn.php'; 

// Pagination Variables
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get Today's Sales Data
$today_date = date("Y-m-d");
$today_date_esc = $conn->real_escape_string($today_date);

$sql = "SELECT o.id, p.name AS product_name, o.quantity, o.m_price, o.discount, o.discount_type, o.bill_date 
        FROM tbl_order o
        JOIN tbl_product p ON o.product_id = p.id
        WHERE DATE(o.bill_date) = '$today_date_esc'
        ORDER BY o.bill_date DESC 
        LIMIT $offset, $limit";
$rs = $conn->query($sql);

// Get Total Sales Count for Pagination
$sql_count = "SELECT COUNT(*) AS total FROM tbl_order WHERE DATE(bill_date) = '$today_date_esc'";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_records = $row_count['total'] ?? 0;
$total_pages = max(1, ceil($total_records / $limit));

// Calculate Total Sales Value for Today
$tot_bill_dis_today = 0;
$sql_today_sales = "SELECT quantity, m_price, discount, discount_type FROM tbl_order WHERE DATE(bill_date) = '$today_date_esc'";
$rs_today_sales = $conn->query($sql_today_sales);
if ($rs_today_sales->num_rows > 0) {
    while ($row = $rs_today_sales->fetch_assoc()) {
        $discount = $row['discount'];
        $p_price = $row['m_price'];

        if ($discount != 0) {
            $d_type = $row['discount_type'];
            $dis_amount = ($d_type == "p") ? ($p_price * $discount) / 100 : $discount;
            $p_price -= round($dis_amount);
        }

        $tot_bill_dis_today += $row['quantity'] * $p_price;
    }
}
?>

<div class="page-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="page-title">Today's Sales Report</h4>
            </div>
        </div>

        <!-- Total Sales Box -->
        <div class="row">
            <div class="col-lg-12">
                <div class="dash-widget dash3">
                    <div class="dash-widgetimg">
                        <span><img src="assets/img/icons/dash2.svg" alt="img"></span>
                    </div>
                    <div class="dash-widgetcontent">
                        <h5>Rs.<?= number_format($tot_bill_dis_today, 2) ?>/-</h5>
                        <h6>Total Sales Today</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Today's Sales</h4>
                        <button class="btn btn-info" onclick="exportTableToExcel('salesTable', 'today_sales_report')">
                            Export to Excel
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table datatable" id="salesTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Discount</th>
                                        <th>Final Price</th>
                                        <th>Bill Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $count = $offset + 1;
                                    if ($rs->num_rows > 0) {
                                        while ($row = $rs->fetch_assoc()) {
                                            $discount = $row['discount'];
                                            $p_price = $row['m_price'];

                                            if ($discount != 0) {
                                                $d_type = $row['discount_type'];
                                                $dis_amount = ($d_type == "p") ? ($p_price * $discount) / 100 : $discount;
                                                $p_price -= round($dis_amount);
                                            }
                                    ?>
                                            <tr>
                                                <td><?= $count++ ?></td>
                                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                <td><?= $row['quantity'] ?></td>
                                                <td>Rs.<?= number_format($row['m_price'], 2) ?></td>
                                                <td><?= ($row['discount'] != 0) ? $row['discount'] . ($row['discount_type'] == "p" ? "%" : " Rs.") : "N/A" ?></td>
                                                <td>Rs.<?= number_format($p_price * $row['quantity'], 2) ?></td>
                                                <td><?= date("d-M-Y H:i", strtotime($row['bill_date'])) ?></td>
                                            </tr>
                                    <?php }} else { ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No Sales Today</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer text-center">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1) { ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
                                <?php } ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php } ?>
                                <?php if ($page < $total_pages) { ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
                                <?php } ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>  
</div>

<?php include './layouts/footer.php'; ?>

<!-- Export to Excel Script -->
<script>
function exportTableToExcel(tableID, filename = '') {
    let downloadLink;
    let dataType = 'application/vnd.ms-excel';
    let tableSelect = document.getElementById(tableID);
    let tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    filename = filename ? filename + '.xls' : 'excel_data.xls';

    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    downloadLink.download = filename;
    downloadLink.click();
}
</script>

</body>
</html>
