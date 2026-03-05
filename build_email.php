<?php
// whatsapp_anniversary_message.php

// Load anniversaries (from your latest find_anniversaries.php)
$anniversaries = include __DIR__ . "/find_anniversaries.php";

// Optional: override date if needed (or use env)
$titleDate = $_ENV['TITLE_DATE'] ?? date('d M Y');

// Path to your template file
$templatePath = __DIR__ . "/anniversary_whatsapp_Template.txt";

if (!file_exists($templatePath)) {
    die("WhatsApp template file not found: " . htmlspecialchars($templatePath));
}

$templateText = file_get_contents($templatePath);
if ($templateText === false) {
    die("Unable to read WhatsApp template file: " . htmlspecialchars($templatePath));
}

// ────────────────────────────────────────────────
// Helper: clean text for WhatsApp formatting
// ────────────────────────────────────────────────
function wa_escape(string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/[ \t]+/", " ", $value);
    return trim($value);
}

// ────────────────────────────────────────────────
// Build the anniversary section
// ────────────────────────────────────────────────
function buildAnniversaryRows(array $anniversaries): string {
    if (empty($anniversaries)) {
        return "No sobriety anniversaries found today.\n━━━━━━━━━━━━━━━━━━";
    }

    $chunks = [];

    foreach ($anniversaries as $person) {
        $milestone   = wa_escape($person['milestone']         ?? '');
        $name        = wa_escape($person['anonymous_name']    ?? 'Anonymous');
        $wa_chat     = trim($person['whatsapp_url']           ?? '');
        $wa_congrats = trim($person['whatsapp_congrats']      ?? '');

        if (empty($milestone) || empty($name)) {
            continue;
        }

        $lines = [];
        $lines[] = "✨ {$milestone}";
        $lines[] = $name;

        if ($wa_chat) {
            $lines[] = "💬 Chat: {$wa_chat}";
        }

        if ($wa_congrats) {
            $lines[] = "🎉 Congrats: {$wa_congrats}";
        }

        $lines[] = "━━━━━━━━━━━━━━━━━━";
        $chunks[] = implode("\n", $lines);
    }

    return implode("\n", $chunks);
}

// Generate content
$anniversaryRowsText = buildAnniversaryRows(is_array($anniversaries) ? $anniversaries : []);

$replacements = [
    '{{TITLE_DATE}}'       => wa_escape($titleDate),
    '{{ANNIVERSARY_ROWS}}' => $anniversaryRowsText,
    // If your template still has {{HELPLINE_ROWS}}, it will be replaced with empty string
    '{{HELPLINE_ROWS}}'    => '',
];

$whatsappText = strtr($templateText, $replacements);

// ────────────────────────────────────────────────
// Browser preview + copy button
// ────────────────────────────────────────────────
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
        <style>
            .preview-box {
                background: #f0f2f5;
                padding: 20px;
                border-radius: 10px;
                white-space: pre-wrap;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                line-height: 1.6;
                max-width: 600px;
                margin: 20px auto;
            }
            .copy-area {
                text-align: center;
                margin: 20px 0;
            }
            button {
                padding: 10px 20px;
                font-size: 16px;
                background: #25D366;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
            }
            button:hover { background: #128C7E; }
        </style>
    </head>
    <body>

    <div class="container">
        <h2>WhatsApp Message Preview (<?= htmlspecialchars($titleDate) ?>)</h2>

        <div class="copy-area">
            <button id="copyBtn">Copy to Clipboard</button>
            <span id="copyStatus" style="margin-left:15px; color:#555;"></span>
        </div>

        <div class="preview-box">
            <?= $safeHtml ?>
        </div>

        <textarea id="rawText" style="position:absolute; left:-9999px;"><?= htmlspecialchars($whatsappText, ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <script>
        const copyBtn = document.getElementById('copyBtn');
        const status = document.getElementById('copyStatus');
        const raw = document.getElementById('rawText');

        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(raw.value);
                status.textContent = 'Copied!';
                setTimeout(() => status.textContent = '', 2000);
            } catch (err) {
                status.textContent = 'Copy failed — select text manually';
                raw.style.position = 'static';
                raw.style.width = '90%';
                raw.style.height = '300px';
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
// If this file is included by another script, return the final message text
return $whatsappText;