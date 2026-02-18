<?php

function validateUrl(string $url): array
{
    if (!$url) {
        return [
            "status" => false,
            "message" => "No URL provided"
        ];
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return [
            "status" => false,
            "message" => "Invalid URL format"
        ];
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return [
            "status" => false,
            "message" => "Connection error",
            "error" => $error
        ];
    }

    $is_valid = ($http_code >= 200 && $http_code < 400);

    return [
        "status" => $is_valid,
        "http_code" => $http_code,
        "url" => $url
    ];
}

$url = isset($_GET['url']) ? trim($_GET['url']) : "";
$wantJson = isset($_GET['json']) && $_GET['json'] === "1";

$result = null;
if ($url !== "" || isset($_GET['url'])) {
    $result = validateUrl($url);
}

if ($wantJson) {
    header('Content-Type: application/json');
    echo json_encode($result ?? ["status" => false, "message" => "No URL provided"]);
    exit;
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>URL Validator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="card">
        <h2>URL Validator</h2>
        <p>Enter a full URL to check if it is valid and reachable.</p>

        <form method="get" action="" class="form-row">
            <input
                class="input"
                type="url"
                name="url"
                value="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"
                placeholder="https://example.com"
                required
            >

            <div class="btn-row">
                <button class="btn" type="submit">Check</button>
                <button class="btn btn-secondary" type="button" onclick="clearForm()">Clear</button>
            </div>
        </form>

        <div class="result" id="result">
            <?php if ($result !== null): ?>
                <?php
                $ok = isset($result['status']) && $result['status'] === true;
                $title = $ok ? "✅ Valid / Reachable" : "❌ Not Valid";
                $msg = $result['message'] ?? '';
                $code = $result['http_code'] ?? null;
                $err = $result['error'] ?? null;
                $alertClass = $ok ? "alert alert-success" : "alert alert-error";
                ?>

                <div class="<?php echo $alertClass; ?>">
                    <div class="alert-title"><?php echo $title; ?></div>

                    <div class="kv"><strong>URL:</strong> <?php echo htmlspecialchars($url, ENT_QUOTES); ?></div>

                    <?php if ($code !== null): ?>
                        <div class="kv"><strong>HTTP Code:</strong> <?php echo htmlspecialchars((string)$code, ENT_QUOTES); ?></div>
                    <?php endif; ?>

                    <?php if ($msg): ?>
                        <div class="kv"><strong>Message:</strong> <?php echo htmlspecialchars($msg, ENT_QUOTES); ?></div>
                    <?php endif; ?>

                    <?php if ($err): ?>
                        <div class="kv"><strong>Error:</strong> <?php echo htmlspecialchars($err, ENT_QUOTES); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="hr"></div>
        <p class="meta">Tip: Add <strong>&amp;json=1</strong> to get JSON output (for tools/tests).</p>
    </div>
</div>

<script>
    function clearForm(){
        const input = document.querySelector('input[name="url"]');
        if (input) input.value = '';
        const result = document.getElementById('result');
        if (result) result.innerHTML = '';
        if (history && history.replaceState) {
            history.replaceState(null, '', window.location.pathname);
        }
    }
</script>

</body>
</html>
