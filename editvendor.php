<?php
include './layouts/header.php';
include './layouts/sidebar.php';

// Get vendor ID from URL
$vendor_id = isset($_GET['vendor_id']) ? mysqli_real_escape_string($conn, $_GET['vendor_id']) : '';

/// Fetch vendor details
$sql = "SELECT * FROM tbl_vendors WHERE vendor_id = '$vendor_id'";
$result = $conn->query($sql);
$vendor = $result->fetch_assoc();

if (!$vendor) {
    echo "<script>alert('Vendor not found!'); window.location.href='vendorlist.php';</script>";
    exit();
}
?>

<div class="page-wrapper">
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h4>Edit Vendor</h4>
                <h6>Modify Vendor Details</h6>
            </div>
            <div>
                <a href="addproduct.php"><button type="button" class="btn btn-primary ml-2">Add product</button></a>
            </div>
        </div>

        <!-- /Edit Form -->
        <form action="./backend/update_vendor.php" method="post">
            <input type="hidden" name="vendor_id" value="<?= $vendor['vendor_id'] ?>">

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 col-sm-6 col-12">
                            <div class="form-group">
                                <label>Vendor Name</label>
                                <input name="vendor_name" type="text" value="<?= htmlspecialchars($vendor['vendor_name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-6 col-12">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input name="phone" type="text" value="<?= htmlspecialchars($vendor['phone']) ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-6 col-12">
                            <div class="form-group">
                                <label>Address</label>
                                <input name="address" type="text" value="<?= htmlspecialchars($vendor['address']) ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <button type="submit" class="btn btn-submit me-2">Update</button>
                            <a href="vendorlist.php" class="btn btn-cancel">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- /Edit Form -->
    </div>
</div>

<?php include './layouts/footer.php'; ?>

</body>
</html>
