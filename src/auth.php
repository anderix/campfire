<?php

interface AuthInterface {
    public function login(string $email, string $password): bool;
    public function logout(): void;
    public function getCurrentUser(): ?array;
    public function isLoggedIn(): bool;
    public function requireAuth(): void;
    public function createUser(string $email, string $password, string $displayName): int;
    public function changePassword(int $userId, string $newPassword): void;
}

class SessionAuth implements AuthInterface {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $email, string $password): bool {
        $db = getDb();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_display_name'] = $user['display_name'];
        return true;
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function getCurrentUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'display_name' => $_SESSION['user_display_name'],
        ];
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public function requireAuth(): void {
        if (!$this->isLoggedIn()) {
            header('Location: ?page=login');
            exit;
        }
    }

    public function createUser(string $email, string $password, string $displayName): int {
        $db = getDb();
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, display_name) VALUES (?, ?, ?)');
        $stmt->execute([
            strtolower(trim($email)),
            password_hash($password, PASSWORD_DEFAULT),
            trim($displayName),
        ]);
        return (int) $db->lastInsertId();
    }

    public function changePassword(int $userId, string $newPassword): void {
        $db = getDb();
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }
}
