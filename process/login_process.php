<?php
/**
 * process/login_process.php
 *
 * Handler untuk proses autentikasi login.
 * Menerima data dari form POST di index.php, memvalidasi kredensial,
 * dan menginisialisasi sesi jika berhasil.
 *
 * Alur:
 *   1. Cek jika sudah login → redirect ke dashboard
 *   2. Ambil username & password dari POST
 *   3. Query database untuk mencari user
 *   4. Verifikasi password
 *   5. Jika cocok: set sesi dan redirect sesuai role
 *   6. Jika gagal: redirect ke login dengan error=1
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Jika user sudah punya sesi aktif, langsung redirect ke dashboard
// (mencegah login ganda)
if (!empty($_SESSION['username'])) {
    header('Location: ../dashboard.php');
    exit;
}

// Ambil dan bersihkan input dari form POST
$username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

// Validasi: kedua field harus diisi
if ($username === '' || $password === '') {
    header('Location: ../index.php?error=1');
    exit;
}

// Query database: cari user berdasarkan username menggunakan prepared statement
// (mencegah SQL Injection)
$stmt = $koneksi->prepare('SELECT id_user, nama, username, password, role FROM users WHERE username = ? LIMIT 1');
if (!$stmt) {
    // Jika query gagal disiapkan, redirect ke login dengan error
    header('Location: ../index.php?error=1');
    exit;
}
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

// Verifikasi password:
//   - password_verify(): untuk password yang sudah di-hash dengan bcrypt (production)
//   - $password === $user['password']: fallback untuk plain-text (seed data awal)
if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {

    // Regenerate session ID untuk mencegah serangan session fixation
    session_regenerate_id(true);

    // Simpan data user ke sesi
    $_SESSION['id_user']  = (int) $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['role']     = $user['role'];

    // Redirect sesuai role:
    //   - Admin → dashboard (akses penuh)
    //   - Kasir → langsung ke halaman kasir/transaksi
    if ($user['role'] === 'admin') {
        header('Location: ../dashboard.php');
    } else {
        header('Location: ../kasir.php');
    }
    exit;
}

// Kredensial tidak valid → kembali ke halaman login dengan kode error
header('Location: ../index.php?error=1');
exit;
