<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAuthenticated(): bool {
    return isset($_SESSION['user']);
}

function getUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        header('Location: /login');
        exit;
    }
}

function loginUser(array $user): void {
    $_SESSION['user'] = $user;
}

function logoutUser(): void {
    unset($_SESSION['user']);
    session_destroy();
}
