<?php 

include './backend/conn.php'; 
$u_id = $_SESSION['u_id'];
include './layouts/sidebar.php'; 
?>
<?php
if (!isset($_SESSION['user_logged'])) {
    header('location:./signin.php');
    exit();
}

$vendor_id = $_GET['vendor_id'] ?? null;
$purchase_id = $_GET['purchase_id'] ?? null;

if (!$purchase_id || !$vendor_id) {
    die(json_encode(["error" => "Invalid request"]));
}

// Modified SQL query with proper field selection
$query = "SELECT 
            pi.product_id,
            p.name,
            pi.unit_price,
            pi.quantity,
            p.price AS selling_price,
            pi.grm_ref,
            MAX(e.barcode) AS barcode  -- Aggregate function for barcode
          FROM tbl_purchase_items pi
          JOIN tbl_product p ON pi.product_id = p.id
          JOIN tbl_purchases pu ON pi.purchase_id = pu.purchase_id
          LEFT JOIN tbl_expiry_date e ON pi.product_id = e.product_id
          WHERE pi.purchase_id = ?
            AND pu.vendor_id = ?
          GROUP BY pi.product_id";  

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $purchase_id, $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['product_id'],
        'name' => $row['name'],
        'unit_price' => $row['unit_price'],
        'quantity' => $row['quantity'],
        'selling_price' => $row['selling_price'],
        'barcode' => $row['barcode'],
        'grm_ref' => $row['grm_ref']
    ];
}
$stmt->close();
// header('Content-Type: application/json');
// echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
// exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
<meta name="robots" content="noindex, nofollow">
<title>Vendor Admin</title>

<!-- Favicon -->
<link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css">

<!-- Animation CSS -->
<link rel="stylesheet" href="assets/css/animate.css">

<!-- Owl Carousel CSS -->
<link rel="stylesheet" href="assets/plugins/owlcarousel/owl.carousel.min.css">
<link rel="stylesheet" href="assets/plugins/owlcarousel/owl.theme.default.min.css">

<!-- Select2 CSS -->
<link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">

<!-- Datetimepicker CSS -->
<link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css">

<!-- Fontawesome CSS -->
<link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
<link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">

<!-- Main CSS -->
<link rel="stylesheet" href="assets/css/style.css">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Arial', sans-serif;
    }

    .content {
        padding: 20px;
    }

    .card {
        border: none;
        border-radius: 10px;
    }

    .card-body {
        padding: 20px;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .form-select, .form-control {
        border-radius: 8px;
    }

    .search-section .input-group {
        border-radius: 8px;
        overflow: hidden;
    }

    .search-results-container {
        position: absolute;
        width: 100%;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15);
        display: none;
    }

    .search-item {
        padding: 12px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
        cursor: pointer;
    }

    .search-item:hover {
        background-color: #f1f3f5;
    }

    .table th {
        background-color: #007bff;
        color: white;
        text-align: center;
    }

    .table td, .table th {
        vertical-align: middle;
    }

    .product-image {
        width: 50px;
        height: 50px;
        object-fit: contain;
        border-radius: 8px;
    }

    #selectedItems input[type="number"] {
        width: 70px;
        text-align: center;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    .btn-success {
        font-size: 18px;
        padding: 12px;
        border-radius: 8px;
    }
</style>

</head>
<body>

<div class="page-wrapper">
    <div class="content">
        <div class="row">
            <!-- Vendor Selection & Search Panel -->
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-primary mb-3">Vendor Selection</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Select Vendor</label>
                                <select class="form-select form-select-lg" id="vendor_id" required>
                                    <option value="">Choose Vendor</option>
                                    <?php
                                        $vendors = $conn->query("SELECT * FROM tbl_vendors");
                                        while($vendor = $vendors->fetch_assoc()): ?>
                                    <option value="<?= $vendor['vendor_id'] ?>" 
    <?= ($vendor_id == $vendor['vendor_id']) ? 'selected' : '' ?>>
    <?= htmlspecialchars($vendor['vendor_name']) ?>
