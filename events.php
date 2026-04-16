<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/calendar.php';

$troopName = getSetting('troop_name', 'Campfire');
$calendarUrl = getSetting('calendar_url', '');
$tz = getDisplayTimezone();
$today = new DateTimeImmutable('today', $tz);

$upcoming = [];
$past = [];

if ($calendarUrl !== '') {
    $content = @file_get_contents($calendarUrl);
    if ($content !== false) {
        $allEvents = parseIcalAll($content);

        $upcomingCutoff = $today->modify('+3 months');
        $pastStart = $today->modify('-3 months');

        foreach ($allEvents as $event) {
            if ($event['start'] >= $today && $event['start'] < $upcomingCutoff) {
                $months = monthsBetween($today, $event['start']) + 1;
                $event['month_bucket'] = min($months, 3);
                $upcoming[] = $event;
            } elseif ($event['start'] < $today && $event['start'] >= $pastStart) {
                $months = monthsBetween($event['start'], $today) + 1;
                $event['month_bucket'] = min($months, 3);
                $past[] = $event;
            }
        }

        // $past is already oldest-first from parseIcalAll sort
    }
}

function monthsBetween(DateTimeImmutable $earlier, DateTimeImmutable $later): int {
    $diff = $earlier->diff($later);
    return ($diff->y * 12) + $diff->m;
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - <?= htmlspecialchars($troopName) ?></title>
    <link rel="stylesheet" href="public/css/axe-brand.css">
    <link rel="stylesheet" href="public/css/axe.css">
    <script src="public/js/theme.js"></script>
    <style>
        .range-buttons { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }
        .range-buttons button {
            padding: 0.3rem 0.85rem;
            font-size: 0.8rem;
            background: var(--color-surface);
            color: var(--color-text-muted);
            border: 1px solid var(--color-border);
        }
        .range-buttons button.active {
            background: var(--color-accent);
            color: #fff;
            border-color: var(--color-accent);
        }
    </style>
</head>
<body>
<main>
    <nav>
        <ul>
            <li><a href="events.php"><?= htmlspecialchars($troopName) ?></a></li>
            <li class="nav-right"><button class="theme-toggle" aria-label="Toggle theme">&#9788;</button></li>
        </ul>
    </nav>

    <details id="past">
        <summary>Past Events</summary>
        <div class="range-buttons">
            <button data-months="1" class="active">1 month</button>
            <button data-months="2">2 months</button>
            <button data-months="3">3 months</button>
        </div>
        <?php if (empty($past)): ?>
            <p><small>No past events.</small></p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($past as $event): ?>
                <tr data-month="<?= $event['month_bucket'] ?>">
                    <td><?= htmlspecialchars(formatEventDateRange($event)) ?></td>
                    <td><?= htmlspecialchars(formatEventTimeRange($event)) ?></td>
                    <td>
                        <?php if (!empty($event['url'])): ?>
                            <a href="<?= htmlspecialchars($event['url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($event['summary']) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($event['summary']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($event['location'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </details>

    <details open id="upcoming">
        <summary>Upcoming Events</summary>
        <div class="range-buttons">
            <button data-months="1" class="active">1 month</button>
            <button data-months="2">2 months</button>
            <button data-months="3">3 months</button>
        </div>
        <?php if (empty($upcoming)): ?>
            <p><small>No upcoming events.</small></p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming as $event): ?>
                <tr data-month="<?= $event['month_bucket'] ?>">
                    <td><?= htmlspecialchars(formatEventDateRange($event)) ?></td>
                    <td><?= htmlspecialchars(formatEventTimeRange($event)) ?></td>
                    <td>
                        <?php if (!empty($event['url'])): ?>
                            <a href="<?= htmlspecialchars($event['url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($event['summary']) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($event['summary']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($event['location'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </details>

    <script>
    function filterRows(section, months) {
        section.querySelectorAll('tbody tr').forEach(function(row) {
            row.style.display = parseInt(row.dataset.month) <= months ? '' : 'none';
        });
    }

    document.querySelectorAll('.range-buttons').forEach(function(group) {
        group.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON') return;
            var months = parseInt(e.target.dataset.months);
            group.querySelectorAll('button').forEach(function(b) { b.classList.remove('active'); });
            e.target.classList.add('active');
            filterRows(group.closest('details'), months);
        });
        filterRows(group.closest('details'), 1);
    });
    </script>
</main>
</body>
</html>
