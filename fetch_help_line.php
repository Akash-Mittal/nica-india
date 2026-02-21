<?php

$DATE_FORMAT = 'm/d/Y';

if (!isset($rows)) {
    $rows = include __DIR__ . '/fetch_csv.php';
}

date_default_timezone_set('Asia/Kolkata');

$maxPerLocation = 3;

$today = new DateTime('today');
$today->setTime(0, 0);

function parse_sober_date(string $raw, string $DATE_FORMAT): ?DateTime {
    $raw = trim($raw);
    if ($raw === '') return null;

    $d = DateTime::createFromFormat($DATE_FORMAT, $raw);
    if ($d instanceof DateTime) {
        $d->setTime(0, 0);
        return $d;
    }

    try {
        $d = new DateTime($raw);
        $d->setTime(0, 0);
        return $d;
    } catch (Exception $e) {
        return null;
    }
}

function has_consent(string $v): bool {
    $v = strtolower(trim($v));
    return in_array($v, ['yes', 'y', 'true', '1'], true);
}

function sobriety_duration(DateTime $sobrietyDate, DateTime $today): string {
    $interval = $sobrietyDate->diff($today);

    if ($interval->y > 0) {
        return $interval->y . ' years';
    }
    if ($interval->m > 0) {
        return $interval->m . ' months';
    }
    return $interval->d . ' days';
}

$bucket = [];

foreach ($rows as $i => $row) {
    if ($i === 0) continue;
    if (!is_array($row) || count($row) < 9) continue;

    $row = array_map('trim', $row);

    [
        $timestamp,
        $email,
        $name,
        $phone,
        $sobrietyStartRaw,
        $gender,
        $location,
        $helplineOptIn,
        $shareAnniv
    ] = $row;

    if (!has_consent($helplineOptIn)) continue;

    $sobrietyDate = parse_sober_date($sobrietyStartRaw, $DATE_FORMAT);
    if (!$sobrietyDate) continue;
    if ($sobrietyDate > $today) continue;

    $locKey = $location !== '' ? $location : 'Unknown';

    if (!isset($bucket[$locKey])) {
        $bucket[$locKey] = [];
    }

    $bucket[$locKey][] = [
        'name' => $name !== '' ? $name : 'Anonymous',
        'phone' => $phone,
        'sobriety_duration' => sobriety_duration($sobrietyDate, $today),
        '_sobriety_ts' => $sobrietyDate->getTimestamp(),
    ];
}

$result = [];

foreach ($bucket as $loc => $items) {

    usort($items, function ($a, $b) {
        $at = (int)$a['_sobriety_ts'];
        $bt = (int)$b['_sobriety_ts'];
        if ($at === $bt) return strcasecmp($a['name'], $b['name']);
        return $at <=> $bt; // oldest sobriety first
    });

    $items = array_slice($items, 0, $maxPerLocation);

    $clean = [];
    foreach ($items as $it) {
        $clean[] = [
            'name' => $it['name'],
            'phone' => $it['phone'],
            'sobriety_duration' => $it['sobriety_duration'],
        ];
    }

    $result[$loc] = $clean;
}

return $result;