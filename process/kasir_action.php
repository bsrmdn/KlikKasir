<?php
/**
 * process/kasir_action.php
 * Menangani CRUD akun kasir (tambah, edit, hapus).
 * Hanya bisa diakses oleh admin.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {

    case 'tambah':
        $nama     = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($nama === '' || $username === '' || $password === '') {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'kasir';

        $stmt = $koneksi->prepare(
            'INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $nama, $username, $hash, $role);

        if ($stmt->execute()) {
            header('Location: ../kelola_kasir.php?msg=tambah_ok');
        } else {
            // Cek duplikat username (error 1062)
            $code = $koneksi->errno;
            header('Location: ../kelola_kasir.php?msg=' . ($code === 1062 ? 'duplikat' : 'error'));
        }
        $stmt->close();
        exit;

    case 'edit':
        $idUser   = (int) ($_POST['id_user'] ?? 0);
        $nama     = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($idUser <= 0 || $nama === '' || $username === '') {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        if ($password !== '') {
            // Update dengan password baru
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare(
                'UPDATE users SET nama = ?, username = ?, password = ? WHERE id_user = ? AND role = "kasir"'
            );
            $stmt->bind_param('sssi', $nama, $username, $hash, $idUser);
        } else {
            // Update tanpa ubah password
            $stmt = $koneksi->prepare(
                'UPDATE users SET nama = ?, username = ? WHERE id_user = ? AND role = "kasir"'
            );
            $stmt->bind_param('ssi', $nama, $username, $idUser);
        }

        if ($stmt->execute()) {
            header('Location: ../kelola_kasir.php?msg=edit_ok');
        } else {
            $code = $koneksi->errno;
            header('Location: ../kelola_kasir.php?msg=' . ($code === 1062 ? 'duplikat' : 'error'));
        }
        $stmt->close();
        exit;

    case 'hapus':
        $idUser = (int) ($_POST['id_user'] ?? 0);
        if ($idUser <= 0) {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        // Jangan hapus diri sendiri & pastikan hanya kasir yang bisa dihapus lewat sini
        if ($idUser === (int) ($_SESSION['id_user'] ?? 0)) {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        $stmt = $koneksi->prepare('DELETE FROM users WHERE id_user = ? AND role = "kasir"');
        $stmt->bind_param('i', $idUser);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../kelola_kasir.php?msg=' . ($ok ? 'hapus_ok' : 'error'));
        exit;

    default:
        header('Location: ../kelola_kasir.php');
        exit;
}
