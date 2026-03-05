<?php
// fetch_csv.php — improved version

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$csvUrl = $_ENV['CSV_URL'] ?? null;
if (!$csvUrl) {
    die("CSV_URL not set in .env");
}

$csvData = @file_get_contents($csvUrl);
if ($csvData === false) {
    http_response_code(500);
    die("Failed to fetch CSV from: " . htmlspecialchars($csvUrl));
}

// Normalize: replace \r\n or \r with \n
$csvData = str_replace(["\r\n", "\r"], "\n", $csvData);
$csvData = trim($csvData);

// If no newlines → assume single-line jammed CSV and split on known anchors (Yes/No)
if (substr_count($csvData, "\n") <= 1) {
    // Fallback splitter: split before each "Yes" or "No" (except first header)
    $lines = preg_split('/(?=(Yes|No))/', $csvData, -1, PREG_SPLIT_NO_EMPTY);
    // Prepend header if missing
    if (!str_starts_with($lines[0] ?? '', 'MILE_STONE')) {
        array_unshift($lines, "MILE_STONE,ANONYMOUS_NAME,WHATSAPP_URL,WHATSAPP_URL_CONGRATS,SHOW");
    }
} else {
    $lines = explode("\n", $csvData);
}

// Parse each line as CSV
$rows = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    $parsed = str_getcsv($line);
    if (is_array($parsed) && !empty($parsed[0])) {
        $rows[] = $parsed;
    }
}

return $rows;