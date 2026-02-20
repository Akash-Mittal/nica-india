<?php

$anniversaries = include __DIR__ . "/find_anniversaries.php";

$fellowshipName  = $_ENV['FELLOWSHIP_NAME']  ?? 'Nicotine Anonymous India';
$emailSubject    = $_ENV['EMAIL_SUBJECT']    ?? 'Upcoming Sobriety Anniversaries';
$formLink        = $_ENV['FORM_LINK']        ?? 'https://forms.gle/vNE9g1igyuvV38Pk8';
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

function wa_title_case(string $s): string {
    $s = wa_escape($s);
    return $s === '' ? '' : $s;
}

function buildWhatsappRows(array $anniversaries): string {
    if (empty($anniversaries)) {
        return "No sobriety anniversaries match the configured milestones.";
    }

    $chunks = [];

    foreach ($anniversaries as $person) {
        $rawName = trim((string)($person['name'] ?? ''));

        if ($rawName !== '') {
            $parts = preg_split('/\s+/', $rawName);
            $name = wa_escape($parts[0]);
        } else {
            $name = 'Anonymous';
        }
        $location = wa_escape((string)($person['location'] ?? ''));
        $phone = wa_escape((string)($person['phone'] ?? ''));

        $milestones = $person['milestones'] ?? [];
        if (is_array($milestones)) {
            $milestones = array_values(array_filter(array_map('wa_escape', $milestones), fn($x) => $x !== ''));
        } else {
            $milestones = [];
        }

        $displayName = $location !== '' ? "{$name} ({$location})" : $name;

        $lines = [];

        if (empty($milestones)) {
            $lines[] = "✨ *—*";
        } else {
            foreach ($milestones as $m) {
                $lines[] = "✨ *{$m}*";
            }
        }

        $lines[] = "*{$displayName}*";

        if ($phone !== '') {

            // remove spaces, +, -, brackets etc
            $digits = preg_replace('/\D+/', '', $phone);

            // if 10 digit Indian number → add country code
            if (strlen($digits) === 10) {
                $digits = '91' . $digits;
            }

            // create WA link only if 12 digits
            if (strlen($digits) === 12) {
                $lines[] = "https://wa.me/{$digits}";
            } else {
                // otherwise just show original phone
                $lines[] = $phone;
            }
        }
        $chunks[] = implode("\n", $lines);
    }

    return implode("\n━━━━━━━━━━━━━━━\n", $chunks);
}

$rowsText = buildWhatsappRows(is_array($anniversaries) ? $anniversaries : []);

$replacements = [
    '{{FELLOWSHIP_NAME}}' => wa_escape($fellowshipName),
    '{{EMAIL_SUBJECT}}'   => wa_escape($emailSubject),
    '{{TITLE_DATE}}'      => wa_escape($titleDate),
    '{{FORM_LINK}}'       => wa_escape($formLink),
    '{{ANNIVERSARY_ROWS}}'=> $rowsText,
    '{{ANNIVERSARY_COUNT}}'=> (string)count(is_array($anniversaries) ? $anniversaries : []),
];

$whatsappText = strtr($templateText, $replacements);

header('Content-Type: text/plain; charset=utf-8');
echo $whatsappText;
return $whatsappText;