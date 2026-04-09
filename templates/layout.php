<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Campfire') ?> - <?= htmlspecialchars(getSetting('troop_name', 'Campfire')) ?></title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<?php if ($auth->isLoggedIn()): ?>
<nav class="sidebar">
    <div class="sidebar-header">
        <h1><?= htmlspecialchars(getSetting('troop_name', 'Campfire')) ?></h1>
    </div>
    <ul class="nav-links">
        <li><a href="?page=dashboard" class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="?page=families" class="<?= ($currentPage === 'families') ? 'active' : '' ?>">Families</a></li>
        <li><a href="?page=settings" class="<?= ($currentPage === 'settings') ? 'active' : '' ?>">Settings</a></li>
        <li><a href="?page=reset" class="<?= ($currentPage === 'reset') ? 'active' : '' ?>" style="color: rgba(255,100,100,0.7);">Reset</a></li>
    </ul>
    <div class="sidebar-footer">
        <span class="user-name"><?= htmlspecialchars($auth->getCurrentUser()['display_name']) ?></span>
        <a href="?page=logout" class="logout-link">Log out</a>
    </div>
</nav>
<main class="content">
    <?php if (!empty($flash)): ?>
        <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?php include $pageTemplate; ?>
</main>
<?php else: ?>
    <?php include $pageTemplate; ?>
<?php endif; ?>
</body>
</html>
