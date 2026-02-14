<?php
include 'fetch_csv.php';

// fetch_csv.php should return $rows
$rows = $rows ?? [];

// Set timezone and today's date
date_default_timezone_set('Asia/Kolkata');
$today = new DateTime();

// Helper: format sober duration nicely
function formatSoberDuration(?DateTime $startDate, DateTime $today): string {
    if (!$startDate) {
        return '';
    }

    $interval = $startDate->diff($today);

    $years  = $interval->y;
    $months = $interval->m;
    $days   = $interval->d;

    // Build a human-readable string (largest units first)
    $parts = [];

    if ($years > 0) {
        $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
    }
    if ($months > 0) {
        $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
    }
    if ($years === 0 && $months === 0 && $days > 0) {
        // Only show days if no years/months
        $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
    }

    return empty($parts) ? '0 days' : implode(' ', $parts);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Viewer</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<h1 style="text-align:center;">Google Sheet CSV Viewer</h1>

<table>
    <?php foreach ($rows as $rowIndex => $row): ?>
        <tr>
        <?php foreach ($row as $colIndex => $cell): ?>
            <?php
            // Skip last column ("Column 9")
            // Adjust index if your CSV has different column count
            // Here we assume it is the 10th column (index 9)
            if ($colIndex === 9) {
                continue;
            }

            // For header row, use <th>
            $tag = ($rowIndex === 0) ? 'th' : 'td';
            ?>
            <<?= $tag ?>><?= htmlspecialchars($cell) ?></<?= $tag ?>>

            <?php
            // After "Sobriety Start Date" column (index 4), insert "Sober Duration"
            if ($colIndex === 4) {
                if ($rowIndex === 0) {
                    // Header row: add the column title
                    echo '<th>Sober Duration</th>';
                } else {
                    // Data row: calculate duration
                    $sobrietyRaw = trim($row[4]);

                    $sobrietyDate = null;

                    // Try to parse common formats (e.g. 2/14/2025 or 2025-02-14)
                    if ($sobrietyRaw !== '') {
                        // Try MM/DD/YYYY
                        $sobrietyDate = DateTime::createFromFormat('m/d/Y', $sobrietyRaw)
                                ?: DateTime::createFromFormat('n/j/Y', $sobrietyRaw)
                                        ?: DateTime::createFromFormat('m/d/Y H:i', $sobrietyRaw)
                                                ?: DateTime::createFromFormat('n/j/Y H:i', $sobrietyRaw);

                        // Fallback to generic parser if above fails
                        if (!$sobrietyDate) {
                            try {
                                $sobrietyDate = new DateTime($sobrietyRaw);
                            } catch (Exception $e) {
                                $sobrietyDate = null;
                            }
                        }
                    }

                    $durationText = formatSoberDuration($sobrietyDate, $today);
                    echo '<td>' . htmlspecialchars($durationText) . '</td>';
                }
            }
            ?>
        <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>