<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Determine environment
$env = getenv('APP_ENV') ?: 'default';

// Select env file based on APP_ENV
switch ($env) {
    case 'test':
        $envFile = '.test.env';
        break;

    case 'prod':
        $envFile = file_exists(__DIR__ . '/.prod.env') ? '.prod.env' : '.env';
        break;

    default:
        $envFile = '.env';
        break;
}

// Load environment file
if (file_exists(__DIR__ . '/' . $envFile)) {
    $dotenv = Dotenv::createImmutable(__DIR__, $envFile);
    $dotenv->load();
}

// Optional debug
// echo "APP_ENV=$env → loaded $envFile\n";
