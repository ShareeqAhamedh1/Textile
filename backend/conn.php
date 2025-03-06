<?php

date_default_timezone_set('Asia/Colombo');


session_start();
// $servername = "localhost";
// $username = "posfkpop_card_system_admin";
// $password = "Dh{Ad{qew{Rr";
// $dbname = "posfkpop_card_system";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_textiles";

$conn = new mysqli($servername,$username,$password,$dbname);

function generateOrderRef($conn) {
    // Fetch the last inserted order_ref for today's date
    $query = "SELECT order_ref FROM tbl_order_grm
              WHERE order_ref LIKE '".date('Y-m-d')."-%'
              ORDER BY id DESC LIMIT 1";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastOrderRef = $row['order_ref'];

        // Extract the numeric part after the last hyphen (-)
        preg_match('/-(\d+)$/', $lastOrderRef, $matches);
        $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
    } else {
        $lastNumber = 0; // If no previous order, start from 0
    }

    // Increment the number (no leading zeros)
    $newNumber = $lastNumber + 1;

    // Generate new order reference
    return date('Y-m-d') . "-" . $newNumber;
}


function getReturnValue($conn, $p_id){
    $sqlReturn = "
        SELECT SUM(t_o.quantity * p.price) AS returnValue
        FROM tbl_return_exchange AS t_re
        JOIN tbl_order AS t_o ON t_o.id = t_re.or_id
        JOIN tbl_product AS p ON p.id = t_o.product_id
        WHERE t_re.p_id = '$p_id'
    ";
    $rsReturn = $conn->query($sqlReturn);

    $returnValue = 0;
    if ($rsReturn && $rsReturn->num_rows > 0) {
        $rowReturn = $rsReturn->fetch_assoc();
        $returnValue = $rowReturn['returnValue'] ?: 0;
    }
    return $returnValue;
}

function getReturnCost($conn, $p_id){
    $sqlReturn = "
        SELECT SUM(t_o.quantity * p.cost_price) AS returnCost
        FROM tbl_return_exchange AS t_re
        JOIN tbl_order AS t_o ON t_o.id = t_re.or_id
        JOIN tbl_product AS p ON p.id = t_o.product_id
        WHERE t_re.p_id = '$p_id'
    ";
    $rsReturn = $conn->query($sqlReturn);

    $returnCost = 0;
    if ($rsReturn && $rsReturn->num_rows > 0) {
        $rowReturn = $rsReturn->fetch_assoc();
        $returnCost = $rowReturn['returnCost'] ?: 0;
    }
    return $returnCost;
}
function currentStockCount($conn, $p_id) {
    // 1) Get total ordered quantity for the product
    //    EXCLUDING orders that have a matching row in tbl_return_exchange.
    $sqlOrder = "SELECT SUM(quantity) AS orderQty
                 FROM tbl_order
                 WHERE product_id = '$p_id'
                   AND id NOT IN (SELECT or_id FROM tbl_return_exchange)";
    $rsOrder = $conn->query($sqlOrder);
    $orderQty = 0;
    if ($rsOrder && $rsOrder->num_rows > 0) {
        $rowOrder = $rsOrder->fetch_assoc();
        $orderQty = $rowOrder['orderQty'] ? $rowOrder['orderQty'] : 0;
    }

    // 2) Get total stock from tbl_expiry_date for the product.
    $sqlExpiry = "SELECT SUM(quantity) AS expiryQty
                  FROM tbl_expiry_date
                  WHERE product_id = '$p_id'";
    $rsExpiry = $conn->query($sqlExpiry);
    $expiryQty = 0;
    if ($rsExpiry && $rsExpiry->num_rows > 0) {
        $rowExpiry = $rsExpiry->fetch_assoc();
        $expiryQty = $rowExpiry['expiryQty'] ? $rowExpiry['expiryQty'] : 0;
    }

    // 3) Get total quantity that was returned for this product
    //    by joining tbl_return_exchange and tbl_order on or_id.
    //    If you only want to include items that are marked as returned,
    //    add: AND t_re.ret_or_ex_st = 1
    $sqlReturn = "SELECT SUM(t_o.quantity) AS returnQty
                  FROM tbl_return_exchange AS t_re
                  JOIN tbl_order AS t_o
                    ON t_o.id = t_re.or_id
                  WHERE t_re.p_id = '$p_id'";
    $rsReturn = $conn->query($sqlReturn);
    $returnQty = 0;
    if ($rsReturn && $rsReturn->num_rows > 0) {
        $rowReturn = $rsReturn->fetch_assoc();
        $returnQty = $rowReturn['returnQty'] ? $rowReturn['returnQty'] : 0;
    }

    // 4) Calculate the current stock:
    //    Total stock from expiry - (ordered but not returned) + (returned qty)
    $currentStock = $expiryQty - $orderQty + $returnQty;

    return $currentStock;
}




