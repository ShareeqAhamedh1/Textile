<?php
include './backend/conn.php';

if (!isset($_GET['id'])) {
    die("Product ID not provided.");
}

$productId = intval($_GET['id']);

// Fetch Product Details
$sql = "SELECT * FROM tbl_product WHERE id = $productId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    $prodCost = $product['cost_price'];

    // Fetch HS Code
    $sqlHs = "SELECT * FROM tbl_stock_grm WHERE stock_hs_price = '$prodCost'";
    $rsHs = $conn->query($sqlHs);
    $hscode = ($rsHs->num_rows > 0) ? $rsHs->fetch_assoc()['stock_ref'] : "";
} else {
    die("Product not found.");
}

// Generate Barcode URL
$barcodeUrl = "https://bwipjs-api.metafloor.com/?bcid=code128&text=" . urlencode($product['barcode']) . "&scale=3&width=40&height=10"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode</title>
    <style>
        @media print {
            @page {
                size: 30mm 20mm;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .btn-print {
                display: none;
            }
        }

        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }

        .barcode-container {
            width: 30mm;
            height: 20mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1mm;
            box-sizing: border-box;
            /* border: 1px solid black; */
        }

        .barcode-item {
            width: 22mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .barcode-item h4 {
            font-size: 8px;
            margin: 1px 0;
        }

        .barcode-item p {
            font-size: 7px;
            margin: 1px 0;
        }

        .barcode-item img {
            width: 22mm;
            height: 8mm;
            object-fit: contain;
            filter: contrast(300%) brightness(30%);
        }

        .hs-code {
            font-size: 6px;
            width: 5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
        }

        .btn-print {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .barcode-item p:last-child {
    font-size: 10px; /* Adjust this value as needed */
    font-weight: bold;
}
    </style>
</head>
<body onload="window.print();">
    <div class="barcode-container">
        <div class="hs-code">
            <p><?= htmlspecialchars($hscode) ?></p>
        </div>
        <div class="barcode-item">
            <h4>I Style</h4>
            
            <img src="<?= $barcodeUrl ?>" alt="Barcode">
            <p><?= htmlspecialchars($product['barcode']) ?></p>
            <p>Rs <?= number_format($product['price']) ?>/=</p>
        </div>
    </div>

    <button class="btn-print" onclick="window.print();">Print</button>
</body>
</html>
