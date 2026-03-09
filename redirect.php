<?php

declare(strict_types=1);

$configFile = __DIR__ . '/redirects.json';

if (!file_exists($configFile)) {
    http_response_code(500);
    echo 'Redirect config missing';
    exit;
}

$data = file_get_contents($configFile);

if ($data === false) {
    http_response_code(500);
    echo 'Unable to read redirect config';
    exit;
}

$redirects = json_decode($data, true);

if (!is_array($redirects)) {
    http_response_code(500);
    echo 'Invalid redirect config';
    exit;
}

$key = $_GET['name'] ?? '';

$item = null;
$url = '';
$status = 302;
$title = 'Redirect Service';
$description = 'Choose a configured redirect.';
$image = 'https://yourdomain.com/default-preview.jpg';
$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . ($_SERVER['REQUEST_URI'] ?? '/redirect.php');

if ($key !== '' && isset($redirects[$key])) {
    $item = $redirects[$key];

    if (is_array($item)) {
        $url = (string) ($item['url'] ?? '');
        $status = (int) ($item['status'] ?? 302);
        $title = (string) ($item['title'] ?? $key);
        $description = (string) ($item['description'] ?? 'Redirecting you to the destination page.');
        $image = (string) ($item['image'] ?? $image);
    } else {
        $url = (string) $item;
        $title = $key;
        $description = 'Redirecting you to the destination page.';
    }

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        header('Refresh: 2; url=' . $url);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">

    <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">

    <meta property="og:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">

    <style>
        body {
            margin: 0;
            background: #f8f9fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #111;
        }

        .redirect-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 24px;
        }

        .redirect-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }

        h1 {
            margin: 0 0 8px;
        }

        p {
            color: #555;
        }

        .preview-image {
            max-width: 100%;
            border-radius: 14px;
            margin: 18px 0;
            display: block;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            background: #128C7E;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        .btn:hover {
            background: #0f7469;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
            background: #fff;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
<div class="redirect-wrapper">
    <div class="redirect-card">
        <?php if ($key !== '' && $url !== '' && filter_var($url, FILTER_VALIDATE_URL)): ?>
            <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>

            <?php if ($image !== ''): ?>
                <img class="preview-image" src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>

            <p>Redirecting in 2 seconds...</p>
            <p>
                <a class="btn" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">Continue now</a>
            </p>
        <?php else: ?>
            <h1>Redirect Service</h1>
            <p>Select a link to test redirect</p>

            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Destination</th>
                    <th>Test</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($redirects as $name => $entry): ?>
                    <?php
                    $entryUrl = is_array($entry) ? (string) ($entry['url'] ?? '') : (string) $entry;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($entryUrl, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a href="?name=<?php echo urlencode((string) $name); ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>