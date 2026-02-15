<?php
// find_anniversaries.php

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
$hours  = array_filter(array_map('intval', explode(',', $hoursRaw)));
$days   = array_filter(array_map('intval', explode(',', $daysRaw)));
$months = array_filter(array_map('intval', explode(',', $monthsRaw)));
$years  = array_filter(array_map('intval', explode(',', $yearsRaw)));

// Optional debug (comment out in production)
echo "DEBUG in find_anniversaries.php:\n";
echo "  Hours thresholds:  " . implode(', ', $hours) . "\n";
echo "  Days thresholds:   " . implode(', ', $days) . "\n";
echo "  Months thresholds: " . implode(', ', $months) . "\n";
echo "  Years thresholds:  " . implode(', ', $years) . "\n";
echo "  Rows loaded:       " . count($rows) . "\n\n";

// Compare dates only – today at midnight
// Allow $testToday to be injected for testing
$today = isset($testToday) ? $testToday : new DateTime('today');

// Matches array
$matches = [];

// Process each row
foreach ($rows as $i => $row) {
    // Skip header row
    if ($i === 0) {
        continue;
    }

    // Basic validation – need at least 9 columns
    if (count($row) < 9) {
        continue;
    }

    // Read values
    $timestamp     = $row[0];
    $email         = $row[1];
    $name          = $row[2];
    $phone         = $row[3];
    $sobrietyStartRaw = $row[4];
    $gender        = $row[5];
    $location      = $row[6];
    $helplineOptIn = $row[7];
    $shareAnniv    = $row[8];  // consent field

    // Skip if sobriety start date is empty
    if (trim($sobrietyStartRaw) === '') {
        continue;
    }

    // Consent check – only proceed if they agreed to share
    $consent = strtolower(trim($shareAnniv));
    $consentGiven = in_array($consent, ['yes', 'y', 'true', '1'], true);
    if (!$consentGiven) {
        continue;
    }

    // Parse sobriety date – try multiple formats
    $sobrietyDate = null;
    $raw = trim($sobrietyStartRaw);

    if ($raw !== '') {
        $sobrietyDate = DateTime::createFromFormat('m/d/Y H:i', $raw)
            ?: DateTime::createFromFormat('n/j/Y H:i', $raw)
                ?: DateTime::createFromFormat('m/d/Y', $raw)
                    ?: DateTime::createFromFormat('n/j/Y', $raw)
                        ?: DateTime::createFromFormat('Y-m-d H:i:s', $raw)
                            ?: DateTime::createFromFormat('Y-m-d H:i', $raw)
                                ?: DateTime::createFromFormat('Y-m-d', $raw);

        // Fallback to generic parser
        if (!$sobrietyDate) {
            try {
                $sobrietyDate = new DateTime($raw);
            } catch (Exception $e) {
                continue;  // invalid date → skip
            }
        }
    } else {
        continue;
    }

    // Compare dates only (ignore time)
    $sobrietyDate->setTime(0, 0, 0);

    // Skip future dates
    if ($sobrietyDate > $today) {
        continue;
    }

    // Calculate differences
    $interval   = $sobrietyDate->diff($today);
    $daysTotal  = $interval->days;
    $hoursTotal = $daysTotal * 24;
    $monthsTotal = ($interval->y * 12) + $interval->m;
    $years      = $interval->y;

    $milestonesHit = [];

    // Exact HOURS
    foreach ($hours as $h) {
        if ($hoursTotal === $h) {
            $milestonesHit[] = $h . ' hours';
        }
    }

    // Exact DAYS
    foreach ($days as $d) {
        if ($daysTotal === $d) {
            $milestonesHit[] = $d . ' days';
        }
    }

    // Exact MONTHS (same day of month, no extra days)
    foreach ($months as $m) {
        if ($interval->y === 0 && $interval->m === $m && $interval->d === 0) {
            $milestonesHit[] = $m . ' months';
        }
    }

    // Exact YEARS (same month & day)
    foreach ($years as $y) {
        if ($interval->y === $y && $interval->m === 0 && $interval->d === 0) {
            $milestonesHit[] = $y . ' years';
        }
    }

    // Skip if no milestones matched
    if (empty($milestonesHit)) {
        continue;
    }

    // Store the match
    $matches[] = [
        'name'            => $name,
        'email'           => $email,
        'phone'           => $phone,
        'sobriety_start'  => $sobrietyDate->format('Y-m-d'),
        'gender'          => $gender,
        'location'        => $location,
        'helpline_optin'  => $helplineOptIn,
        'share_anniv'     => $shareAnniv,
        'milestones'      => $milestonesHit,
    ];
}

return $matches;