</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" id="purchase_date" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <!-- Product Search Section -->
                        <h5 class="card-title fw-bold text-primary mt-4">Search Products</h5>
                        <div class="search-section">
                            <div class="input-group input-group-lg">
                                <input type="text" class="form-control" id="search_name" 
                                       placeholder="🔍 Search products..." autofocus oninput="searchProd()">
                            </div>
                            <div id="searchResults" class="search-results-container mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Items Table -->
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-primary mb-3">Purchase Items</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Number of Prints</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedItems" class="fw-semibold">
                                    <!-- Selected items will appear here -->
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                        <td id="grandTotal">Rs 0.00</td>
                                        <td colspan="2">
                                            <button class="btn btn-sm btn-primary" onclick="printAllBarcodes()">Print All Barcodes</button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button class="btn btn-warning btn-lg" onclick="updatePurchase()">
                                <i class="fas fa-check-circle me-2"></i> Update Purchase
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- End row -->
    </div> <!-- End content -->
</div> <!-- End page-wrapper -->

<script>


function searchProd() {
    const searchTerm = document.getElementById('search_name').value.trim();
    const resultsContainer = document.getElementById('searchResults');
    
    if (searchTerm.length > 1) {
        fetch(`select_vendor_table.php?word=${encodeURIComponent(searchTerm)}`)
            .then(response => response.text())
            .then(data => {
                resultsContainer.innerHTML = data;
                resultsContainer.style.display = 'block';
            });
    } else {
        resultsContainer.style.display = 'none';
    }
}

// Initialize with PHP data
let purchaseItems = <?= json_encode($data) ?>;

document.addEventListener("DOMContentLoaded", () => {
    renderPurchaseItems();
});

function renderPurchaseItems() {
    const tbody = document.getElementById('selectedItems');
    let grandTotal = 0;

    tbody.innerHTML = purchaseItems.map((item, index) => {
        const total = parseFloat(item.unit_price) * parseInt(item.quantity);
        grandTotal += total;

        return `
            <tr>
                <td>${item.name} <br>${item.barcode}</td>
                <td>
                    <input class="form-control" 
                        value="${item.unit_price}" 
                        disabled>
                </td>
                <td>
                    <input class="form-control" 
                        value="${item.selling_price}" 
                        disabled>
                </td>
                <td>
                    <input type="number" class="form-control" 
                        value="${item.quantity}" 
                        onchange="updateItemField(${index}, 'quantity', this.value)">
                </td>
                <td>Rs ${total.toFixed(2)}</td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                        value="${item.prints}" 
                        onchange="updatePrints(${index}, event)">
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" 
                        onclick="removeItem(${index})">
                        Remove
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    document.getElementById('grandTotal').textContent = `Rs ${grandTotal.toFixed(2)}`;
}

async function updatePurchase() { 
    const formData = new FormData();
    formData.append('purchase_id', <?= $purchase_id ?>);
    formData.append('vendor_id', <?= $vendor_id ?>);

    // Collect updated quantities
    let updatedItems = purchaseItems.map((item, index) => ({
        id: item.id,
        quantity: document.querySelectorAll('input[type="number"]')[index].value,  // Only update quantity
    }));

    formData.append('items', JSON.stringify(updatedItems));

    try {
        const response = await fetch('./backend/update_purchase.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            alert('Purchase updated successfully!');
            window.location.reload();
        } else {
            throw new Error(result.message || 'Failed to update purchase');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    }
}


function updateItemField(index, field, value) {
    purchaseItems[index][field] = value;
    renderPurchaseItems();
}

function removeItem(index) {
    if(confirm('Are you sure you want to remove this item?')) {
        purchaseItems.splice(index, 1);
        renderPurchaseItems();
    }
}


</script>