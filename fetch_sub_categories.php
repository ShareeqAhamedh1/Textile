<?php
include 'backend/conn.php'; // Include your DB connection file

if (isset($_POST['category_id'])) {
    $category_id = $_POST['category_id'];

    $sql = "SELECT id, name FROM tbl_sub_category WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    $stmt->bind_result($id, $name);
    
    $subCategories = [];
    while ($stmt->fetch()) {
        $subCategories[] = ["id" => $id, "name" => $name];
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode($subCategories);
}
?>
