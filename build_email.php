<?php
// build_email.php

// Include the anniversaries data (assuming find_anniversaries.php returns array)
$anniversaries = include __DIR__ . "/find_anniversaries.php";

// ────────────────────────────────────────────────
// Read values from .env (via $_ENV)
$fellowshipName   = $_ENV['FELLOWSHIP_NAME']   ?? 'NICA India Fellowship';
$emailRecipients  = $_ENV['EMAIL_RECIPIENTS']  ?? 'mail.akash.on@gmail.com,mittal.akash.adam@gmail.com';
$emailSubject     = $_ENV['EMAIL_SUBJECT']     ?? 'NICA India – Upcoming Sobriety Anniversaries';

// Optional debug (comment out or remove in production)
echo "DEBUG in build_email.php:\n";
echo "  Fellowship name: $fellowshipName\n";
echo "  Recipients:      $emailRecipients\n";
echo "  Subject:         $emailSubject\n";
echo "  Found anniversaries: " . count($anniversaries) . "\n\n";

// ────────────────────────────────────────────────
// Build the plain text body
if (empty($anniversaries)) {
    $plainTextBody = "Dear members,\n\n"
        . "There are currently no sobriety anniversaries matching the configured milestones.\n\n"
        . "In service and fellowship,\n"
        . $fellowshipName;
} else {
    $plainTextBody = "Dear members,\n\n";
    $plainTextBody .= "We are pleased to share the details of members whose sobriety milestones "
        . "fall within the configured time windows.\n\n";
    $plainTextBody .= "The following members are celebrating important sobriety anniversaries:\n\n";

    foreach ($anniversaries as $person) {
        $plainTextBody .= "- Name: " . ($person["name"] ?? 'Unknown') . "\n";
        $plainTextBody .= "  Milestone(s): " . implode(", ", $person["milestones"] ?? []) . "\n";
        $plainTextBody .= "  Location: " . ($person["location"] ?? 'Not specified') . "\n";
        $plainTextBody .= "  Phone (WhatsApp): " . ($person["phone"] ?? 'Not provided') . "\n\n";
    }

    $plainTextBody .= "We invite you to join us in extending warm wishes and support to these members "
        . "as they mark their sobriety milestones.\n\n";
    $plainTextBody .= "In service and fellowship,\n";
    $plainTextBody .= $fellowshipName;
}

// ────────────────────────────────────────────────
// Prepare the email array (same structure as before)
$email = [
    "to"      => $emailRecipients,
    "subject" => $emailSubject,
    "body"    => $plainTextBody,  // plain text – easy to copy into WhatsApp
];

// Optional: more debug (remove later)
echo "Generated email body preview (first 200 chars):\n";
echo substr($plainTextBody, 0, 200) . "...\n\n";

return $email;