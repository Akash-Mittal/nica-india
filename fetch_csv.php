<?php
// URL of the published Google Sheet CSV
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSrItDolNht1WdX7M8o7bjkkTpizpHA49TNVPM8dvEjG-GktrhhIQcUeNmHCcs3rFNPzxH_H5M-GUbR/pub?gid=1011241333&single=true&output=csv";

// Fetch CSV content
$csvData = file_get_contents($csvUrl);

if ($csvData === false) {
    die("Error fetching CSV data.");
}

// Convert CSV to array
$rows = array_map('str_getcsv', explode("\n", $csvData));
return $rows;
