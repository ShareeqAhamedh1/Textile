<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['c_id'])) {
    $_SESSION['c_id'] = $_POST['c_id'];
    echo "200"; // Success
} else {
    echo "500"; // Failure
}
?>
