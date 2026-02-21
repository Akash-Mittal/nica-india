<?php

$anniversaries = include __DIR__ . "/find_anniversaries.php";

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

function buildWhatsappRows(array $anniversaries): string {

    if (empty($anniversaries)) {
        return "No sobriety anniversaries found.\n━━━━━━━━━━━━━━━━━━";
    }

    $chunks = [];

    foreach ($anniversaries as $person) {

        $name = trim((string)($person['name'] ?? 'Anonymous'));
        $location = wa_escape((string)($person['location'] ?? ''));
        $phone = wa_escape((string)($person['phone'] ?? ''));

        $milestones = $person['milestones'] ?? [];

        if (!is_array($milestones)) {
            $milestones = [];
        }

        foreach ($milestones as $milestone) {

            $milestone = wa_escape($milestone);

            $displayName = $location !== ''
                ? "{$name} ({$location})"
                : $name;

            $lines = [];

            // Milestone line
            $lines[] = "✨ {$milestone}";

            // Name line
            $lines[] = $displayName;

            // WhatsApp link with congratulation text
            if ($phone !== '') {

                $digits = preg_replace('/\D+/', '', $phone);

                if (strlen($digits) === 10) {
                    $digits = '91' . $digits;
                }

                if (strlen($digits) === 12) {

                    $message = urlencode(
                        "Congratulations {$name} on your {$milestone} milestone! Proud of you. Keep going — one day at a time 🙌"
                    );

                    $lines[] = "💬 https://wa.me/{$digits}?text={$message}";
                }
            }

            $lines[] = "━━━━━━━━━━━━━━━━━━";

            $chunks[] = implode("\n", $lines);
        }
    }

    return implode("\n", $chunks);
}

$rowsText = buildWhatsappRows(is_array($anniversaries) ? $anniversaries : []);

$replacements = [
    '{{FELLOWSHIP_NAME}}' => wa_escape($fellowshipName),
    '{{WEBSITE_URL}}'     => wa_escape($websiteUrl),
    '{{TITLE_DATE}}'      => wa_escape($titleDate),
    '{{FORM_LINK}}'       => wa_escape($formLink),
    '{{BUSINESS_MEETING_LINK}}' => wa_escape($businessLink),
    '{{LITERATURE_LINK}}' => wa_escape($literatureLink),
    '{{TRADITION_LINK}}'  => wa_escape($traditionLink),
    '{{ANNIVERSARY_ROWS}}'=> $rowsText
];

$whatsappText = strtr($templateText, $replacements);

header('Content-Type: text/plain; charset=utf-8');

echo $whatsappText;

return $whatsappText;