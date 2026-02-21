<?php
// find_anniversaries.php

$DATE_FORMAT = 'm/d/Y';


// If $rows is not already defined (e.g., by a test script), fetch it
if (!isset($rows)) {
    $rows = include __DIR__ . '/fetch_csv.php';
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Read milestone thresholds from .env (comma-separated strings)
$hoursRaw  = $_ENV['HOURS']  ?? '';
$daysRaw   = $_ENV['DAYS']   ?? '';
$monthsRaw = $_ENV['MONTHS'] ?? '';
$yearsRaw  = $_ENV['YEARS']  ?? '';

// Parse into integer arrays (safe: ignores empty/malformed values)
$configuredHours  = array_filter(array_map('intval', explode(',', $hoursRaw)));
$configuredDays   = array_filter(array_map('intval', explode(',', $daysRaw)));
$configuredMonths = array_filter(array_map('intval', explode(',', $monthsRaw)));
$configuredYears  = array_filter(array_map('intval', explode(',', $yearsRaw)));

// DEBUG: thresholds and CSV info
//echo "DEBUG in find_anniversaries.php:\n";
//echo "  Hours thresholds:  " . implode(', ', $configuredHours) . "\n";
//echo "  Days thresholds:   " . implode(', ', $configuredDays) . "\n";
//echo "  Months thresholds: " . implode(', ', $configuredMonths) . "\n";
//echo "  Years thresholds:  " . implode(', ', $configuredYears) . "\n";
//echo "  Rows loaded:       " . count($rows) . "\n\n";

// Compare dates only – today at midnight
$today = isset($testToday) ? clone $testToday : new DateTime('today');
$today->setTime(0,0);

// Matches array
$matches = [];

// Process each row
foreach ($rows as $i => $row) {
    if ($i === 0) {
        continue;
    } // skip header

    if (count($row) < 9) {
        continue;
    }

    // Trim all fields to avoid whitespace issues
    $row = array_map('trim', $row);
    [$timestamp, $email, $name, $phone, $sobrietyStartRaw, $gender, $location, $helplineOptIn, $shareAnniv] = $row;

    if ($sobrietyStartRaw === '') {
        continue;
    }

    // Consent check
    $consent = strtolower(trim($shareAnniv));
    if (!in_array($consent, ['yes', 'y', 'true', '1'], true)) {
        continue;
    }

    // Parse sobriety date – multiple formats
    $sobrietyDate = DateTime::createFromFormat($DATE_FORMAT, $sobrietyStartRaw);
    if (!$sobrietyDate) {
        try {
            $sobrietyDate = new DateTime($sobrietyStartRaw);
        } catch (Exception $e) {
            continue;
        }
    }
    $sobrietyDate->setTime(0,0);

    // Skip future dates
    if ($sobrietyDate > $today) {
        continue;
    }

    // Calculate differences
    $interval    = $sobrietyDate->diff($today);
    $daysTotal   = $interval->days;
    $hoursTotal  = $daysTotal * 24;

    $milestonesHit = [];

    // Exact HOURS
    foreach ($configuredHours as $h) {
        if ($hoursTotal === $h) {
            $milestonesHit[] = $h . ' hours';
        }
    }

    // Exact DAYS
    foreach ($configuredDays as $d) {
        if ($daysTotal === $d) {
            $milestonesHit[] = $d . ' days';
        }
    }

    // Exact MONTHS – calculate using modify("+X months")
    foreach ($configuredMonths as $m) {
        $milestoneMonth = (clone $sobrietyDate)->modify("+$m months");
        $milestoneMonth->setTime(0,0);
        if ($milestoneMonth == $today) {
            $milestonesHit[] = $m . ' months';
        }
    }

    // Exact YEARS – calculate using modify("+X years")
    foreach ($configuredYears as $y) {
        $anniversary = (clone $sobrietyDate)->modify("+$y years");
        $anniversary->setTime(0,0);
        if ($anniversary == $today) {
            $milestonesHit[] = $y . ' years';
        }
    }

    if (!empty($milestonesHit)) {
        $matches[] = [
                'name'           => $name,
                'email'          => $email,
                'phone'          => $phone,
                'sobriety_start' => $sobrietyDate->format($DATE_FORMAT),
                'gender'         => $gender,
                'location'       => $location,
                'helpline_optin' => $helplineOptIn,
                'share_anniv'    => $shareAnniv,
                'milestones'     => $milestonesHit,
        ];
    }
}

return $matches;
