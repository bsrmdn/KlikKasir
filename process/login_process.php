<?php
require_once __DIR__ . '/../config/session.php';

$username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
$password = isset($_POST['password']) ? trim((string) $_POST['password']) : '';

$isValid = $username === 'admin' && $password === '1234';

if ($isValid) {
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'admin';
    header('Location: ../kasir.php');
    exit;
}

header('Location: ../index.php?error=1');
exit;
