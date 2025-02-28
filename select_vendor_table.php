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

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="list-group-item" 
                onclick="addToPurchase(
                    '.$row['id'].', 
                    \''.htmlspecialchars($row['name']).'\', 
                    '.$row['cost_price'].', 
                     '.$row['price'].', 
                    \''.htmlspecialchars($row['vendor_name']).'\', 
                    '.$row['vendor_id'].',
                    \''.$row['barcode'].'\',
                    \''.$row['hs_code'].'\', // This is stock_ref from tbl_stock_grm
                    '.$row['grm_ref'].' // From tbl_product
                )">
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
    }
}
?>