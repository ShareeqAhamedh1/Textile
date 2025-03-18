<?php
include 'backend/conn.php';
$today_date = date("Y-m-d");
$total_payments_today = [
    'cash'   => 0,
    'online' => 0,
    'bank'   => 0,
    'credit' => 0
];

$sql_today_orders_3 = "SELECT * FROM tbl_order WHERE DATE(bill_date)='$today_date'";
$res_today_orders_3 = $conn->query($sql_today_orders_3);

while($oRow = $res_today_orders_3->fetch_assoc()){
    $oid       = $oRow['id'];
    $grm_ref   = $oRow['grm_ref'];
    $p_id      = $oRow['product_id'];
    $qty       = (int)$oRow['quantity'];
    $item_disc = (float)$oRow['discount'] * $qty;

    // get order_grm
    $sql_g4 = "SELECT payment_type, discount_price, order_date
               FROM tbl_order_grm
               WHERE id = '$grm_ref' AND DATE(order_date)='$today_date'";
    $rg4 = $conn->query($sql_g4);
    $gg4 = $rg4->fetch_assoc();


    $ptype        = (int)$gg4['payment_type']; // 0=cash,1=online,2=bank,3=credit
    $billDiscount = (float)$gg4['discount_price'];

    // product price
    $sql_pp = "SELECT price FROM tbl_product WHERE id='$p_id'";
    $rp_pp  = $conn->query($sql_pp);
    $row_pp = $rp_pp->fetch_assoc();
    $price  = $row_pp ? (float)$row_pp['price'] : 0;

    $price = $price - ($item_disc + $billDiscount);

    $rawOrderValue = $price * $qty;

    // Check returns for this order
    $returnsForThisOrder = 0;
    $sql_ret_this = "SELECT * FROM tbl_return_exchange WHERE or_id='$oid'";
    $res_ret_this = $conn->query($sql_ret_this);
    if($res_ret_this->num_rows == 1){
        $netOrder = 0;
    }
    else {
      $netOrder = $rawOrderValue;
    }

    // net after discount + returns

    if($netOrder < 0){
        $netOrder = 0;
    }

    switch($ptype){
        case 0: $total_payments_today['cash']   += $netOrder; break;
        case 1: $total_payments_today['online'] += $netOrder; break;
        case 2: $total_payments_today['bank']   += $netOrder; break;
        case 3: $total_payments_today['credit'] += $netOrder; break;
    }
}

echo $total_payments_today['cash'];

 ?>
