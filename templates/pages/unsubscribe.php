<?php
$token = $_GET['token'] ?? '';
$db = getDb();

if ($token !== '') {
    $stmt = $db->prepare('SELECT m.*, f.name AS family_name FROM members m JOIN families f ON f.id = m.family_id WHERE m.unsubscribe_token = ?');
    $stmt->execute([$token]);
    $member = $stmt->fetch();

    if ($member) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db->prepare('UPDATE members SET active = 0 WHERE id = ?')->execute([$member['id']]);
            $unsubscribed = true;
        }
    } else {
        $invalidToken = true;
    }
}
?>
<div class="login-container">
    <div class="login-box">
        <h1><?= htmlspecialchars(getSetting('troop_name', 'Campfire')) ?></h1>
        <?php if (empty($token) || !empty($invalidToken)): ?>
            <p>Invalid unsubscribe link.</p>
        <?php elseif (!empty($unsubscribed)): ?>
            <p>You have been unsubscribed. You will no longer receive newsletter emails.</p>
        <?php else: ?>
            <p>Unsubscribe <strong><?= htmlspecialchars($member['display_name']) ?></strong> (<?= htmlspecialchars($member['email']) ?>) from the newsletter?</p>
            <br>
            <form method="post">
            <?= csrfField() ?>
                <button type="submit" class="btn btn-primary">Unsubscribe</button>
            </form>
        <?php endif; ?>
    </div>
</div>
