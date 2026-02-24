<?php
// find_anniversaries.php

$DATE_FORMAT = 'm/d/Y';

if (!isset($rows)) {
    $rows = include __DIR__ . '/fetch_csv.php';
}

date_default_timezone_set('Asia/Kolkata');

$hoursRaw  = $_ENV['HOURS']  ?? '';
$daysRaw   = $_ENV['DAYS']   ?? '';
$monthsRaw = $_ENV['MONTHS'] ?? '';
$yearsRaw  = $_ENV['YEARS']  ?? '';

$configuredHours  = array_filter(array_map('intval', explode(',', $hoursRaw)));
$configuredDays   = array_filter(array_map('intval', explode(',', $daysRaw)));
$configuredMonths = array_filter(array_map('intval', explode(',', $monthsRaw)));
$configuredYears  = array_filter(array_map('intval', explode(',', $yearsRaw)));

$today = isset($testToday) ? clone $testToday : new DateTime('today');
$today->setTime(0, 0);

$matches = [];

foreach ($rows as $i => $row) {
    if ($i === 0) continue;
    if (!is_array($row) || count($row) < 9) continue;

    $row = array_map('trim', $row);
    [$timestamp, $email, $name, $phone, $sobrietyStartRaw, $gender, $location, $helplineOptIn, $shareAnniv] = $row;

    if ($sobrietyStartRaw === '') continue;

    $consent = strtolower(trim($shareAnniv));
    if (!in_array($consent, ['yes', 'y', 'true', '1'], true)) continue;

    $sobrietyDate = DateTime::createFromFormat($DATE_FORMAT, $sobrietyStartRaw);
    if (!$sobrietyDate) {
        try {
            $sobrietyDate = new DateTime($sobrietyStartRaw);
        } catch (Exception $e) {
            continue;
        }
    }
    $sobrietyDate->setTime(0, 0);

    if ($sobrietyDate > $today) continue;

    $interval = $sobrietyDate->diff($today);
    $daysTotal = $interval->days;
    $hoursTotal = $daysTotal * 24;

    $milestonesHit = [];

    foreach ($configuredHours as $h) {
        if ($hoursTotal === $h) $milestonesHit[] = $h . ' hours';
    }

    foreach ($configuredDays as $d) {
        if ($daysTotal === $d) $milestonesHit[] = $d . ' days';
    }

    foreach ($configuredMonths as $m) {
        $milestoneMonth = (clone $sobrietyDate)->modify("+$m months");
        $milestoneMonth->setTime(0, 0);
        if ($milestoneMonth == $today) $milestonesHit[] = $m . ' months';
    }

    foreach ($configuredYears as $y) {
        $anniversary = (clone $sobrietyDate)->modify("+$y years");
        $anniversary->setTime(0, 0);
        if ($anniversary == $today) $milestonesHit[] = $y . ' years';
    }

    if (!empty($milestonesHit)) {
        $matches[] = [
            '_sobriety_ts'   => $sobrietyDate->getTimestamp(),
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

usort($matches, function ($a, $b) {
    $at = (int)($a['_sobriety_ts'] ?? 0);
    $bt = (int)($b['_sobriety_ts'] ?? 0);
    if ($at === $bt) return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    return $at <=> $bt;
});

foreach ($matches as &$m) {
    unset($m['_sobriety_ts']);
}
unset($m);

return $matches;