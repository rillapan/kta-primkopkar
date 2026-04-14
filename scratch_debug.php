<?php
$_GET['id'] = 1;
ob_start();
include 'generate_kta.php';
$output = ob_get_clean();
if (strlen($output) < 100) {
    echo "Short output, likely an error message: " . $output;
} else {
    echo "PNG output detected, length: " . strlen($output);
}
?>
