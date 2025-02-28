<?php

include './layouts/header.php';


// Validate product ID
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    $_SESSION['error_cus'] = 'Invalid product ID';
    header('Location: productlist.php');
    exit();
}

// Check authentication
if (!isset($_SESSION['u_id'])) {
    $_SESSION['error_cus'] = 'You must be logged in to view this page.';
    header('Location: login.php');
    exit();
}

// Fetch product data
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, u.unit_name, v.vendor_name 
    FROM tbl_product p
    LEFT JOIN tbl_category c ON p.category_id = c.id
    LEFT JOIN tbl_unit u ON p.unit = u.id
    LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    $_SESSION['error_cus'] = 'Product not found';
    header('Location: productlist.php');
    exit();
}
?>

<!-- Sidebar -->
<?php include './layouts/sidebar.php'; ?>
<!-- /Sidebar -->

<div class="page-wrapper">
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h4>Product Details</h4>
                <h6>Full details of a product</h6>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <div class="productdetails">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Product Name</h5>
                                    <span class="text-muted"><?= htmlspecialchars($product['name']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Category</h5>
                                    <span class="text-muted"><?= htmlspecialchars($product['category_name']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Vendor</h5>
                                    <span class="text-muted"><?= htmlspecialchars($product['vendor_name']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Unit/Size</h5>
                                    <span class="text-muted"><?= htmlspecialchars($product['unit_name']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Barcode</h5>
                                    <span class="text-muted"><?= htmlspecialchars($product['barcode']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Quantity</h5>
                                    <span class="text-muted"><?= htmlspecialchars($product['quantity']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Selling Price</h5>
                                    <span class="text-muted">Rs.<?= number_format($product['price'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Status</h5>
                                    <span class="badge bg-<?= $product['status'] == "active" ? 'success' : 'danger' ?>">
                                        <?= $product['status'] == "active" ? 'Available' : 'Unavailable' ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($product['image'])): ?>
            <div class="col-lg-4 col-sm-12">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($product['image']) ?>" 
                             alt="Product Image" 
                             class="img-fluid rounded"
                             style="max-height: 300px;">
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Stock Information</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Quantity</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT * FROM tbl_expiry_date 
                                WHERE product_id = ?
                                ORDER BY created_at ASC
                            ");
                            $stmt->bind_param("i", $product_id);
                            $stmt->execute();
                            $stockResult = $stmt->get_result();
                            $totalStock = 0;

                            while ($stock = $stockResult->fetch_assoc()):
                                $totalStock += $stock['quantity'];
                            ?>
                            <tr>
                                <td><?= $stock['quantity'] ?></td>
                                <td>
                                    <?= $stock['created_at'] != '0000-00-00' ? 
                                        date('M d, Y', strtotime($stock['created_at'])) : 
                                        'N/A' ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        
                                        <a href="backend/delStock.php?id=<?= $stock['id'] ?>&pid=<?= $product_id ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this stock entry?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <tr class="table-secondary">
                                <td colspan="3" class="text-end fw-bold">
                                    Total Stock: <?= $totalStock ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './layouts/footer.php'; ?>
</body>
</html>