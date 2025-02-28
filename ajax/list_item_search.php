<?php
    include '../backend/conn.php';
    $skey = $_REQUEST['skey'];

    $sql ="SELECT * FROM `tbl_product` WHERE name LIKE '%$skey%' OR barcode ='$skey'";
    $rs=$conn->query($sql);

    if($rs->num_rows > 0){
      while($row = $rs->fetch_assoc()){
        $id=$row['id'];
        $currentStock = currentStockCount($conn, $id);
        $stockBadge = '<span class="badge bg-success">In Stock</span>';
        if ($currentStock <= 5) {
            $stockBadge = '<span class="badge bg-warning">Low Stock</span>';
        }
        if ($currentStock == 0) {
            $stockBadge = '<span class="badge bg-danger">Out of Stock</span>';
        }
    ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
  <div>
    <h6 class="mb-1"><?= $row['name'] ?></h6>
    <small class="text-muted">Price: LKR <?= $row['price'] ?> | In Stock: <span class="badge bg-primary px-3 py-2 fs-6"><?= $currentStock ?></span> <?= $stockBadge ?> </small>
  </div>
  <button class="btn btn-sm btn-primary" onclick="addToOrders(<?= $id ?>,1)">
    <i class="fas fa-plus"></i>
  </button>
</li>
<?php } } ?>
