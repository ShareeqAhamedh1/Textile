<?php
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_id = $_POST['expense_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $description = $_POST['description'] ?? null;
    $category = $_POST['category'] ?? null;
    $expense_date = $_POST['expense_date'] ?? null;
    $vendor_id = ($category === 'petty_cash') ? 0 : ($_POST['vendor_id'] ?? null);

    // Debug: Check if all data is received
    if (!$expense_id || !$amount || !$description || !$category || !$expense_date) {
        echo json_encode(["statusCode" => 400, "message" => "Missing required fields"]);
        exit;
    }

    // Debug: Print received data
    file_put_contents("debug_log.txt", print_r($_POST, true));

    // Update query
    $sql = "UPDATE tbl_expenses
            SET amount = ?, description = ?, category = ?, expense_date = ?, vendor_id = ?
            WHERE expense_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("dsssii", $amount, $description, $category, $expense_date, $vendor_id, $expense_id);
        if ($stmt->execute()) {
            echo json_encode(["statusCode" => 200, "message" => "success"]);
        } else {
            echo json_encode(["statusCode" => 500, "message" => "SQL Error: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["statusCode" => 500, "message" => "Database Error"]);
    }

    $conn->close();
}
?>
