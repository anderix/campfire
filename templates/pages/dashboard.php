<?php
require_once APP_ROOT . '/src/calendar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_now') {
        require_once APP_ROOT . '/src/newsletter.php';
        $result = sendNewsletter();
        if ($result['success']) {
            flash('success', "Newsletter sent to {$result['count']} recipients.");
        } else {
            flash('error', "Send failed: {$result['error']}");
        }
        redirect('dashboard');
    }
}

$db = getDb();
$familyCount = $db->query('SELECT COUNT(*) FROM families')->fetchColumn();
$memberCount = $db->query('SELECT COUNT(*) FROM members WHERE active = 1')->fetchColumn();
$accountCount = $db->query('SELECT COUNT(*) FROM scout_accounts')->fetchColumn();

$calendarUrl = getSetting('calendar_url', '');
$lookahead = (int) getSetting('event_lookahead_days', '14');
$events = [];
if ($calendarUrl !== '') {
    $events = fetchCalendarEvents($calendarUrl, $lookahead);
}

$lastSend = $db->query('SELECT * FROM email_log ORDER BY sent_at DESC LIMIT 1')->fetch();

$frequency = getSetting('send_frequency', 'biweekly');
$sendDay = getSetting('send_day', 'Monday');
?>

<h2>Dashboard</h2>

<div class="stats">
    <div class="stat-card">
        <div class="stat-value"><?= $familyCount ?></div>
        <div class="stat-label">Families</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $memberCount ?></div>
        <div class="stat-label">Active Members</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $accountCount ?></div>
        <div class="stat-label">Scout Accounts</div>
    </div>
</div>

<div class="card">
    <h3>Newsletter Status</h3>
    <p>
        Schedule: <strong><?= htmlspecialchars(ucfirst($frequency)) ?></strong> on <strong><?= htmlspecialchars($sendDay) ?>s</strong>
    </p>
    <?php if ($lastSend): ?>
        <p>Last sent: <strong><?= htmlspecialchars($lastSend['sent_at']) ?></strong> to <?= $lastSend['recipient_count'] ?> recipients
            <?php if ($lastSend['status'] !== 'success'): ?>
                <span class="badge badge-inactive"><?= htmlspecialchars($lastSend['status']) ?></span>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p>No newsletters sent yet.</p>
    <?php endif; ?>
    <br>
    <form method="post" onsubmit="return confirm('Send the newsletter to all active members now?')">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="send_now">
        <button type="submit" class="btn btn-primary">Send Now</button>
    </form>
</div>

<div class="card">
    <h3>Upcoming Events (next <?= $lookahead ?> days)</h3>
    <?php if (empty($events)): ?>
        <p>
            <?php if ($calendarUrl === ''): ?>
                No calendar URL configured. <a href="?page=settings">Set one up in Settings.</a>
            <?php else: ?>
                No upcoming events found.
            <?php endif; ?>
        </p>
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
                <?php foreach ($events as $event): ?>
                <tr>
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
</div>
