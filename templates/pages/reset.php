<?php
$db = getDb();

$familyCount = $db->query('SELECT COUNT(*) FROM families')->fetchColumn();
$memberCount = $db->query('SELECT COUNT(*) FROM members')->fetchColumn();
$accountCount = $db->query('SELECT COUNT(*) FROM scout_accounts')->fetchColumn();
$userCount = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$logCount = $db->query('SELECT COUNT(*) FROM email_log')->fetchColumn();
$settingsCount = $db->query('SELECT COUNT(*) FROM settings')->fetchColumn();

$confirmed = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmation = trim($_POST['confirmation'] ?? '');
    if ($confirmation !== 'RESET') {
        $error = 'You must type RESET to confirm.';
    } else {
        // Remove the database file
        $dbPath = DB_PATH;
        $walPath = $dbPath . '-wal';
        $shmPath = $dbPath . '-shm';

        // Close the connection before deleting
        unset($db);

        $deleted = [];
        foreach ([$dbPath, $walPath, $shmPath] as $file) {
            if (file_exists($file)) {
                if (@unlink($file)) {
                    $deleted[] = basename($file);
                }
            }
        }

        // Destroy session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();

        $confirmed = true;
    }
}
?>

<?php if ($confirmed): ?>
<div class="login-container">
    <div class="login-box">
        <h1>Reset Complete</h1>
        <p>Campfire has been reset to its initial state.</p>
        <?php if (!empty($deleted)): ?>
            <p>Deleted: <?= htmlspecialchars(implode(', ', $deleted)) ?></p>
        <?php endif; ?>
        <p>If you set up a cron job for scheduled sends, remove it manually with <code>crontab -e</code>.</p>
        <br>
        <a href="?" class="btn btn-primary">Start Fresh</a>
    </div>
</div>
<?php else: ?>

<h2>Reset Campfire</h2>

<div class="card" style="border-color: var(--color-error);">
    <h3>This will permanently delete everything</h3>
    <p>Resetting will remove all data and return the application to its initial state. The following will be destroyed:</p>
    <br>
    <table>
        <tbody>
            <tr><td>Families</td><td><strong><?= $familyCount ?></strong></td></tr>
            <tr><td>Members (email recipients)</td><td><strong><?= $memberCount ?></strong></td></tr>
            <tr><td>Scout accounts</td><td><strong><?= $accountCount ?></strong></td></tr>
            <tr><td>Admin users</td><td><strong><?= $userCount ?></strong></td></tr>
            <tr><td>Email log entries</td><td><strong><?= $logCount ?></strong></td></tr>
            <tr><td>Settings (calendar URL, API keys, schedule)</td><td><strong><?= $settingsCount ?> values</strong></td></tr>
        </tbody>
    </table>
    <br>
    <p>If you set up a cron job, you will need to remove it manually with <code>crontab -e</code>.</p>
    <p>Your application files will remain on the server. After resetting, visiting the site will show the initial setup page again.</p>
    <br>
    <?php if ($error): ?>
        <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
            <?= csrfField() ?>
        <div class="form-group">
            <label for="confirmation">Type <strong>RESET</strong> to confirm</label>
            <input type="text" id="confirmation" name="confirmation" autocomplete="off" style="max-width: 250px;">
        </div>
        <button type="submit" class="btn btn-danger">Reset Campfire</button>
    </form>
</div>

<?php endif; ?>
