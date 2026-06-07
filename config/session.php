<?php
/**
 * config/session.php
 *
 * Manajemen sesi dan kontrol akses berbasis peran (role-based access control).
 * File ini harus di-include di awal setiap halaman yang memerlukan autentikasi.
 *
 * Fungsi yang tersedia:
 *   require_login()   — pastikan user sudah login
 *   require_role()    — pastikan user memiliki role tertentu
 *   is_admin()        — cek apakah user adalah admin
 *   user_nama()       — dapatkan nama user yang sedang login
 */

// Mulai sesi hanya jika belum berjalan (mencegah error "session already started")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memastikan user sudah login sebelum mengakses halaman.
 * Jika belum login (tidak ada $_SESSION['username']), redirect ke halaman login.
 *
 * @param string $redirectTo  URL tujuan redirect jika belum login (default: index.php)
 */
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
 * Fungsi ini secara otomatis memanggil require_login() terlebih dahulu
 * untuk memastikan user sudah login, sebelum memeriksa role-nya.
 *
 * @param string|array $roles    Role yang diizinkan, e.g. 'admin' atau ['admin', 'kasir']
 * @param string       $redirect Halaman tujuan redirect jika tidak berhak (default: dashboard.php)
 */
function require_role($roles, string $redirect = 'dashboard.php'): void
{
    // Pastikan user sudah login terlebih dahulu
    require_login('index.php');

    // Normalkan $roles menjadi array agar bisa menangani string maupun array
    $allowedRoles = (array) $roles;

    // Ambil role user saat ini dari sesi
    $currentRole  = $_SESSION['role'] ?? '';

    // Jika role tidak ada dalam daftar yang diizinkan, redirect dengan pesan akses ditolak
    if (!in_array($currentRole, $allowedRoles, true)) {
        header('Location: ' . $redirect . '?akses=ditolak');
        exit;
    }
}

/**
 * Cek apakah user saat ini adalah admin.
 *
 * @return bool  true jika role user adalah 'admin', false jika tidak
 */
function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Dapatkan nama tampilan user yang sedang login.
 * Mengutamakan nama lengkap (field 'nama'), fallback ke username jika tidak ada.
 *
 * @return string  Nama user atau string kosong jika tidak ada sesi
 */
function user_nama(): string
{
    return $_SESSION['nama'] ?? $_SESSION['username'] ?? '';
}
