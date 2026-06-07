<?php
/**
 * process/kasir_action.php
 *
 * Handler POST untuk operasi CRUD akun kasir (tambah, edit, hapus).
 * Hanya bisa diakses oleh user dengan role 'admin'.
 *
 * Aturan bisnis:
 *   - Admin TIDAK BISA menghapus akun dirinya sendiri (cegah lock-out)
 *   - Hanya akun dengan role 'kasir' yang bisa dikelola melalui halaman ini
 *     (admin tidak bisa menghapus admin lain dari sini)
 *   - Password selalu di-hash dengan bcrypt (password_hash)
 *   - Saat edit: jika field password dikosongkan, password lama tetap digunakan
 *   - Duplicate username ditangkap secara khusus (MySQL error 1062)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Hanya admin yang boleh mengakses endpoint ini
require_role('admin');

// Ambil jenis aksi dari POST data
$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {

    // ── TAMBAH KASIR BARU ──────────────────────────────────────────────────
    case 'tambah':
        $nama     = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Semua field wajib diisi saat menambah kasir baru
        if ($nama === '' || $username === '' || $password === '') {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        // Hash password dengan bcrypt (PASSWORD_DEFAULT) sebelum disimpan ke database
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'kasir'; // Role selalu 'kasir' untuk halaman ini

        $stmt = $koneksi->prepare(
            'INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $nama, $username, $hash, $role);

        if ($stmt->execute()) {
            header('Location: ../kelola_kasir.php?msg=tambah_ok');
        } else {
            // Tangkap error duplicate entry (username sudah digunakan)
            // MySQL error code 1062 = Duplicate entry for UNIQUE key
            $code = $koneksi->errno;
            header('Location: ../kelola_kasir.php?msg=' . ($code === 1062 ? 'duplikat' : 'error'));
        }
        $stmt->close();
        exit;

    // ── EDIT DATA KASIR ────────────────────────────────────────────────────
    case 'edit':
        $idUser   = (int) ($_POST['id_user'] ?? 0);
        $nama     = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Validasi: ID, nama, dan username harus valid
        if ($idUser <= 0 || $nama === '' || $username === '') {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        if ($password !== '') {
            // Jika password baru diisi: update termasuk hash password baru
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare(
                'UPDATE users SET nama = ?, username = ?, password = ? WHERE id_user = ? AND role = "kasir"'
            );
            $stmt->bind_param('sssi', $nama, $username, $hash, $idUser);
        } else {
            // Jika password dikosongkan: hanya update nama dan username, password lama dipertahankan
            $stmt = $koneksi->prepare(
                'UPDATE users SET nama = ?, username = ? WHERE id_user = ? AND role = "kasir"'
            );
            $stmt->bind_param('ssi', $nama, $username, $idUser);
        }

        if ($stmt->execute()) {
            header('Location: ../kelola_kasir.php?msg=edit_ok');
        } else {
            // Tangkap error duplicate username
            $code = $koneksi->errno;
            header('Location: ../kelola_kasir.php?msg=' . ($code === 1062 ? 'duplikat' : 'error'));
        }
        $stmt->close();
        exit;

    // ── HAPUS KASIR ───────────────────────────────────────────────────────
    case 'hapus':
        $idUser = (int) ($_POST['id_user'] ?? 0);
        if ($idUser <= 0) {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        // Keamanan: cegah admin menghapus akun dirinya sendiri
        // (akan menyebabkan admin ter-logout dan tidak bisa masuk kembali)
        if ($idUser === (int) ($_SESSION['id_user'] ?? 0)) {
            header('Location: ../kelola_kasir.php?msg=error');
            exit;
        }

        // DELETE hanya untuk user dengan role 'kasir' (admin tidak bisa dihapus dari sini)
        $stmt = $koneksi->prepare('DELETE FROM users WHERE id_user = ? AND role = "kasir"');
        $stmt->bind_param('i', $idUser);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../kelola_kasir.php?msg=' . ($ok ? 'hapus_ok' : 'error'));
        exit;

    // ── AKSI TIDAK DIKENAL ─────────────────────────────────────────────────
    default:
        header('Location: ../kelola_kasir.php');
        exit;
}
