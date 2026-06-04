<?php
/**
 * process/gudang_action.php
 * Menangani CRUD gudang (tambah, edit, hapus).
 * Hanya bisa diakses oleh admin.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {

    case 'tambah':
        $nama   = trim((string) ($_POST['nama_gudang'] ?? ''));
        $lokasi = trim((string) ($_POST['lokasi'] ?? ''));

        if ($nama === '' || $lokasi === '') {
            header('Location: ../gudang.php?msg=error_gudang');
            exit;
        }

        $stmt = $koneksi->prepare('INSERT INTO gudang (nama_gudang, lokasi) VALUES (?, ?)');
        $stmt->bind_param('ss', $nama, $lokasi);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'tambah_gudang_ok' : 'error_gudang'));
        exit;

    case 'edit':
        $id     = (int) ($_POST['id_gudang'] ?? 0);
        $nama   = trim((string) ($_POST['nama_gudang'] ?? ''));
        $lokasi = trim((string) ($_POST['lokasi'] ?? ''));

        if ($id <= 0 || $nama === '' || $lokasi === '') {
            header('Location: ../gudang.php?msg=error_gudang');
            exit;
        }

        $stmt = $koneksi->prepare('UPDATE gudang SET nama_gudang = ?, lokasi = ? WHERE id_gudang = ?');
        $stmt->bind_param('ssi', $nama, $lokasi, $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'edit_gudang_ok' : 'error_gudang'));
        exit;

    case 'hapus':
        $id = (int) ($_POST['id_gudang'] ?? 0);
        if ($id <= 0) {
            header('Location: ../gudang.php?msg=error_gudang');
            exit;
        }

        // Cek apakah gudang masih punya barang
        $cek = $koneksi->prepare('SELECT COUNT(*) AS c FROM barang WHERE id_gudang = ?');
        $cek->bind_param('i', $id);
        $cek->execute();
        $jumlah = (int) $cek->get_result()->fetch_assoc()['c'];
        $cek->close();

        if ($jumlah > 0) {
            header('Location: ../gudang.php?msg=gudang_ada_barang');
            exit;
        }

        $stmt = $koneksi->prepare('DELETE FROM gudang WHERE id_gudang = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'hapus_gudang_ok' : 'error_gudang'));
        exit;

    default:
        header('Location: ../gudang.php');
        exit;
}
