<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Cron entry point for scheduled newsletter sends.
 *
 * Set up a cron job to run this daily. It checks whether today matches
 * the configured send day and frequency, and sends if so.
 *
 * Example crontab entry (runs daily at 8am):
 *   0 8 * * * php /path/to/campfire/cron.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/db.php';

$frequency = getSetting('send_frequency', 'biweekly');
$sendDay = getSetting('send_day', 'Monday');

$today = new DateTimeImmutable('today');
$todayDay = $today->format('l');

if ($todayDay !== $sendDay) {
    echo "Not send day ({$sendDay}). Today is {$todayDay}.\n";
    exit(0);
}

$db = getDb();
$lastSend = $db->query('SELECT sent_at FROM email_log ORDER BY sent_at DESC LIMIT 1')->fetch();

if ($lastSend) {
    $lastDate = new DateTimeImmutable($lastSend['sent_at']);
    $daysSince = (int) $today->diff($lastDate)->days;

    $override = (int) getSetting('min_days_between_sends', '0');
    $minDays = $override > 0 ? $override : match ($frequency) {
        'weekly' => 5,
        'biweekly' => 12,
        'monthly' => 25,
        default => 12,
    };

    if ($daysSince < $minDays) {
        echo "Too soon since last send ({$daysSince} days ago, minimum {$minDays}).\n";
        exit(0);
    }
}

require_once __DIR__ . '/src/auth.php';
require_once __DIR__ . '/src/newsletter.php';

$result = sendNewsletter();

if ($result['success']) {
    echo "Newsletter sent to {$result['count']} recipients.\n";
} else {
    echo "Send failed: {$result['error']}\n";
    exit(1);
}
