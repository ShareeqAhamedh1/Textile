<?php if($from_date != 0){ ?>
  <td> <?= getProductPriceWithDate($conn,$p_id,$from_date,$to_date) ?> </td>
  <td> <?= getProductCostWithDate($conn,$p_id,$from_date,$to_date) ?> </td>
  <td>LKR <?= number_format(getProductPriceWithDate($conn,$p_id,$from_date,$to_date) - getProductCostWithDate($conn,$p_id,$from_date,$to_date),2) ?> </td>
<?php }else{ ?>
  <td> <?= getProductPrice($conn,$p_id) ?> </td>
  <td> <?= getProductCost($conn,$p_id) ?> </td>
  <td>LKR <?= number_format(getProductPrice($conn,$p_id) - getProductCost($conn,$p_id),2) ?> </td>
<?php } ?>
