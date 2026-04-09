<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/auth.php';

$auth = new SessionAuth();

$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect(string $page): void {
    header('Location: ?page=' . $page);
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

$currentPage = $_GET['page'] ?? 'dashboard';

if ($currentPage === 'logout') {
    $auth->logout();
    redirect('login');
}

$db = getDb();
$userCount = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount == 0 && $currentPage !== 'install') {
    $currentPage = 'install';
}

$publicPages = ['login', 'install', 'unsubscribe'];
if (!in_array($currentPage, $publicPages)) {
    $auth->requireAuth();
}

$pages = [
    'login'         => 'templates/pages/login.php',
    'install'       => 'templates/pages/install.php',
    'dashboard'     => 'templates/pages/dashboard.php',
    'families'      => 'templates/pages/families.php',
    'family-detail' => 'templates/pages/family-detail.php',
    'settings'      => 'templates/pages/settings.php',
    'unsubscribe'   => 'templates/pages/unsubscribe.php',
    'reset'     => 'templates/pages/reset.php',
];

if (!isset($pages[$currentPage])) {
    $currentPage = 'dashboard';
}

$pageTemplate = $pages[$currentPage];

$pageTitles = [
    'login'         => 'Log In',
    'install'       => 'Setup',
    'dashboard'     => 'Dashboard',
    'families'      => 'Families',
    'family-detail' => 'Family',
    'settings'      => 'Settings',
    'unsubscribe'   => 'Unsubscribe',
    'reset'     => 'Reset',
];
$pageTitle = $pageTitles[$currentPage] ?? 'Campfire';

include __DIR__ . '/templates/layout.php';
