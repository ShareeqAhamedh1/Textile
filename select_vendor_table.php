<?php
include 'backend/conn.php';

$searchTerm = $_GET['word'] ?? '';

$sql = "SELECT p.*, v.vendor_name, sg.stock_ref AS hs_code, p.price
        FROM tbl_product p
        JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
        LEFT JOIN tbl_stock_grm sg ON p.grm_ref = sg.id
        WHERE p.name LIKE ? OR p.barcode LIKE ?";

$stmt = $conn->prepare($sql);
$searchParam = "%$searchTerm%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

$firstResult = true; // Flag to track the first result

if ($result->num_rows > 0) {
    echo '<div id="searchResults">';
    while ($row = $result->fetch_assoc()) {
        $dataAttributes = "data-id='{$row['id']}'
                           data-name='".htmlspecialchars($row['name'])."'
                           data-cost_price='{$row['cost_price']}'
                           data-price='{$row['price']}'
                           data-vendor='".htmlspecialchars($row['vendor_name'])."'
                           data-vendor_id='{$row['vendor_id']}'
                           data-barcode='{$row['barcode']}'
                           data-hs_code='{$row['hs_code']}'
                           data-grm_ref='{$row['grm_ref']}'";

        echo '<div class="list-group-item'.($firstResult ? ' first-result' : '').'"
                onclick="addToPurchase(
                    '.$row['id'].',
                    \''.htmlspecialchars($row['name']).'\',
                    '.$row['cost_price'].',
                    '.$row['price'].',
                    \''.htmlspecialchars($row['vendor_name']).'\',
                    '.$row['vendor_id'].',
                    \''.$row['barcode'].'\',
                    \''.$row['hs_code'].'\',
                    '.$row['grm_ref'].'
                )" '.$dataAttributes.'>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>'.htmlspecialchars($row['name']).'</strong><br>
                        <small class="text-muted">Barcode: '.$row['barcode'].'</small><br>
                        <small class="text-muted">HS Code: '.$row['hs_code'].'</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">Rs '.number_format($row['price'],2).'</span><br>
                        <small class="text-muted">Vendor: '.htmlspecialchars($row['vendor_name']).'</small>
                    </div>
                </div>
              </div>';
        $firstResult = false;
    }
    echo '</div>';
}
?>
