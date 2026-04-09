<?php

require_once APP_ROOT . '/src/calendar.php';
require_once APP_ROOT . '/src/mailer.php';

function sendNewsletter(): array {
    $db = getDb();

    $calendarUrl = getSetting('calendar_url', '');
    $lookahead = (int) getSetting('event_lookahead_days', '14');
    $troopName = getSetting('troop_name', 'Campfire');
    $fromName = getSetting('email_from_name', $troopName);
    $fromAddress = getSetting('email_from_address', '');
    $apiKey = getSetting('brevo_api_key', '');

    if ($fromAddress === '' || $apiKey === '') {
        return ['success' => false, 'error' => 'Email sending is not configured. Set the from address and Brevo API key in Settings.', 'count' => 0];
    }

    $events = [];
    if ($calendarUrl !== '') {
        $events = fetchCalendarEvents($calendarUrl, $lookahead);
    }

    $members = $db->query('
        SELECT m.id, m.display_name, m.email, m.family_id, m.unsubscribe_token
        FROM members m
        WHERE m.active = 1
        ORDER BY m.family_id
    ')->fetchAll();

    if (empty($members)) {
        return ['success' => false, 'error' => 'No active members to send to.', 'count' => 0];
    }

    $familyAccounts = [];
    $allAccounts = $db->query('SELECT * FROM scout_accounts ORDER BY family_id, label')->fetchAll();
    foreach ($allAccounts as $account) {
        $familyAccounts[$account['family_id']][] = $account;
    }

    $sentCount = 0;
    $errors = [];
    $baseUrl = getBaseUrl();

    foreach ($members as $member) {
        $accounts = $familyAccounts[$member['family_id']] ?? [];
        $unsubscribeUrl = $baseUrl . '?page=unsubscribe&token=' . $member['unsubscribe_token'];

        $html = renderEmail($troopName, $member['display_name'], $events, $accounts, $unsubscribeUrl);

        $subject = $troopName . ' Events';
        $result = sendBrevoEmail($apiKey, $fromName, $fromAddress, $member['display_name'], $member['email'], $subject, $html);

        if ($result['success']) {
            $sentCount++;
        } else {
            $errors[] = $member['email'] . ': ' . $result['error'];
        }
    }

    $status = empty($errors) ? 'success' : 'partial';
    $details = empty($errors) ? null : implode("\n", $errors);

    $db->prepare('INSERT INTO email_log (recipient_count, status, details) VALUES (?, ?, ?)')
        ->execute([$sentCount, $status, $details]);

    if ($sentCount === 0) {
        return ['success' => false, 'error' => 'All sends failed. ' . ($errors[0] ?? ''), 'count' => 0];
    }

    return ['success' => true, 'count' => $sentCount, 'errors' => $errors];
}

function renderEmail(string $troopName, string $memberName, array $events, array $accounts, string $unsubscribeUrl): string {
    ob_start();
    include APP_ROOT . '/templates/email.php';
    return ob_get_clean();
}

function getBaseUrl(): string {
    $configured = getSetting('app_url', '');
    if ($configured !== '') {
        return rtrim($configured, '/') . '/';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    return rtrim($scheme . '://' . $host . $path, '/') . '/';
}
