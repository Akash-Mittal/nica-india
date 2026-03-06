<?php

$rows = include __DIR__ . '/fetch_csv.php';

$matches = [];

if (!empty($rows) && is_array($rows)) {
    $header = array_shift($rows);

    foreach ($rows as $row) {
        if (!is_array($row) || count($row) < 6) {
            continue;
        }

        $row = array_map('trim', $row);

        $milestone = $row[0] ?? '';
        $name = $row[2] ?? '';
        $whatsapp = $row[3] ?? '';
        $congratsLink = $row[4] ?? '';
        $showRaw = $row[5] ?? '';
        $show = strtolower(trim($showRaw));

        if ($show !== 'yes') {
            continue;
        }

        if ($milestone === '' || $name === '') {
            continue;
        }

        $matches[] = [
            'MILE_STONE' => $milestone,
            'ANONYMOUS_NAME' => $name,
            'WHATSAPP_URL_CONTACT' => $whatsapp,
            'WHATSAPP_URL_CONGRATS_PERSONAL' => $congratsLink,
            'SHOW' => $showRaw,
        ];
    }
}

usort($matches, function ($a, $b) {
    return strcasecmp($a['ANONYMOUS_NAME'], $b['ANONYMOUS_NAME']);
});

return $matches;