function currentStockCountByLocation($conn,$p_id,$s_point_id){

  $sqlMinStock = "SELECT SUM(quantity) AS qnty FROM tbl_order WHERE product_id='$p_id'";
  $rsMinStock = $conn->query($sqlMinStock);

  if($rsMinStock->num_rows > 0){
    $rowMinStock = $rsMinStock->fetch_assoc();
    $redStoc = $rowMinStock['qnty'];
  }

  $sqlMinStockCall = "SELECT SUM(quantity) AS qnty_call FROM tbl_order_temp WHERE product_id='$p_id' AND status !='0' AND status !='1'";
  $rsMinStockCall = $conn->query($sqlMinStockCall);

  if($rsMinStockCall->num_rows > 0){
    $rowMinStockCall = $rsMinStockCall->fetch_assoc();
    $redStocCall = $rowMinStockCall['qnty_call'];
  }

  $redStoc +=$redStocCall;

  $sqlSub = "SELECT SUM(quantity) AS quantity FROM tbl_expiry_date WHERE product_id='$p_id' AND s_point_id='$s_point_id'";
  $rsSub = $conn->query($sqlSub);
  if($rsSub->num_rows >0){
    $rowSub = $rsSub->fetch_assoc();

    $quantity = $rowSub['quantity'] - $redStoc;
 }
 else {
    $quantity = 0;
 }


  $sql_tally = "SELECT * FROM tbl_tally_stock WHERE product_id='$p_id'";
  $rs_tally = $conn->query($sql_tally);
  if($rs_tally->num_rows > 0){
    while($row_tally = $rs_tally->fetch_assoc()){
      $tally_qnty = $row_tally['new_quantity'];
      $plus_minus = $row_tally['add_minus'];
      if($plus_minus == 1){
        $quantity += $tally_qnty;
      }
      elseif ($plus_minus == 2) {
        $quantity -=$tally_qnty;
      }
    }


  }


 return $quantity;
}


function getDataBack($conn,$table,$col_id,$id,$coulmn){
  $sql = "SELECT * FROM $table WHERE $col_id = '$id'";
  $rs = $conn->query($sql);

  if ($rs->num_rows > 0) {
    $row = $rs->fetch_assoc();

    return $row[$coulmn];
  }
}

function getPickup($id){
  switch ($id) {
    case "9":
        return "Koko Pay";
        break;
    case "8":
        return "Mint Pay";
        break;
    case "1":
        return "Daraz";
        break;
    case "2":
        return "Pick Me";
        break;
    case "3":
        return "Kiddoz";
        break;
    case "4":
        return "Website";
        break;
    case "5":
        return "Social Media";
        break;
    case "6":
        return "Call";
        break;
    case "7":
        return "Walk In Customer";
        break;
    case "0":
        return "Others";
        break;
      }
    }

    function getPayment($id){
      switch ($id) {
        case "0":
            return "Cash";
        case "1":
            return "Online Payment";
            break;
        case "2":
            return "Bank Transfer";
            break;
        case "3":
            return "Credit";
            break;
        case "4":
            return "Cash On Delivery";
            break;
          }
        }

function getData($conn,$table,$col_id,$id,$coulmn){
  $sql = "SELECT * FROM $table WHERE $col_id = '$id'";
  $rs = $conn->query($sql);

  if ($rs->num_rows > 0) {
    $row = $rs->fetch_assoc();

    echo $row[$coulmn];
  }
  else {
    echo "Nothing Found";
  }
}

function uploadImage($fileName,$filePath,$allowedList,$errorLocation){

  $img = $_FILES[$fileName];
  $imgName =$_FILES[$fileName]['name'];
  $imgTempName = $_FILES[$fileName]['tmp_name'];
  $imgSize = $_FILES[$fileName]['size'];
  $imgError= $_FILES[$fileName]['error'];

  $fileExt = explode(".",$imgName);
  $fileActualExt = strtolower(end($fileExt));

  $allowed = $allowedList;

  if(in_array($fileActualExt, $allowed)){
    if($imgError == 0){
      $GLOBALS['fileNameNew']='posfive'.uniqid('',true).".".$fileActualExt;
        $fileDestination = $filePath.$GLOBALS['fileNameNew'];

        $resultsImage = move_uploaded_file($imgTempName,$fileDestination);

      }
      else{
        header('location:'.$errorLocation.'?imgerror');
        exit();
      }
  }
  else{
    header('location:'.$errorLocation.'?extensionError&'.$fileActualExt);
    exit();
  }
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
