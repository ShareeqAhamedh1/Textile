<?php
include 'backend/conn.php';
$today_date = date("Y-m-d");
$total_payments_today = [
    'cash'   => 0,
    'online' => 0,
    'bank'   => 0,
    'credit' => 0
];

$sql_today_orders_3 = "SELECT * FROM tbl_order";
$res_today_orders_3 = $conn->query($sql_today_orders_3);
$billDisc_cash=0;
while($oRow = $res_today_orders_3->fetch_assoc()){
    $oid       = $oRow['id'];
    $grm_ref   = $oRow['grm_ref'];
    $p_id      = $oRow['product_id'];
    $qty       = (int)$oRow['quantity'];
    $item_disc = (float)$oRow['discount'] * $qty;

    // get order_grm
    $sql_g4 = "SELECT payment_type, discount_price, order_date
               FROM tbl_order_grm
               WHERE id = '$grm_ref'";
    $rg4 = $conn->query($sql_g4);
    $gg4 = $rg4->fetch_assoc();
    if(!$gg4) continue;

    $g_date       = substr($gg4['order_date'], 0, 10);
    if($g_date !== $today_date) {
        continue;
    }

    $ptype        = (int)$gg4['payment_type']; // 0=cash,1=online,2=bank,3=credit
    $billDiscount = (float)$gg4['discount_price'];

    // product price
    $sql_pp = "SELECT price FROM tbl_product WHERE id='$p_id'";
    $rp_pp  = $conn->query($sql_pp);
    $row_pp = $rp_pp->fetch_assoc();
    $price  = $row_pp ? (float)$row_pp['price'] : 0;

    $rawOrderValue = $price * $qty;

    // Check returns for this order
    $returnsForThisOrder = 0;
    $sql_ret_this = "SELECT * FROM tbl_return_exchange WHERE grm_ref='$grm_ref'";
    $res_ret_this = $conn->query($sql_ret_this);

    if($res_ret_this->num_rows == 0){
        $netOrder = $rawOrderValue - $item_disc;
    }
    else {
      $netOrder = 0;
    }

      echo $netOrder."<br>";

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

$sqlDisc="SELECT SUM(discount_price) AS bill_disc FROM tbl_order_grm WHERE payment_type=0";
$rsDisc=$conn->query($sqlDisc);
if($rsDisc->num_rows > 0){
  $rowDisc=$rsDisc->fetch_assoc();
  $billDisc_cash=$rowDisc['bill_disc'];
}

$total_payments_today['cash']   -= $billDisc_cash;

$sqlPayments ="SELECT SUM(cp_amount) AS payment FROM tbl_customer_payments";
$rsPayments=$conn->query($sqlPayments);
if($rsPayments->num_rows >0){
  $rowPay=$rsPayments->fetch_assoc();
  $total_payments_today['credit'] -=$rowPay['payment'];
  $total_payments_today['cash'] +=$rowPay['payment'];
}

echo $total_payments_today['cash'];
