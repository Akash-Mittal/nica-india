<?php
// build_email.php

$anniversaries = include __DIR__ . "/find_anniversaries.php";

$fellowshipName  = $_ENV['FELLOWSHIP_NAME']  ?? 'NICA India Fellowship';
$emailRecipients = $_ENV['EMAIL_RECIPIENTS'] ?? 'mail.akash.on@gmail.com,mittal.akash.adam@gmail.com';
$emailSubject    = $_ENV['EMAIL_SUBJECT']    ?? 'NICA India – Upcoming Sobriety Anniversaries';

$templatePath = __DIR__ . "/anniversary_email_Template.html";
if (!file_exists($templatePath)) {
    throw new RuntimeException("Email template file not found: {$templatePath}");
}
$templateHtml = file_get_contents($templatePath);
if ($templateHtml === false) {
    throw new RuntimeException("Unable to read email template file: {$templatePath}");
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function buildAnniversaryRows(array $anniversaries): string {
    if (empty($anniversaries)) {
        return '<tr><td style="padding:12px 0;">No sobriety anniversaries match the configured milestones.</td></tr>';
    }

    $rows = '';
    foreach ($anniversaries as $person) {
        $name = trim((string)($person['name'] ?? 'Anonymous'));
        $location = trim((string)($person['location'] ?? ''));
        $displayName = $location !== '' ? "{$name} ({$location})" : $name;

        $milestones = $person['milestones'] ?? [];
        $milestoneText = is_array($milestones) && !empty($milestones) ? implode(', ', $milestones) : '—';

        $phone = trim((string)($person['phone'] ?? 'Not provided'));

        $rows .= ''
            . '<tr>'
            . '<td style="padding:12px 0; border-bottom:1px solid #eee;">'
            . '<div style="font-weight:600;">' . e($displayName) . '</div>'
            . '<div><strong>Milestone(s):</strong> ' . e($milestoneText) . '</div>'
            . '<div><strong>Phone (WhatsApp):</strong> ' . e($phone) . '</div>'
            . '</td>'
            . '</tr>';
    }

    return $rows;
}

$anniversaryRowsHtml = buildAnniversaryRows(is_array($anniversaries) ? $anniversaries : []);

$replacements = [
    '{{FELLOWSHIP_NAME}}' => e($fellowshipName),
    '{{EMAIL_SUBJECT}}'   => e($emailSubject),
    '{{ANNIVERSARY_ROWS}}'=> $anniversaryRowsHtml,
    '{{ANNIVERSARY_COUNT}}'=> (string)count(is_array($anniversaries) ? $anniversaries : []),
];

$htmlBody = strtr($templateHtml, $replacements);

$email = [
    "to"      => $emailRecipients,
    "subject" => $emailSubject,
    "body"    => $htmlBody, // HTML from template
];



echo "Email:\n";
print_r($email);
return $email;