<?php
/**
 * process/logout.php
 *
 * Handler untuk proses logout user.
 * Menghancurkan sesi secara menyeluruh (menghapus data sesi, cookie, dan session storage)
 * lalu mengarahkan kembali ke halaman login.
 *
 * Dipanggil via form POST dari tombol "Keluar" di navbar.
 */
require_once __DIR__ . '/../config/session.php';

// Pastikan sesi sudah dimulai sebelum melakukan operasi sesi
// (config/session.php sudah memanggil session_start() jika belum berjalan,
//  tapi ini sebagai pengaman tambahan)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Langkah 1: Kosongkan semua variabel sesi di memori
$_SESSION = [];

// Langkah 2: Hapus cookie sesi dari browser jika ada
// (ini memastikan browser tidak menyimpan session ID lama)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),   // nama cookie sesi (biasanya PHPSESSID)
        '',               // nilai kosong
        time() - 42000,  // waktu kadaluarsa di masa lalu (langsung expired)
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Langkah 3: Hancurkan data sesi di server secara permanen
session_destroy();

// Redirect ke halaman login setelah sesi berhasil dihancurkan
header('Location: ../index.php');
exit;
