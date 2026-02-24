<?php

header('Content-Type: application/json; charset=utf-8');

function toAnonymousName(string $name): string {
    $name = trim($name);
    if ($name === '') return 'Anonymous';

    $name = preg_replace('/\s+/', ' ', $name);

    $parts = explode(' ', $name);
    $first = trim((string)($parts[0] ?? ''));

    if ($first === '') return 'Anonymous';
    return $first;
}

$name = $_GET['name'] ?? null;

if ($name === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'name parameter is required']);
    exit;
}

$anonymous = toAnonymousName((string)$name);

echo json_encode([
    'success' => true,
    'name' => (string)$name,
    'anonymous_name' => $anonymous
]);