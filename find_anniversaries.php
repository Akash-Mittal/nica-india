<?php
// find_anniversaries.php

$config = include "config.php";
$rows   = include "fetch_csv.php";

// Set your timezone
date_default_timezone_set('Asia/Kolkata');

// Compare *dates only* – today at midnight
$today = new DateTime('today'); // 2026-02-14 00:00:00, for example

$matches = [];

// ORIGINAL CSV COLUMNS (no "Sober Duration")
// 0: Timestamp
// 1: Email Address
// 2: Name
// 3: Phone Number (WhatsApp preferred)
// 4: Sobriety Start Date
// 5: Gender
// 6: Location (State in India)
// 7: Nica India Helpline opt-in (Yes/No)
// 8: Anniversary share with fellowship (Yes/No)  <-- CONSENT

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
    $timestamp        = $row[0];
    $email            = $row[1];
    $name             = $row[2];
    $phone            = $row[3];
    $sobrietyStartRaw = $row[4];
    $gender           = $row[5];
    $location         = $row[6];
    $helplineOptIn    = $row[7];
    $shareAnniv       = $row[8];  // consent field

    // Skip if sobriety start date is empty
    if (trim($sobrietyStartRaw) === '') {
        continue;
    }

    // ✅ CONSENT CHECK – only proceed if they agreed to share anniversary
    $consent      = strtolower(trim($shareAnniv));
    $consentGiven = in_array($consent, ['yes', 'y', 'true', '1'], true);

    if (!$consentGiven) {
        continue;
    }

    // Parse sobriety date – handle common formats
    $sobrietyDate = null;
    $raw = trim($sobrietyStartRaw);

    if ($raw !== '') {
        // Try explicit formats first (US style, ISO)
        $sobrietyDate =
            DateTime::createFromFormat('m/d/Y H:i', $raw) ?:
                DateTime::createFromFormat('n/j/Y H:i', $raw) ?:
                    DateTime::createFromFormat('m/d/Y', $raw) ?:
                        DateTime::createFromFormat('n/j/Y', $raw) ?:
                            DateTime::createFromFormat('Y-m-d H:i:s', $raw) ?:
                                DateTime::createFromFormat('Y-m-d H:i', $raw) ?:
                                    DateTime::createFromFormat('Y-m-d', $raw);

        // Fallback to generic parser if above didn't work
        if (!$sobrietyDate) {
            try {
                $sobrietyDate = new DateTime($raw);
            } catch (Exception $e) {
                // Invalid date → skip this record
                continue;
            }
        }
    } else {
        continue;
    }

    // ✅ IMPORTANT: compare *dates only* (ignore time-of-day)
    $sobrietyDate->setTime(0, 0, 0);
    // $today is already midnight

    // Skip future dates
    if ($sobrietyDate > $today) {
        continue;
    }

    // Calculate date difference
    $interval = $sobrietyDate->diff($today);

    $daysTotal   = $interval->days;               // total days
    $hoursTotal  = $daysTotal * 24;               // total hours (date-only, so multiple of 24)
    $monthsTotal = ($interval->y * 12) + $interval->m;
    $years       = $interval->y;

    $milestonesHit = [];

    // EXACT HOURS milestones (e.g., 72 hours => exactly 3 days)
    if (!empty($config['hours'])) {
        foreach ($config['hours'] as $h) {
            $h = (int)$h;
            if ($hoursTotal === $h) {
                $milestonesHit[] = $h . ' hours';
            }
        }
    }

    // EXACT DAYS milestones
    if (!empty($config['days'])) {
        foreach ($config['days'] as $d) {
            $d = (int)$d;
            if ($daysTotal === $d) {
                $milestonesHit[] = $d . ' days';
            }
        }
    }

    // EXACT MONTHS milestones — same day, same year span
    if (!empty($config['months'])) {
        foreach ($config['months'] as $m) {
            $m = (int)$m;
            if ($interval->y === 0 && $interval->m === $m && $interval->d === 0) {
                $milestonesHit[] = $m . ' months';
            }
        }
    }

    // EXACT YEARS milestones — same month & day
    if (!empty($config['years'])) {
        foreach ($config['years'] as $y) {
            $y = (int)$y;
            if ($interval->y === $y && $interval->m === 0 && $interval->d === 0) {
                $milestonesHit[] = $y . ' years';
            }
        }
    }

    // If no milestones match, skip this member
    if (empty($milestonesHit)) {
        continue;
    }

    // Save this member with all useful details
    $matches[] = [
        'name'           => $name,
        'email'          => $email,
        'phone'          => $phone,
        'sobriety_start' => $sobrietyDate->format('Y-m-d'),
        'gender'         => $gender,
        'location'       => $location,
        'helpline_optin' => $helplineOptIn,
        'share_anniv'    => $shareAnniv,
        'milestones'     => $milestonesHit,
    ];
}

return $matches;