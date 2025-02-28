<!-- Header -->
<?php include './layouts/header.php'; 
// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch existing product data
$product = [];
$stock_grm = [];
if($product_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, sg.stock_ref AS hs_code 
                          FROM tbl_product p
                          LEFT JOIN tbl_stock_grm sg ON p.grm_ref = sg.id
                          WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if(!$product) {
        $_SESSION['error_cus'] = 'Product not found';
        header('Location: productlist.php');
        exit();
    }
}
?>
<!-- Header -->

<!-- Sidebar -->
<?php include './layouts/sidebar.php'; ?>
<!-- /Sidebar -->

<div class="page-wrapper">
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h4 class="mb-2"><?= $product_id ? 'Edit' : 'Add' ?> Product</h4>
                <h6 class="text-muted"><?= $product_id ? 'Modify existing product' : 'Create new product' ?></h6>
            </div>
            <a href="productlist.php" class="btn btn-primary">
                <i class="fas fa-list"></i> View Products
            </a>
        </div>

        <!-- Product Edit Form -->
        <form action="./backend/update_product.php" method="post">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="redir" value="<?= isset($_REQUEST['redir']) ? htmlspecialchars($_REQUEST['redir']) : '' ?>">

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Category Section -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Category</label>
                                <div class="input-group">
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Choose Category</option>
                                        <?php
                                        $sqlCategory = "SELECT * FROM tbl_category";
                                        $rsCategory = $conn->query($sqlCategory);
                                        while ($rowCategory = $rsCategory->fetch_assoc()): ?>
                                            <option value="<?= $rowCategory['id'] ?>" 
                                                <?= ($product['category_id'] ?? '') == $rowCategory['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rowCategory['name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <a href="addcategory.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Product Name -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Product Name</label>
                                <input name="name" type="text" class="form-control" 
                                       value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Vendor Section -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Vendor</label>
                                <div class="input-group">
                                    <select name="vendor_id" class="form-select" required>
                                        <option value="">Choose Vendor</option>
                                        <?php
                                        $sql = "SELECT * FROM tbl_vendors";
                                        $rs = $conn->query($sql);
                                        while ($row = $rs->fetch_assoc()): ?>
                                            <option value="<?= $row['vendor_id'] ?>" 
                                                <?= ($product['vendor_id'] ?? '') == $row['vendor_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['vendor_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <a href="addvendor.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Unit/Size Section -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Unit/Size</label>
                                <select name="unit_id" class="form-select" required>
                                    <option value="0">Common</option>
                                    <?php
                                    $sql = "SELECT * FROM tbl_unit";
                                    $rs = $conn->query($sql);
                                    while ($row = $rs->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>" 
                                            <?= ($product['unit'] ?? '') == $row['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['unit_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Barcode Section -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Barcode</label>
                                <input name="barcode" type="text" class="form-control" 
                                       value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" 
                                       <?= $product_id ? 'readonly' : 'required' ?>>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Quantity</label>
                                <input name="quantity" type="number" 
                                       value="<?= htmlspecialchars($product['quantity'] ?? 1) ?>" 
                                       class="form-control" min="1" disabled>
                            </div>
                        </div>

                        <!-- Pricing Section -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-bold">HS Code</label>
                                <input name="hs_code" type="text" 
                                       value="<?= htmlspecialchars($product['hs_code'] ?? '') ?>" 
                                       class="form-control" placeholder="Enter HS Code" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-bold">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input name="cost_price" type="number" step="0.01" 
                                           value="<?= htmlspecialchars($product['cost_price'] ?? '') ?>" 
                                           class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-bold">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input name="price" type="number" step="0.01" 
                                           value="<?= htmlspecialchars($product['price'] ?? '') ?>" 
                                           class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="1" <?= ($product['status'] ?? 1) == 1 ? 'selected' : '' ?>>Available</option>
                                    <option value="0" <?= ($product['status'] ?? 1) == 0 ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                            <a href="productlist.php" class="btn btn-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <?= $product_id ? 'Update' : 'Create' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Footer -->
<?php include './layouts/footer.php'; ?>
</body>
</html>