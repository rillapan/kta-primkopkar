<?php
$output = file_get_contents("http://localhost/generate-KTA/generate_kta.php?id=1");
file_put_contents("debug_output.txt", $output);
echo "Length: " . strlen($output) . "\n";
echo "First 100 bytes:\n";
echo substr($output, 0, 100);
?>
