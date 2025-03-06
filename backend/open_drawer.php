<?php
include './conn.php';
// Open cash drawer by sending ESC/POS command to the printer
$printer_name = "Xprinter XP-T361U"; // Set your receipt printer's name
$handle = printer_open($printer_name);

if ($handle) {
    printer_start_doc($handle, "Cash Drawer Open");
    printer_start_page($handle);
    
    // ESC/POS command to open drawer (standard: \x1B\x70\x00\x19\xFA)
    $drawerCommand = "\x1B\x70\x00\x19\xFA"; 

    printer_write($handle, $drawerCommand);
    printer_end_page($handle);
    printer_end_doc($handle);
    printer_close($handle);
    
    echo "Drawer Opened";
} else {
    echo "Printer Not Found";
}
?>