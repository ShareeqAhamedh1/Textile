<!-- Header -->
<?php include './layouts/header.php'; ?>
<!-- Header -->

<!-- Sidebar -->
<?php include './layouts/sidebar.php'; ?>
<!-- /Sidebar -->

<div class="page-wrapper">
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h4 class="mb-2">Product Add</h4>
                <h6 class="text-muted">Create a new product</h6>
            </div>
			<a href="productlist.php" class="btn btn-primary">
    <i class="fas fa-box"></i> View Products
</a>

        </div>

        <!-- Product Add Form -->
        <form action="./backend/add_product.php" method="post" enctype="multipart/form-data">
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
                                            <option value="<?= $rowCategory['id'] ?>">
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
                                <input name="name" type="text" class="form-control" required>
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
                                            <option value="<?= $row['vendor_id'] ?>">
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
                                        <option value="<?= $row['id'] ?>">
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
                                <?php
                                $barcode = 1000;
                                $sqlBar = "SELECT barcode FROM tbl_product ORDER BY id DESC LIMIT 1";
                                $rsBar = $conn->query($sqlBar);
                                if ($rsBar && $rsBar->num_rows > 0) {
                                    $row = $rsBar->fetch_assoc();
                                    $barcode = $row['barcode'] + 1;
                                }
                                ?>
                                <input name="barcode" type="text" class="form-control" 
                                       value="<?= $barcode ?>" required readonly>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">Quantity</label>
                                <input name="quantity" type="number" value="0" 
                                       class="form-control" min="0" required readonly>
                            </div>
                        </div>

                        <!-- Pricing Section -->
						<div class="col-md-4">
							<div class="form-group">
								<label class="form-label fw-bold">HS Code</label>
								<div class="input-group">
									
									<input name="hs_code" type="text" 
										class="form-control" placeholder="Enter HS Code" required>
								</div>
							</div>
						</div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-bold">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input name="cost_price" type="number" step="0.01" 
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
                                           class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="1">Available</option>
                                    <option value="0">Unavailable</option>
                                </select>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                            <a href="productlist.php" class="btn btn-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Submit</button>
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