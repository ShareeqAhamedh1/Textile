<?php include './backend/conn.php';

$u_id = $_SESSION['u_id'];
if(isset($_REQUEST['cat_id'])){
  $cat_id = $_REQUEST['cat_id'];
}else{
  $cat_id = 0;
}
if(isset($_REQUEST['sub_cat_id'])){
  $sub_cat_id = $_REQUEST['sub_cat_id'];
}else{
  $sub_cat_id = 0;
}
if(isset($_REQUEST['brand_id'])){
  $brand_id = $_REQUEST['brand_id'];
}else{
  $brand_id = 0;
}
 ?>
<table class="table  datanew" id="table_id">
  <thead>
    <tr>

      <th>Product Name</th>
      <th>Barcode </th>
    
      <th>Quantity </th>

    
      <th>GRM Ref</th>


    </tr>
  </thead>
  <tbody>

    <?php
   $sql = "SELECT * FROM tbl_expiry_date WHERE user_id='$u_id' ORDER BY grm_ref DESC";


    $rs = $conn->query($sql);
    if($rs->num_rows >0){
      while($row = $rs->fetch_assoc()){ ?>
            <tr>

              <?php
              $cat_id = $row['product_id'];
              $sqlSub = "SELECT * FROM tbl_product WHERE id='$cat_id'";
              $rsSub = $conn->query($sqlSub);
              if($rsSub->num_rows >0){
                while($rowSub = $rsSub->fetch_assoc()){
                  $s_p_id = $row['s_point_id'];
                   ?>

            <?php 
                            $subId=$rowSub['sub_category_id'];
                            
                            $sqlProdName="SELECT * FROM tbl_sub_category WHERE id='$subId'";
                            $rsProdName=$conn->query($sqlProdName);
                            if($rsProdName->num_rows>0){
                              $rowsProdName=$rsProdName->fetch_assoc();
                              $productName=$rowsProdName['name'];
                              ?>
                              <td >
                            <?= $productName ?>
                          </td>
                              <?php
                            }
                            ?>
                  
              <?php }}else{ ?>
                  <td>N/A</td>
                  <?php } ?>
              <td><?= $row['barcode'] ?></td>
         
              <td><?= $row['quantity'] ?></td>
   
             

              <?php
              $cat_id = $row['grm_ref'];
              $sqlSub = "SELECT * FROM tbl_stock_grm WHERE id='$cat_id'";
              $rsSub = $conn->query($sqlSub);
              if($rsSub->num_rows >0){
                while($rowSub = $rsSub->fetch_assoc()){ ?>
                  <td><?= $rowSub['stock_ref']; ?></td>
              <?php }}else{ ?>
                  <td>N/A</td>
                  <?php } ?>

               

            </tr>
    <?php }} ?>

  </tbody>
</table>
