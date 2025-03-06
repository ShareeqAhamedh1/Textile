<?php
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $expense_date = $_POST['expense_date'];
    $cash_in_out = $_POST['cash_in_out'];

    $currDate=date('Y-m-d');
    // Fetch yesterday's closing balance as today's opening balance
    $sql_opening_balance = "SELECT * FROM tbl_expenses WHERE expense_date = '$currDate' AND category='Opening Balance'";
    $rs_opening_balance = $conn->query($sql_opening_balance);
    if ($rs_opening_balance->num_rows > 0) {
        header("Location: ../manage_expenses.php?status=fail");
        exit();
    }



    // $vendor_id = (int)$_POST['vendor_id'];
    // $payment_type = $_POST['payment_type'];

    // Determine vendor_id based on category
    if ($category == 'petty_cash') {
        $vendor_id = 0; // Petty cash has no vendor
    } else {
        $vendor_id = $_POST['vendor_id']; // Get vendor_id from form
        $payment_type=$_POST['payment_type'];
    }

    // Validate required fields
    if (empty($amount) || empty($description) || empty($category) || empty($expense_date)) {
        echo json_encode(["statusCode" => 400, "message" => "All fields are required"]);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into tbl_expenses
        $sql = "INSERT INTO tbl_expenses (amount, description, category, expense_date, vendor_id, cash_in_out, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Debugging statement
}
// Corrected Bind Parameters: "dsssii" (double, string, string, string, integer, integer)
$stmt->bind_param("dsssii", $amount, $description, $category, $expense_date, $vendor_id, $cash_in_out);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error); // Debugging statement
}
$expense_id = $stmt->insert_id; // Get last inserted expense_id
$stmt->close();

        // If vendor_id is not 0, insert into tbl_vendor_payments
        if ($vendor_id != 0) {
            $payment_method = $_POST['payment_type'] ?? 'OnlinePay'; // Default to cash if not provided
            $reference_number = $_POST['reference_number'] ?? null; // Optional reference number
            $remarks = $_POST['remarks'] ?? null; // Optional remarks

            $sql = "INSERT INTO tbl_vendor_payments (vendor_id, expense_id, amount, payment_date, payment_method, reference_number, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iidssss", $vendor_id, $expense_id, $amount, $expense_date, $payment_method, $reference_number, $remarks);
            $stmt->execute();
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();



        // Redirect with success status
        header("Location: ../manage_expenses.php?status=success");
        exit;
    } catch (Exception $e) {
        // Rollback transaction if an error occurs
        $conn->rollback();
        echo json_encode(["statusCode" => 500, "message" => "Error: " . $e->getMessage()]);
    }
}

// Close database connection
$conn->close();
?>
