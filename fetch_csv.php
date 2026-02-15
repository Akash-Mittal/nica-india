<?php
$config = include "config.php";

// URL of the published Google Sheet CSV
$csvUrl = $config['csv_url'];
// Fetch CSV content
$csvData = file_get_contents($csvUrl);

if ($csvData === false) {
    die("Error fetching CSV data.");
}

// Convert CSV to array
$rows = array_map('str_getcsv', explode("\n", $csvData));
return $rows;
