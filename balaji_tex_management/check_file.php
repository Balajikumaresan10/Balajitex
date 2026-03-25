<?php
$dbPath = realpath(__DIR__ . '/../data/app.db');
echo 'Expected DB path: ' . $dbPath . '<br>';
echo 'File exists: ' . (file_exists($dbPath) ? 'YES' : 'NO') . '<br>';
echo 'File size: ' . (file_exists($dbPath) ? filesize($dbPath) : 'N/A') . ' bytes<br>';
echo 'Directory writable: ' . (is_writable(dirname($dbPath)) ? 'YES' : 'NO') . '<br>';
?>
