<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdmin(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']) && strtoupper(trim($_SESSION['role'])) === 'ADMIN';
}

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        header("Location: ../login/dashboard.php");
        exit();
    }
}

function normalizeRole($role): string
{
    return (isset($role) && strtoupper(trim((string) $role)) === 'ADMIN') ? 'ADMIN' : 'USER';
}
