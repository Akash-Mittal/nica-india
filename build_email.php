<?php
// build_email.php

$config        = include "config.php";
$anniversaries = include "find_anniversaries.php";

$fellowshipName = $config["fellowship_name"] ?? "NICA India Fellowship";

// If no anniversaries found
if (empty($anniversaries)) {
    $plainTextBody = "Dear members,\n\n"
        . "There are currently no sobriety anniversaries matching the configured milestones.\n\n"
        . "In service and fellowship,\n"
        . $fellowshipName;

} else {
    $plainTextBody  = "Dear members,\n\n";
    $plainTextBody .= "We are pleased to share the details of members whose sobriety milestones "
        . "fall within the configured time windows.\n\n";

    $plainTextBody .= "The following members are celebrating important sobriety anniversaries:\n\n";

    foreach ($anniversaries as $person) {
        $plainTextBody .= "- Name: " . $person["name"] . "\n";
        $plainTextBody .= "  Sobriety start date: " . $person["sobriety_start"] . "\n";
        $plainTextBody .= "  Milestone(s): " . implode(", ", $person["milestones"]) . "\n";
        $plainTextBody .= "  Location: " . $person["location"] . "\n";
        $plainTextBody .= "  Phone (WhatsApp): " . $person["phone"] . "\n";
        $plainTextBody .= "  Email: " . $person["email"] . "\n";
        $plainTextBody .= "  Included in NICA India Helpline: " . $person["helpline_optin"] . "\n";
        $plainTextBody .= "  Anniversary to be shared with fellowship: " . $person["share_anniv"] . "\n";

        // blank line between members
        $plainTextBody .= "\n";
    }

    $plainTextBody .= "We invite you to join us in extending warm wishes and support to these members "
        . "as they mark their sobriety milestones.\n\n";

    $plainTextBody .= "In service and fellowship,\n";
    $plainTextBody .= $fellowshipName;
}

$email = [
    "to"      => $config["email_recipients"],
    "subject" => $config["email_subject"],
    "body"    => $plainTextBody,   // plain text – easy to copy into WhatsApp
];

return $email;