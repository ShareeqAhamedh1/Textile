<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $city = $_POST['city'];

    $stmt = $conn->prepare("INSERT INTO tbl_customer (c_name, c_phone, c_email, c_address, c_city) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $phone, $email, $address, $city);

    if ($stmt->execute()) {
        echo "200"; // Success
    } else {
        echo "500"; // Failure
    }

    $stmt->close();
}
?>
