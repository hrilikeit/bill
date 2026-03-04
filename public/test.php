<?php

$filename = 'example.txt';
$data = "Cron started at: " . date('Y-m-d-m-Y-H-i-s') . PHP_EOL;

// Write the data to the file
file_put_contents($filename, $data, FILE_APPEND);

echo "done";