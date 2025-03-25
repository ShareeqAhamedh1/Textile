<?php if($from_date != 0){ ?>
  <td> <?= getProductPriceWithDate($conn,$p_id,$from_date,$to_date) ?> </td>
  <td> <?= getProductCostWithDate($conn,$p_id,$from_date,$to_date) ?> </td>
  <td>LKR <?= number_format(getProductPriceWithDate($conn,$p_id,$from_date,$to_date) - getProductCostWithDate($conn,$p_id,$from_date,$to_date),2) ?> </td>
<?php }else{ ?>
  <td> <?= getProductPrice($conn,$p_id) ?> </td>
  <td> <?= getProductCost($conn,$p_id) ?> </td>
  <td>LKR <?= number_format(getProductPrice($conn,$p_id) - getProductCost($conn,$p_id),2) ?> </td>
<?php } ?>


<?php
  if($customer_id !=0){
 ?>
 var cccc_id = <?= $customer_id ?>;

 if (cccc_id) {
   $.ajax({
     url: "backend/set_customer_session.php",
     type: "POST",
     data: { customer_id: cccc_id },
     dataType: "json",
     success: function (data) {
       if (data.status === "success") {
         $("#customerPhone").text(data.phone);
         $("#customerBalance").text(parseFloat(data.balance).toFixed(2));
         $("#customerInfoBox").fadeIn(); // Show the info box
       } else {
         alert("Failed to fetch customer details.");
       }
     },
     error: function (xhr, status, error) {
       console.error("AJAX Error:", error);
       console.error("Status:", status);
       console.error("Response Text:", xhr.responseText);
       alert("An error occurred while fetching customer details. Check the console for details.");
     }
   });
 }
 <?php } ?>
