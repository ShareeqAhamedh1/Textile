<?php
include 'conn.php';

$p_id = $_POST['p_id'];

if ($p_id) {
  $stmt = $conn->prepare("DELETE FROM tbl_item_return WHERE ret_i_id = ?");
  $stmt->bind_param("i", $p_id);
  $stmt->execute();
  echo "deleted";
} else {
  http_response_code(400);
  echo "Invalid ID";
}
?>
