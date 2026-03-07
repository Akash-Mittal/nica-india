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

function extract_mobile_from_whatsapp_url(string $waChat): string
{
    $waChat = trim($waChat);
    if ($waChat === '') {
        return '';
    }

    if (preg_match('~wa\.me/(\d+)~', $waChat, $matches)) {
        return $matches[1];
    }

    return preg_replace('/\D+/', '', $waChat);
}

function buildAnniversaryRows(array $anniversaries, string $mode): string
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
        $mobile = extract_mobile_from_whatsapp_url($waChat);

        if ($milestone === '' || $name === '') {
            continue;
        }

        $lines = [];
        $lines[] = "🎉✨*{$milestone}*";

        if ($mode === 'congrats') {
            $lines[] = "🥳 {$name} 🎊";
            if ($waChat !== '') {
                $lines[] = "💬 Chat: {$waChat}";
            }
            if ($waCongrats !== '') {
                $lines[] = "👏 Congrats: {$waCongrats}";
            }
        } elseif ($mode === 'chat') {
            $lines[] = $waChat !== ''
                ? "🥳 {$name} {$waChat} 🎊"
                : "🥳 {$name} 🎊";
        } elseif ($mode === 'mobile') {
            $lines[] = $mobile !== ''
                ? "🥳 {$name} {$mobile} 🎊"
                : "🥳 {$name} 🎊";
        } else {
            continue;
        }

        $chunks[] = implode("\n", $lines);
    }

    if (empty($chunks)) {
        return "No sobriety anniversaries found today.\n━━━━━━━━━━━━━━━━━━";
    }

    return implode("\n", $chunks);
}

function buildMessage(string $templateText, string $titleDate, string $anniversaryRows): string
{
    $replacements = [
        '{{TITLE_DATE}}' => wa_escape($titleDate),
        '{{ANNIVERSARY_ROWS}}' => $anniversaryRows,
        '{{HELPLINE_ROWS}}' => '',
    ];

    return ensure_utf8(strtr($templateText, $replacements));
}

$anniversaryList = is_array($anniversaries) ? $anniversaries : [];

$rowsCongrats = buildAnniversaryRows($anniversaryList, 'congrats');
$rowsChat = buildAnniversaryRows($anniversaryList, 'chat');
$rowsMobile = buildAnniversaryRows($anniversaryList, 'mobile');

$messageCongrats = buildMessage($templateText, $titleDate, $rowsCongrats);
$messageChat = buildMessage($templateText, $titleDate, $rowsChat);
$messageMobile = buildMessage($templateText, $titleDate, $rowsMobile);

$waShareCongrats = 'https://wa.me/?' . http_build_query([
        'text' => $messageCongrats,
    ], '', '&', PHP_QUERY_RFC3986);

$waShareChat = 'https://wa.me/?' . http_build_query([
        'text' => $messageChat,
    ], '', '&', PHP_QUERY_RFC3986);

$waShareMobile = 'https://wa.me/?' . http_build_query([
        'text' => $messageMobile,
    ], '', '&', PHP_QUERY_RFC3986);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Anniversary Message</title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .subtext {
            margin: 0 0 24px;
            color: #6b7280;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card h2 {
            margin: 0;
            font-size: 20px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-action {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-copy {
            background: #111827;
            color: #ffffff;
        }

        .btn-whatsapp {
            background: #25D366;
            color: #ffffff;
        }

        .copy-status {
            font-size: 13px;
            color: #059669;
            min-height: 18px;
        }

        .preview-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.55;
            font-size: 15px;
        }

        textarea.rawText {
            position: absolute;
            left: -9999px;
            opacity: 0;
            pointer-events: none;
        }

        @media (max-width: 640px) {
            body {
                padding: 16px;
            }

            h1 {
                font-size: 24px;
            }

            .card {
                padding: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>WhatsApp Anniversary Messages</h1>
    <p class="subtext">Choose the version you want to copy or open directly in WhatsApp for <?= htmlspecialchars($titleDate, ENT_QUOTES, 'UTF-8') ?>.</p>

    <div class="grid">
        <div class="card">
            <h2>With Congrats</h2>
            <div class="actions">
                <button class="btn-action btn-copy" type="button" data-target="rawCongrats">Copy Message</button>
                <a href="<?= htmlspecialchars($waShareCongrats, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-action btn-whatsapp">Open in WhatsApp</a>
            </div>
            <div class="copy-status" id="status-rawCongrats"></div>
            <div class="preview-box"><?= nl2br(htmlspecialchars($messageCongrats, ENT_QUOTES, 'UTF-8')) ?></div>
            <textarea id="rawCongrats" class="rawText"><?= htmlspecialchars($messageCongrats, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="card">
            <h2>With Chat</h2>
            <div class="actions">
                <button class="btn-action btn-copy" type="button" data-target="rawChat">Copy Message</button>
                <a href="<?= htmlspecialchars($waShareChat, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-action btn-whatsapp">Open in WhatsApp</a>
            </div>
            <div class="copy-status" id="status-rawChat"></div>
            <div class="preview-box"><?= nl2br(htmlspecialchars($messageChat, ENT_QUOTES, 'UTF-8')) ?></div>
            <textarea id="rawChat" class="rawText"><?= htmlspecialchars($messageChat, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="card">
            <h2>With Mobile Number</h2>
            <div class="actions">
                <button class="btn-action btn-copy" type="button" data-target="rawMobile">Copy Message</button>
                <a href="<?= htmlspecialchars($waShareMobile, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-action btn-whatsapp">Open in WhatsApp</a>
            </div>
            <div class="copy-status" id="status-rawMobile"></div>
            <div class="preview-box"><?= nl2br(htmlspecialchars($messageMobile, ENT_QUOTES, 'UTF-8')) ?></div>
            <textarea id="rawMobile" class="rawText"><?= htmlspecialchars($messageMobile, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-copy').forEach(function (button) {
        button.addEventListener('click', async function () {
            const targetId = button.getAttribute('data-target');
            const raw = document.getElementById(targetId);
            const status = document.getElementById('status-' + targetId);

            try {
                await navigator.clipboard.writeText(raw.value);
                status.textContent = 'Copied!';
                setTimeout(function () {
                    status.textContent = '';
                }, 2000);
            } catch (err) {
                status.textContent = 'Copy failed — select text manually';
                raw.style.position = 'static';
                raw.style.left = 'auto';
                raw.style.opacity = '1';
                raw.style.pointerEvents = 'auto';
                raw.style.width = '100%';
                raw.style.height = '220px';
                raw.style.marginTop = '12px';
                raw.focus();
                raw.select();
            }
        });
    });
</script>
</body>
</html>
<?php
return [
    'congrats' => $messageCongrats,
    'chat' => $messageChat,
    'mobile' => $messageMobile,
];
?>
