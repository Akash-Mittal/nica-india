<?php
// fetch_csv.php

require_once __DIR__ . '/vendor/autoload.php';   // ← add this

use Dotenv\Dotenv;

// the rest of your code...
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
// echo "Current directory: " . __DIR__ . "\n";
// echo "CSV_URL from _ENV:    '" . ($_ENV['CSV_URL'] ?? 'NOT SET') . "'\n\n";

$csvUrl = $_ENV['CSV_URL'] ?? null;// Fetch CSV content
$csvData = file_get_contents($csvUrl);

if ($csvData === false) {
    die("Error fetching CSV data.");
}

// Convert CSV to array
$rows = array_map('str_getcsv', explode("\n", $csvData));
return $rows;
