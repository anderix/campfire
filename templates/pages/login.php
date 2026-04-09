<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        redirect('dashboard');
    } else {
        $loginError = 'Invalid email or password.';
    }
}
?>
<div class="login-container">
    <div class="login-box">
        <h1><?= htmlspecialchars(getSetting('troop_name', 'Campfire')) ?></h1>
        <p class="subtitle">Sign in to manage your troop newsletter.</p>
        <?php if (!empty($loginError)): ?>
            <div class="flash flash-error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="post" action="?page=login">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
    </div>
</div>
