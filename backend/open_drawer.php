<?php
include './conn.php';
// Open the cash drawer using ESC/POS commands
$printer = "XP-T361U"; // Change this to your printer name
$handle = printer_open($printer);

if ($handle) {
    printer_start_doc($handle, "Open Cash Drawer");
    printer_start_page($handle);
    
    // ESC/POS command to open the cash drawer
    $drawerCommand = chr(27) . chr(112) . chr(0) . chr(25) . chr(250);
    printer_write($handle, $drawerCommand);
    
    printer_end_page($handle);
    printer_end_doc($handle);
    printer_close($handle);
    
    echo "Drawer opened successfully";
} else {
    echo "Error opening printer";
}
?>