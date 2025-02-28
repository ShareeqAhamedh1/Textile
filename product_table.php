<?php include './backend/conn.php';
$u_id = $_SESSION['u_id'];

if(isset($_REQUEST['cat_id'])){
  $cat_id = $_REQUEST['cat_id'];
}else{
  $cat_id = 0;
}
$totVal=0;
 ?>
<table class="table  datanew" id="table_id">
  <thead>
    <tr>

      <th>Product Name</th>
      <th>Barcode</th>
      <th>Category </th>
      <!-- <th>Sub Category </th> -->
      <!-- <th>Brand</th> -->
      <th>Price</th>
      <th>Qty</th>
      <th>Total Stock Value</th>

      <!-- <th>Discount</th> -->
      <th>Action</th>
    </tr>
  </thead>
  <tbody>

    <?php
    if(!$cat_id ){
      $sql = "SELECT * FROM tbl_product ORDER BY `created_at` DESC";
    }elseif($cat_id){
      $sql = "SELECT * FROM tbl_product WHERE category_id='$cat_id'";
   
    }
    $rs = $conn->query($sql);
    if($rs->num_rows >0){
    
      while($row = $rs->fetch_assoc()){ ?>
            <tr>

             
                <!-- <a href="javascript:void(0);" class="product-img">
                  <img src="assets/img/product/product1.jpg" alt="product">
                </a> -->

                
                <td><?= $row['name'] ?></td>
                 
              <td><?= $row['barcode'] ?></td>

              <?php
              $cat_id = $row['category_id'];
              $sqlSub = "SELECT * FROM tbl_category WHERE id='$cat_id'";
              $rsSub = $conn->query($sqlSub);
              if($rsSub->num_rows >0){
                while($rowSub = $rsSub->fetch_assoc()){ ?>
                  <td><?= $rowSub['name']; ?></td>
              <?php }}else{ ?>
                  <td>N/A</td>
                  <?php } ?>

                  <?php
                  $id = $row['id'];


                   ?>

              <td><?= $row['price']; ?></td>

              <td>
                <?php if($u_id==1){
                  ?>
                  <button class="btn btn-primary" onclick="openQuantityModalReduce(<?= $id ?>)">-</button>
                  
                  <?php
                } ?>
                  <span id="qty_<?= $row['id'] ?>"><?= currentStockCount($conn,$row['id']) ?></span>
                  <?php if($u_id==1){
                  ?>
                  <button class="btn btn-primary" onclick="openQuantityModal(<?= $id ?>)">+</button>
                  <?php
                } ?>
                  

              </td>

                  <td> <?= currentStockCount($conn,$id) * $row['price'] ?> </td>


                  <td>
                                        <a class="me-3" href="product-details.php?id=<?= $row['id'] ?>">
                                            <img src="assets/img/icons/eye.svg" alt="View">
                                        </a>
                                        <?php if($u_id == 1){ ?>
                                            <a class="me-3" href="editproduct.php?id=<?= $row['id'] ?>">
                                                <img src="assets/img/icons/edit.svg" alt="Edit">
                                            </a>
                                            <a class="confirm-text" onclick="del_prod(<?= $row['id'] ?>)" href="javascript:void(0);">
                                                <img src="assets/img/icons/delete.svg" alt="Delete">
                                            </a>
                                            <button onclick="printBarcode(<?= $row['id'] ?>)" class="btn btn-sm btn-primary">Print Barcode</button>
                                        <?php } ?>
                                    </td>
            </tr>
            <?php $totVal += currentStockCount($conn,$id) * $row['price'] ; ?>
    <?php }} ?>
    <tr>
      <td> Total Value : <?= $totVal ?> </td>
    </tr>

  </tbody>
</table>
