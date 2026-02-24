<?php

$anniversaries = include __DIR__ . "/find_anniversaries.php";
$helpByLocation = include __DIR__ . "/fetch_help_line.php";

$titleDate       = $_ENV['TITLE_DATE']       ?? date('d M Y');

$templatePath = __DIR__ . "/anniversary_whatsapp_Template.txt";

if (!file_exists($templatePath)) {
    throw new RuntimeException("WhatsApp template file not found: {$templatePath}");
}

$templateText = file_get_contents($templatePath);

if ($templateText === false) {
    throw new RuntimeException("Unable to read WhatsApp template file: {$templatePath}");
}

function wa_escape(string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/[ \t]+/", " ", $value);
    return trim($value);
}

function wa_friendly_url(string $phone): ?string {
    $apiUrl = "https://mittal.blog/nica-india/services/whatsapp/URLService.php?mobile=" . urlencode($phone);
    $response = @file_get_contents($apiUrl);
    if ($response === false) return null;
    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['success']) || empty($data['wa_url'])) return null;
    return (string)$data['wa_url'];
}

function buildAnniversaryRows(array $anniversaries): string {

    if (empty($anniversaries)) {
        return "No sobriety anniversaries found.\n━━━━━━━━━━━━━━━━━━";
    }

    $chunks = [];

    foreach ($anniversaries as $person) {

        $name = trim((string)($person['name'] ?? 'Anonymous'));
        $anonymousName = '';
        if (!empty($name)) {
            $parts = explode(' ', trim($name));
            $anonymousName = $parts[0] ?? '';
        }

        $location = wa_escape((string)($person['location'] ?? ''));
        $phone = wa_escape((string)($person['phone'] ?? ''));

        $milestones = $person['milestones'] ?? [];
        if (!is_array($milestones)) $milestones = [];

        foreach ($milestones as $milestone) {

            $milestone = wa_escape((string)$milestone);
            $displayName = $location !== '' ? "{$anonymousName} ({$location})" : $anonymousName;

            $lines = [];
            $lines[] = "✨ {$milestone}";
            $lines[] = $displayName;

            if ($phone !== '') {
                $wa = wa_friendly_url($phone);
                if ($wa) {
                    $lines[] = "💬 {$wa}";
                }
            }

            $lines[] = "━━━━━━━━━━━━━━━━━━";
            $chunks[] = implode("\n", $lines);
        }
    }

    return implode("\n", $chunks);
}

function buildHelplineRows(array $helpByLocation): string {

    if (empty($helpByLocation)) {
        return "No helpline volunteers found.";
    }

    $chunks = [];

    foreach ($helpByLocation as $location => $people) {

        $location = wa_escape((string)$location);
        if ($location === '') $location = 'Unknown';

        $lines = [];
        $lines[] = "📍 {$location}";

        if (!is_array($people) || empty($people)) {
            $lines[] = "No contacts available.";
            $lines[] = "━━━━━━━━━━━━━━━━━━";
            $chunks[] = implode("\n", $lines);
            continue;
        }

        foreach ($people as $p) {
            $name = wa_escape((string)($p['name'] ?? 'Anonymous'));
            $phone = wa_escape((string)($p['phone'] ?? ''));
            $duration = wa_escape((string)($p['sobriety_duration'] ?? ''));

            $line = "👤 {$name}";
            if ($duration !== '') $line .= " — {$duration}";
            $lines[] = $line;

            if ($phone !== '') {
                $wa = wa_friendly_url($phone);
                if ($wa) {
                    $lines[] = "💬 {$wa}";
                } else {
                    $lines[] = "📞 {$phone}";
                }
            }

            $lines[] = "";
        }

        $lines[] = "━━━━━━━━━━━━━━━━━━";
        $chunks[] = trim(implode("\n", $lines));
    }

    return implode("\n", $chunks);
}

$anniversaryRowsText = buildAnniversaryRows(is_array($anniversaries) ? $anniversaries : []);
$helplineRowsText = buildHelplineRows(is_array($helpByLocation) ? $helpByLocation : []);

$replacements = [
        'TITLE_DATE'       => wa_escape($titleDate),
        'ANNIVERSARY_ROWS' => $anniversaryRowsText,
        'HELPLINE_ROWS'    => $helplineRowsText,
];
$whatsappText = strtr($templateText, $replacements);

header('Content-Type: text/html; charset=utf-8');
?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>WhatsApp Message Preview</title>
        <link rel="stylesheet" href="/style.css">
    </head>
    <body>

    <div class="whatsapp-preview">
        <?php echo nl2br(htmlspecialchars($whatsappText, ENT_QUOTES, 'UTF-8')); ?>
    </div>

    </body>
    </html>
<?php
return $whatsappText;