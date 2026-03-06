<?php

ob_start();
$anniversaries = include __DIR__ . "/find_anniversaries.php";
ob_end_clean();

$titleDate = $_ENV['TITLE_DATE'] ?? date('d M Y');
$templatePath = __DIR__ . "/anniversary_whatsapp_Template.txt";

if (!file_exists($templatePath)) {
    die("WhatsApp template file not found: " . htmlspecialchars($templatePath, ENT_QUOTES, 'UTF-8'));
}

$templateText = file_get_contents($templatePath);
if ($templateText === false) {
    die("Unable to read WhatsApp template file: " . htmlspecialchars($templatePath, ENT_QUOTES, 'UTF-8'));
}

/* -------------------------
   Ensure UTF-8 safe text
--------------------------*/

function ensure_utf8(string $text): string
{
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    }
    return str_replace("\xEF\xBB\xBF", '', $text);
}

function wa_escape(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/[ \t]+/", " ", $value);
    return trim($value);
}

function normalizeAnniversaryRow(array $row): array
{
    return [
        'milestone' => $row['MILE_STONE'] ?? $row['milestone'] ?? '',
        'milestone_days' => $row['MILESTONE_DAYS'] ?? $row['milestone_days'] ?? '',
        'anonymous_name' => $row['ANONYMOUS_NAME'] ?? $row['anonymous_name'] ?? 'Anonymous',
        'whatsapp_url_contact' => $row['WHATSAPP_URL_CONTACT'] ?? $row['whatsapp_url_contact'] ?? '',
        'whatsapp_url_congrats_personal' => $row['WHATSAPP_URL_CONGRATS_PERSONAL'] ?? $row['whatsapp_url_congrats_personal'] ?? '',
        'whatsapp_url_congrats_group' => $row['WHATSAPP_URL_CONGRATS_GROUP'] ?? $row['whatsapp_url_congrats_group'] ?? '',
        'show' => $row['SHOW'] ?? $row['show'] ?? '',
    ];
}

function buildAnniversaryRows(array $anniversaries): string
{

    if (empty($anniversaries)) {
        return "No sobriety anniversaries found today.\n━━━━━━━━━━━━━━━━━━";
    }

    $chunks = [];

    foreach ($anniversaries as $person) {

        if (!is_array($person)) {
            continue;
        }

        $person = normalizeAnniversaryRow($person);

        $show = strtolower(trim((string)$person['show']));
        if ($show !== 'yes') {
            continue;
        }

        $milestone = wa_escape((string)$person['milestone']);
        $name = wa_escape((string)$person['anonymous_name']);
        $waChat = trim((string)$person['whatsapp_url_contact']);
        $waCongrats = trim((string)$person['whatsapp_url_congrats_personal']);

        if ($milestone === '' || $name === '') {
            continue;
        }

        $lines = [];
        $lines[] = $milestone;
        $lines[] = $name;

        if ($waChat !== '') {
            $lines[] = "Chat: {$waChat}";
        }

        if ($waCongrats !== '') {
            $lines[] = "Congrats: {$waCongrats}";
        }

        $lines[] = "━━━━━━━━━━━━━━━━━━";

        $chunks[] = implode("\n", $lines);
    }

    if (empty($chunks)) {
        return "No sobriety anniversaries found today.\n━━━━━━━━━━━━━━━━━━";
    }

    return implode("\n", $chunks);
}

/* -------------------------
   Generate message
--------------------------*/

$anniversaryRowsText = buildAnniversaryRows(is_array($anniversaries) ? $anniversaries : []);

$replacements = [
    '{{TITLE_DATE}}' => wa_escape($titleDate),
    '{{ANNIVERSARY_ROWS}}' => $anniversaryRowsText,
    '{{HELPLINE_ROWS}}' => '',
];

$whatsappText = ensure_utf8(strtr($templateText, $replacements));

/* -------------------------
   WhatsApp link
--------------------------*/

$waShareLink = 'https://wa.me/?' . http_build_query([
        'text' => $whatsappText
    ], '', '&', PHP_QUERY_RFC3986);
header('Content-Type: text/html; charset=utf-8');

$safeHtml = nl2br(htmlspecialchars($whatsappText, ENT_QUOTES, 'UTF-8'));
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>WhatsApp Anniversary Message</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>

    <div class="container whatsapp-preview">

        <h2>WhatsApp Message Preview (<?= htmlspecialchars($titleDate, ENT_QUOTES, 'UTF-8') ?>)</h2>

        <div class="copy-area">

            <button id="copyBtn" class="btn-action btn-copy" type="button">
                Copy Message
            </button>

            <a href="<?= htmlspecialchars($waShareLink, ENT_QUOTES, 'UTF-8') ?>"
               target="_blank"
               class="btn-action btn-whatsapp">
                Open in WhatsApp
            </a>

            <span id="copyStatus"></span>

        </div>

        <div class="preview-box">

            <?= $safeHtml ?>

        </div>

        <textarea id="rawText" style="position:absolute;left:-9999px;"><?= htmlspecialchars($whatsappText, ENT_QUOTES, 'UTF-8') ?></textarea>

    </div>

    <script>

        const copyBtn = document.getElementById('copyBtn');
        const status = document.getElementById('copyStatus');
        const raw = document.getElementById('rawText');

        copyBtn.addEventListener('click', async () => {

            try {

                await navigator.clipboard.writeText(raw.value);

                status.textContent = 'Copied!';

                setTimeout(() => {
                    status.textContent = '';
                }, 2000);

            } catch (err) {

                status.textContent = 'Copy failed — select text manually';

                raw.style.position = 'static';
                raw.style.left = 'auto';
                raw.style.width = '90%';
                raw.style.height = '250px';
                raw.style.margin = '20px auto';
                raw.style.display = 'block';

                raw.focus();
                raw.select();
            }

        });

    </script>

    </body>
    </html>

<?php
return $whatsappText;
?>