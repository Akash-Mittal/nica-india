<?php

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$csvUrl = $_ENV['CSV_URL'] ?? null;

if (!$csvUrl) {
    return [];
}

$csvData = @file_get_contents($csvUrl);

if ($csvData === false) {
    return [];
}

$csvData = str_replace(["\r\n", "\r"], "\n", $csvData);
$csvData = trim($csvData);

$lines = explode("\n", $csvData);

$rows = [];

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '') {
        continue;
    }

    $parsed = str_getcsv($line, "\t");

    if (count($parsed) <= 1) {
        $parsed = str_getcsv($line, ",");
    }

    if (!empty($parsed[0])) {
        $rows[] = $parsed;
    }
}

return $rows;