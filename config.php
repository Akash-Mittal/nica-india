<?php
date_default_timezone_set('Asia/Kolkata');

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$base = json_decode(file_get_contents(__DIR__ . '/config.json'), true) ?? [];

return array_merge($base, [
    'smtp_host'      => getenv('SMTP_HOST')      ?: null,
    'smtp_port'      => (int)(getenv('SMTP_PORT') ?: 0),
    'smtp_username'  => getenv('SMTP_USERNAME')  ?: null,
    'smtp_password'  => getenv('SMTP_PASSWORD')  ?: null,
    'smtp_encryption'  => getenv('SMTP_ENCRYPTION')  ?: null,
    'csv_url'     => getenv('CSV_URL')     ?: null,
]);