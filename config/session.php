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
