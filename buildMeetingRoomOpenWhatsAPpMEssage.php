<?php

date_default_timezone_set('Asia/Kolkata');

$titleDate = $_ENV['TITLE_DATE'] ?? date('d M Y');

$templatePath = __DIR__ . "/meetingRoomOpenWhatsAppTemplate.txt";

if (!file_exists($templatePath)) {
    throw new RuntimeException("Template file not found: {$templatePath}");
}

$templateText = file_get_contents($templatePath);

if ($templateText === false) {
    throw new RuntimeException("Unable to read template file: {$templatePath}");
}

function wa_escape(string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/[ \t]+/", " ", $value);
    return trim($value);
}

function wa_friendly_url(string $phone): ?string {
    $apiUrl = "https://mittal.blog/nica-india/services/whatsapp/URLService.php?mobile=" . urlencode($phone);
    $ctx = stream_context_create(['http' => ['timeout' => 6]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['success']) || empty($data['wa_url'])) return null;

    return (string)$data['wa_url'];
}

function anonymous_name(string $name): string {
    $apiUrl = "https://mittal.blog/nica-india/services/anonymous/NamingService.php?name=" . urlencode($name);
    $ctx = stream_context_create(['http' => ['timeout' => 6]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) return 'Anonymous';

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['success']) || empty($data['anonymous_name'])) return 'Anonymous';

    return (string)$data['anonymous_name'];
}

function fetch_top_helpline(int $limit): array {
    $limit = max(1, min(50, $limit));
    $apiUrl = "https://mittal.blog/nica-india/TopSoberService.php?limit=" . $limit;
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) return [];

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['success']) || empty($data['results']) || !is_array($data['results'])) return [];

    return $data['results'];
}

function buildHelplineRowsFromTop(array $results): string {
    if (empty($results)) {
        return "No helpline contacts available right now.";
    }

    $out = [];
    $i = 1;

    foreach ($results as $r) {
        if (!is_array($r)) continue;

        $nameRaw = wa_escape((string)($r['name'] ?? 'Anonymous'));
        $anon = anonymous_name($nameRaw);

        $location = wa_escape((string)($r['location'] ?? ''));
        $duration = wa_escape((string)($r['sobriety_duration'] ?? ''));
        $phone = wa_escape((string)($r['phone'] ?? ''));

        $line1 = "{$i}. {$anon}";
        if ($location !== '') $line1 .= " ({$location})";
        if ($duration !== '') $line1 .= " — {$duration}";
        $out[] = $line1;

        if ($phone !== '') {
            $wa = wa_friendly_url($phone);
            if ($wa) {
                $out[] = "💬 {$wa}";
            } else {
                $out[] = "📞 {$phone}";
            }
        }

        $out[] = "";
        $i++;
    }

    return rtrim(implode("\n", $out));
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
if ($limit <= 0) $limit = 12;
if ($limit > 50) $limit = 50;

$top = fetch_top_helpline($limit);
$helplineRowsText = buildHelplineRowsFromTop($top);

$replacements = [
        '{{HELPLINE_ROWS}}' => $helplineRowsText,
        '{{TITLE_DATE}}' => wa_escape($titleDate),
];

$whatsappText = strtr($templateText, $replacements);

header('Content-Type: text/html; charset=utf-8');

$raw = $whatsappText;
$safeHtml = nl2br(htmlspecialchars($whatsappText, ENT_QUOTES, 'UTF-8'));
?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Meeting Room Open WhatsApp Message</title>
        <link rel="stylesheet" href="/style.css">
    </head>
    <body>

    <div class="whatsapp-preview">
        <div style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
            <button id="copyBtn" type="button">Copy WhatsApp Text</button>
            <span id="copyStatus" style="opacity:.7;"></span>
        </div>

        <div class="preview-box">
            <?php echo $safeHtml; ?>
        </div>

        <textarea id="rawText" style="position:absolute; left:-9999px; top:-9999px;"><?php
            echo htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
            ?></textarea>
    </div>

    <script>
        const btn = document.getElementById('copyBtn');
        const status = document.getElementById('copyStatus');
        const raw = document.getElementById('rawText');

        btn.addEventListener('click', async () => {
            const text = raw.value;

            try {
                await navigator.clipboard.writeText(text);
                status.textContent = 'Copied!';
                setTimeout(() => status.textContent = '', 1200);
                return;
            } catch (e) {}

            raw.style.display = 'block';
            raw.focus();
            raw.select();
            try {
                document.execCommand('copy');
                status.textContent = 'Copied!';
                setTimeout(() => status.textContent = '', 1200);
            } catch (e) {
                status.textContent = 'Copy failed — select & copy manually.';
            }
            raw.style.display = '';
        });
    </script>

    </body>
    </html>
<?php
return $whatsappText;