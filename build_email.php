<?php

$anniversaries = include __DIR__ . "/find_anniversaries.php";
$helpByLocation = include __DIR__ . "/fetch_help_line.php";

$fellowshipName  = $_ENV['FELLOWSHIP_NAME']  ?? 'Nicotine Anonymous India';
$websiteUrl      = $_ENV['WEBSITE_URL']      ?? 'https://nicaindia.wordpress.com';
$formLink        = $_ENV['FORM_LINK']        ?? 'https://forms.gle/vNE9g1igyuvV38Pk8';
$businessLink    = $_ENV['BUSINESS_MEETING_LINK'] ?? 'https://nicaindia.wordpress.com/2026/02/13/agenda-items-for-next-business-meeting/';
$literatureLink  = $_ENV['LITERATURE_LINK']  ?? 'https://nicaindia.wordpress.com/downloads/';
$traditionLink   = $_ENV['TRADITION_LINK']   ?? 'https://nicaindia.wordpress.com/2026/02/18/contributing-under-7th-tradition-to-nica-india-online/';
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

function normalize_wa_digits(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 10) $digits = '91' . $digits;
    return $digits;
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
                $digits = normalize_wa_digits($phone);

                if (strlen($digits) === 12) {
                    $message = urlencode("Congratulations {$anonymousName} on your {$milestone} milestone! Proud of you. Keep going — one day at a time 🙌");
                    $lines[] = "💬 https://wa.me/{$digits}?text={$message}";
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
                $digits = normalize_wa_digits($phone);
                if (strlen($digits) === 12) {
                    $lines[] = "💬 https://wa.me/{$digits}";
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
    '{{FELLOWSHIP_NAME}}' => wa_escape($fellowshipName),
    '{{WEBSITE_URL}}'     => wa_escape($websiteUrl),
    '{{TITLE_DATE}}'      => wa_escape($titleDate),
    '{{FORM_LINK}}'       => wa_escape($formLink),
    '{{BUSINESS_MEETING_LINK}}' => wa_escape($businessLink),
    '{{LITERATURE_LINK}}' => wa_escape($literatureLink),
    '{{TRADITION_LINK}}'  => wa_escape($traditionLink),
    '{{ANNIVERSARY_ROWS}}'=> $anniversaryRowsText,
    '{{HELPLINE_ROWS}}'   => $helplineRowsText,
];

$whatsappText = strtr($templateText, $replacements);

header('Content-Type: text/plain; charset=utf-8');
echo $whatsappText;

return $whatsappText;