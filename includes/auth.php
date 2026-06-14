<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_role(): string
{
    return current_user()['role'] ?? 'staff';
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(array $roles): bool
{
    return in_array(current_user_role(), $roles, true);
}

function can_manage_users(): bool
{
    return has_role(['admin', 'leader']);
}

function can_delete_tickets(): bool
{
    return has_role(['admin', 'leader']);
}

function can_change_ticket_status(string $fromStatus, string $toStatus): bool
{
    if (has_role(['admin', 'leader'])) {
        return true;
    }

    $allowedTransitions = [
        'in_progress' => ['waiting', 'closed'],
        'waiting' => ['in_progress', 'closed'],
    ];

    return in_array($toStatus, $allowedTransitions[$fromStatus] ?? [], true);
}

function login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    return true;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_roles(array $roles): void
{
    require_login();

    if (!has_role($roles)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
