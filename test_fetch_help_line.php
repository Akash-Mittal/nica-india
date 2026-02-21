<?php
$help = include __DIR__ . '/fetch_help_line.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode($help, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);