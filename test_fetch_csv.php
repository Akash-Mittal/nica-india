<?php
// Include the fetch script
$rows = include 'fetch_csv.php';

// Basic test cases
echo "<pre>";

// 1. Check if rows is an array
if (is_array($rows)) {
    echo "PASS: CSV returned as array\n";
} else {
    echo "FAIL: CSV did not return as array\n";
}

// 2. Check if first row exists
if (isset($rows[0])) {
    echo "PASS: First row exists\n";
} else {
    echo "FAIL: First row missing\n";
}

// 3. Check if first row has at least one cell
if (isset($rows[0][0])) {
    echo "PASS: First cell exists: " . htmlspecialchars($rows[0][0]) . "\n";
} else {
    echo "FAIL: First cell missing\n";
}

// 4. Count total rows
echo "Total rows fetched: " . count($rows) . "\n";

echo "</pre>";
