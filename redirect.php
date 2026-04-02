<?php

declare(strict_types=1);

$csvUrl = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSrItDolNht1WdX7M8o7bjkkTpizpHA49TNVPM8dvEjG-GktrhhIQcUeNmHCcs3rFNPzxH_H5M-GUbR/pub?gid=700095459&single=true&output=csv';

function fetchRemoteContent(string $url): string|false
{
    $data = @file_get_contents($url);

    if ($data !== false) {
        return $data;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $data = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data !== false && $httpCode >= 200 && $httpCode < 300) {
            return $data;
        }
    }

    return false;
}

function loadRedirectsFromCsv(string $csvUrl): array
{
    $csvData = fetchRemoteContent($csvUrl);

    if ($csvData === false) {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($csvData));

    if (!$lines || count($lines) < 2) {
        return [];
    }

    $headers = str_getcsv(array_shift($lines));
    $headers = array_map(static fn($value) => trim((string) $value), $headers);

    $redirects = [];

    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }

        $row = str_getcsv($line);
        $row = array_pad($row, count($headers), '');
        $row = array_slice($row, 0, count($headers));

        $item = array_combine($headers, $row);

        if (!is_array($item)) {
            continue;
        }

        $key = trim((string) ($item['URL_PATH_CONTEXT'] ?? ''));
        $url = trim((string) ($item['URL'] ?? ''));

        if ($key === '') {
            continue;
        }

        $redirects[$key] = [
            'url' => $url,
            'status' => 302,
            'title' => trim((string) ($item['Title'] ?? $key)),
            'description' => trim((string) ($item['description'] ?? 'Redirecting you to the destination page.')),
            'image' => trim((string) ($item['image'] ?? '')),
        ];
    }

    return $redirects;
}

function getRedirectKey(): string
{
    if (!empty($_GET['redirect_to'])) {
        return trim((string) $_GET['redirect_to']);
    }

    if (!empty($_GET['name'])) {
        return trim((string) $_GET['name']);
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = (string) parse_url($requestUri, PHP_URL_PATH);
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

    $prefix = rtrim($scriptName, '/') . '/';

    if ($path !== '' && str_starts_with($path, $prefix)) {
        $extra = substr($path, strlen($prefix));

        if (str_starts_with($extra, 'redirect_to=')) {
            return urldecode(substr($extra, strlen('redirect_to=')));
        }
    }

    return '';
}

function isAllowedRedirectUrl(string $url): bool
{
    if ($url === '') {
        return false;
    }

    $parts = parse_url($url);

    if (!is_array($parts) || empty($parts['scheme'])) {
        return false;
    }

    $scheme = strtolower((string) $parts['scheme']);
    $allowedSchemes = ['http', 'https', 'upi', 'mailto', 'tel'];

    return in_array($scheme, $allowedSchemes, true);
}

$redirects = loadRedirectsFromCsv($csvUrl);

if ($redirects === []) {
    http_response_code(500);
    echo 'Unable to load redirect config from Google Sheet CSV';
    exit;
}

$key = getRedirectKey();

$item = null;
$url = '';
$status = 302;
$title = 'Redirect Service';
$description = 'Choose a configured redirect.';
$image = '';
$shouldRedirect = false;

$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . ($_SERVER['REQUEST_URI'] ?? '/redirect.php');

if ($key !== '' && isset($redirects[$key])) {
    $item = $redirects[$key];
    $url = (string) ($item['url'] ?? '');
    $status = (int) ($item['status'] ?? 302);
    $title = (string) ($item['title'] ?? $key);
    $description = (string) ($item['description'] ?? 'Redirecting you to the destination page.');
    $image = (string) ($item['image'] ?? '');
    $shouldRedirect = isAllowedRedirectUrl($url);

    if ($shouldRedirect) {
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

    <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">

    <meta property="og:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($image !== ''): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($image !== ''): ?>
        <meta name="twitter:image" content="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
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
        <?php if ($item !== null): ?>
            <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>

            <?php if ($description !== ''): ?>
                <p><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($image !== ''): ?>
                <img
                        class="preview-image"
                        src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                >
            <?php endif; ?>

            <?php if ($shouldRedirect): ?>
                <p>Redirecting in 2 seconds...</p>
                <p>
                    <a class="btn" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">Continue now</a>
                </p>
            <?php else: ?>
                <p>Redirect is currently unavailable.</p>
            <?php endif; ?>

        <?php else: ?>
            <h1>Redirect Service</h1>
            <p>Select a link to test redirect</p>

            <table>
                <thead>
                <tr>
                    <th>URL_PATH_CONTEXT</th>
                    <th>Destination</th>
                    <th>Test</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($redirects as $name => $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($entry['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a href="?redirect_to=<?php echo urlencode((string) $name); ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>