<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Jika sudah login, redirect ke dashboard
if (!empty($_SESSION['username'])) {
    header('Location: ../dashboard.php');
    exit;
}

$username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

if ($username === '' || $password === '') {
    header('Location: ../index.php?error=1');
    exit;
}

$stmt = $koneksi->prepare('SELECT id_user, nama, username, password, role FROM users WHERE username = ? LIMIT 1');
if (!$stmt) {
    header('Location: ../index.php?error=1');
    exit;
}
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['id_user']  = (int) $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['role']     = $user['role'];

    // Admin ke dashboard, kasir langsung ke kasir
    if ($user['role'] === 'admin') {
        header('Location: ../dashboard.php');
    } else {
        header('Location: ../kasir.php');
    }
    exit;
}

header('Location: ../index.php?error=1');
exit;
