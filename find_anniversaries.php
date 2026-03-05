<?php
// find_anniversaries_debug.php  ← rename or use as test file

$today = new DateTime('today');

if (!isset($rows)) {
    $rows = include __DIR__ . '/fetch_csv.php';
}

// ── Debug: show what we actually loaded ───────────────────────────────
echo "<pre>DEBUG - Raw rows count: " . count($rows) . "\n";
if (!empty($rows)) {
    echo "First row (should be header): " . implode(" | ", $rows[0] ?? []) . "\n\n";
    echo "Second row (first data): " . implode(" | ", $rows[1] ?? []) . "\n";
}
echo "</pre>";

// Process
$matches = [];

if (!empty($rows) && is_array($rows)) {
    $header = array_shift($rows); // remove header

    foreach ($rows as $index => $row) {
        if (!is_array($row) || count($row) < 5) {
            echo "<pre>Row $index skipped - too few columns: " . count($row) . "</pre>";
            continue;
        }

        $row = array_map('trim', $row);

        $milestone    = $row[0] ?? '';
        $name         = $row[1] ?? '';
        $whatsapp     = $row[2] ?? '';
        $congratsLink = $row[3] ?? '';
        $showRaw      = $row[4] ?? '';
        $show         = strtolower(trim($showRaw));

        // More permissive check
        if (!in_array($show, ['yes', 'y', 'true', '1'], true)) {
            // echo "<pre>Row $index skipped - SHOW = '$showRaw'</pre>"; // uncomment to see skipped
            continue;
        }

        if (empty($milestone) || empty($name)) {
            continue;
        }

        $matches[] = [
            'milestone'         => $milestone,
            'anonymous_name'    => $name,
            'whatsapp_url'      => $whatsapp,
            'whatsapp_congrats' => $congratsLink,
            'show'              => $showRaw,
        ];
    }
}

usort($matches, function($a, $b) {
    return strcasecmp($a['anonymous_name'], $b['anonymous_name']);
});

// ── Browser output ───────────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && !empty($_SERVER['REQUEST_URI'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sobriety Anniversaries - Debug View</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>

    <div class="container">
        <h1>Sobriety Anniversaries (All SHOW = Yes) - <?= $today->format('d M Y') ?></h1>

        <?php if (empty($matches)): ?>
            <p class="no-data">No matching entries found.</p>
            <p style="color: #c00;">Check debug output above — probably wrong columns, fetch failed, or no 'Yes' rows loaded.</p>
        <?php else: ?>
            <p class="count">Found <?= count($matches) ?> entries with SHOW = Yes</p>

            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Milestone</th>
                        <th>Name</th>
                        <th>WhatsApp</th>
                        <th>Congrats</th>
                        <th>SHOW (raw)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($matches as $person): ?>
                        <tr>
                            <td><?= htmlspecialchars($person['milestone']) ?></td>
                            <td><?= htmlspecialchars($person['anonymous_name']) ?></td>
                            <td>
                                <?php if ($person['whatsapp_url']): ?>
                                    <a href="<?= htmlspecialchars($person['whatsapp_url']) ?>" target="_blank">Chat</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($person['whatsapp_congrats']): ?>
                                    <a href="<?= htmlspecialchars($person['whatsapp_congrats']) ?>" target="_blank">Congrats</a>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($person['show']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    </body>
    </html>
    <?php
    exit;
}

return $matches;