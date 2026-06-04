<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(string $redirectTo = 'index.php'): void
{
    if (empty($_SESSION['username'])) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * Pastikan user memiliki role tertentu.
 * Jika tidak sesuai, redirect ke halaman yang ditentukan.
 *
 * @param string|array $roles  Role yang diizinkan, e.g. 'admin' atau ['admin']
 * @param string       $redirect Halaman tujuan redirect jika tidak berhak
 */
function require_role($roles, string $redirect = 'dashboard.php'): void
{
    require_login('index.php');
    $allowedRoles = (array) $roles;
    $currentRole  = $_SESSION['role'] ?? '';
    if (!in_array($currentRole, $allowedRoles, true)) {
        header('Location: ' . $redirect . '?akses=ditolak');
        exit;
    }
}

/**
 * Cek apakah user saat ini adalah admin.
 */
function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Dapatkan nama user yang sedang login.
 */
function user_nama(): string
{
    return $_SESSION['nama'] ?? $_SESSION['username'] ?? '';
}
