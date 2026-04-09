<?php
$db = getDb();
$userCount = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount > 0) {
    redirect('login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $troopName = trim($_POST['troop_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');

    $errors = [];
    if ($troopName === '') $errors[] = 'Troop name is required.';
    if ($email === '') $errors[] = 'Email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($displayName === '') $errors[] = 'Display name is required.';

    if (empty($errors)) {
        setSetting('troop_name', $troopName);
        $auth->createUser($email, $password, $displayName);
        $auth->login($email, $password);
        flash('success', 'Welcome to Campfire! Start by configuring your settings.');
        redirect('settings');
    }
}
?>
<div class="login-container">
    <div class="login-box">
        <h1>Campfire Setup</h1>
        <p class="subtitle">Create your first admin account to get started.</p>
        <?php if (!empty($errors)): ?>
            <div class="flash flash-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
        <?php endif; ?>
        <form method="post" action="?page=install">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="troop_name">Troop Name</label>
                <input type="text" id="troop_name" name="troop_name" placeholder="Troop 99" value="<?= htmlspecialchars($_POST['troop_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="display_name">Your Name</label>
                <input type="text" id="display_name" name="display_name" placeholder="Jane Smith" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
    </div>
</div>
