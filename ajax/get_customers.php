<?php
include '../backend/conn.php';

$query = "SELECT c_id , c_name FROM tbl_customer ORDER BY c_name ASC";
$result = $conn->query($query);

$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode($customers);
?>
