<?php
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_settings') {
        setSetting('troop_name', trim($_POST['troop_name'] ?? ''));
        setSetting('calendar_url', trim($_POST['calendar_url'] ?? ''));
        setSetting('event_lookahead_days', (string) max(1, (int) ($_POST['event_lookahead_days'] ?? 14)));
        setSetting('timezone', $_POST['timezone'] ?? 'America/Chicago');
        setSetting('send_frequency', $_POST['send_frequency'] ?? 'biweekly');
        setSetting('send_day', $_POST['send_day'] ?? 'Monday');
        setSetting('email_from_name', trim($_POST['email_from_name'] ?? ''));
        setSetting('email_from_address', trim($_POST['email_from_address'] ?? ''));
        setSetting('brevo_api_key', trim($_POST['brevo_api_key'] ?? ''));
        setSetting('app_url', trim($_POST['app_url'] ?? ''));
        flash('success', 'Settings saved.');
        redirect('settings');
    }

    if ($action === 'add_user') {
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email !== '' && $displayName !== '' && strlen($password) >= 8) {
            $existing = $db->prepare('SELECT id FROM users WHERE email = ?');
            $existing->execute([strtolower($email)]);
            if ($existing->fetch()) {
                flash('error', 'A user with that email already exists.');
            } else {
                $auth->createUser($email, $password, $displayName);
                flash('success', "User \"{$displayName}\" created.");
            }
        } else {
            flash('error', 'All fields are required and password must be at least 8 characters.');
        }
        redirect('settings');
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $currentUser = $auth->getCurrentUser();
        if ($userId === $currentUser['id']) {
            flash('error', 'You cannot delete your own account.');
        } else {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            flash('success', 'User deleted.');
        }
        redirect('settings');
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $currentUser = $auth->getCurrentUser();
        if (!$auth->login($currentUser['email'], $currentPassword)) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($newPassword) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            $auth->changePassword($currentUser['id'], $newPassword);
            flash('success', 'Password changed.');
        }
        redirect('settings');
    }
}

$users = $db->query('SELECT id, email, display_name, created_at FROM users ORDER BY display_name')->fetchAll();
?>

<h2>Settings</h2>

<!-- Troop & Calendar -->
<div class="card">
    <h3>Troop &amp; Calendar</h3>
    <form method="post">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="save_settings">
        <div class="form-group">
            <label for="troop_name">Troop Name</label>
            <input type="text" id="troop_name" name="troop_name" value="<?= htmlspecialchars(getSetting('troop_name', '')) ?>" placeholder="Troop 99">
        </div>
        <div class="form-group">
            <label for="calendar_url">Scoutbook Plus Calendar URL</label>
            <input type="url" id="calendar_url" name="calendar_url" value="<?= htmlspecialchars(getSetting('calendar_url', '')) ?>" placeholder="https://api.scouting.org/advancements/events/calendar/...">
        </div>
        <div class="form-group">
            <label for="event_lookahead_days">Show events this many days ahead</label>
            <input type="number" id="event_lookahead_days" name="event_lookahead_days" value="<?= htmlspecialchars(getSetting('event_lookahead_days', '14')) ?>" min="1" max="90" style="width: 100px;">
        </div>
        <div class="form-group">
            <label for="timezone">Timezone</label>
            <select id="timezone" name="timezone">
                <?php
                $currentTz = getSetting('timezone', 'America/Chicago');
                $zones = ['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'America/Anchorage', 'Pacific/Honolulu'];
                $labels = ['Eastern', 'Central', 'Mountain', 'Pacific', 'Alaska', 'Hawaii'];
                foreach ($zones as $i => $zone): ?>
                    <option value="<?= $zone ?>" <?= $currentTz === $zone ? 'selected' : '' ?>><?= $labels[$i] ?> (<?= $zone ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <h3>Email Sending</h3>
        <div class="form-group">
            <label for="email_from_name">From Name</label>
            <input type="text" id="email_from_name" name="email_from_name" value="<?= htmlspecialchars(getSetting('email_from_name', '')) ?>" placeholder="Troop 99">
        </div>
        <div class="form-group">
            <label for="email_from_address">From Email Address</label>
            <input type="email" id="email_from_address" name="email_from_address" value="<?= htmlspecialchars(getSetting('email_from_address', '')) ?>" placeholder="troop99@example.com">
        </div>
        <div class="form-group">
            <label for="brevo_api_key">Brevo API Key</label>
            <input type="text" id="brevo_api_key" name="brevo_api_key" value="<?= htmlspecialchars(getSetting('brevo_api_key', '')) ?>" placeholder="xkeysib-...">
        </div>
        <div class="form-group">
            <label for="app_url">App URL (used for unsubscribe links in emails)</label>
            <input type="url" id="app_url" name="app_url" value="<?= htmlspecialchars(getSetting('app_url', '')) ?>" placeholder="https://yoursite.com/campfire/">
        </div>

        <h3>Schedule</h3>
        <div class="form-group">
            <label for="send_frequency">Frequency</label>
            <select id="send_frequency" name="send_frequency">
                <?php $freq = getSetting('send_frequency', 'biweekly'); ?>
                <option value="weekly" <?= $freq === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="biweekly" <?= $freq === 'biweekly' ? 'selected' : '' ?>>Every two weeks</option>
                <option value="monthly" <?= $freq === 'monthly' ? 'selected' : '' ?>>Every four weeks</option>
            </select>
        </div>
        <div class="form-group">
            <label for="send_day">Send Day</label>
            <select id="send_day" name="send_day">
                <?php $day = getSetting('send_day', 'Monday'); ?>
                <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                    <option value="<?= $d ?>" <?= $day === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<!-- Users -->
<div class="card">
    <h3>Users</h3>
    <?php if (!empty($users)): ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['display_name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <?php if ($user['id'] !== $auth->getCurrentUser()['id']): ?>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this user?')">
            <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <?php endif; ?>

    <h3>Add User</h3>
    <form method="post">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="add_user">
        <div class="inline-form" style="margin-bottom: 0.5rem;">
            <div class="form-group">
                <label for="new_display_name">Name</label>
                <input type="text" id="new_display_name" name="display_name" required>
            </div>
            <div class="form-group">
                <label for="new_email">Email</label>
                <input type="email" id="new_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="new_password">Password</label>
                <input type="password" id="new_password" name="password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </div>
    </form>
</div>

<!-- Change own password -->
<div class="card">
    <h3>Change Your Password</h3>
    <form method="post" class="inline-form">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="change_password">New Password</label>
            <input type="password" id="change_password" name="new_password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-secondary">Change Password</button>
    </form>
</div>
