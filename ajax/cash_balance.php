<?php
    include '../backend/conn.php';
    $skey = $_REQUEST['skey'];

    $sql ="SELECT * FROM `tbl_product` WHERE name LIKE '%$skey%' OR barcode ='$skey'";
    $rs=$conn->query($sql);

    if($rs->num_rows > 0){
      while($row = $rs->fetch_assoc()){
    ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
  <div>
    <h6 class="mb-1"><?= $row['name'] ?></h6>
    <small class="text-muted">Price: LKR <?= $row['price'] ?> | In Stock: <?= $row['quantity'] ?></small>
  </div>
  <button class="btn btn-sm btn-primary">
    <i class="fas fa-plus"></i>
  </button>
</li>
<?php } } ?>
