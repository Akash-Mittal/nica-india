<?php

header('Content-Type: application/json; charset=utf-8');

function normalizeWaDigits(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);

    if (strlen($digits) === 10) {
        $digits = '91' . $digits;
    }

    return $digits;
}

function buildWaUrl(string $phone, ?string $text = null): ?string {
    $digits = normalizeWaDigits($phone);

    if (strlen($digits) !== 12) {
        return null;
    }

    if ($text) {
        return "https://wa.me/{$digits}?text=" . urlencode($text);
    }

    return "https://wa.me/{$digits}";
}

/*
|--------------------------------------------------------------------------
| REST Handling
|--------------------------------------------------------------------------
| GET  /whatsapp/mobile/friendly-url?mobile=9876543210
| GET  /whatsapp/mobile/friendly-url?mobile=9876543210&text=Hello
*/

$mobile = $_GET['mobile'] ?? null;
$text   = $_GET['text'] ?? null;

if (!$mobile) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'mobile parameter is required'
    ]);
    exit;
}

$url = buildWaUrl($mobile, $text);

if (!$url) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid mobile number'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'mobile' => $mobile,
    'wa_url' => $url
]);