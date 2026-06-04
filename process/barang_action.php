<?php
/**
 * process/barang_action.php
 * Menangani CRUD barang (tambah, edit, hapus).
 * Hanya bisa diakses oleh admin.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {

    case 'tambah':
        $nama     = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $harga    = (float) ($_POST['harga'] ?? 0);
        $stok     = (int) ($_POST['stok'] ?? 0);
        $idGudang = (int) ($_POST['id_gudang'] ?? 0);

        if ($nama === '' || $kategori === '' || $harga < 0 || $idGudang <= 0) {
            header('Location: ../gudang.php?msg=error_barang');
            exit;
        }

        $stmt = $koneksi->prepare(
            'INSERT INTO barang (nama_barang, kategori, harga, stok, id_gudang) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssdii', $nama, $kategori, $harga, $stok, $idGudang);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'tambah_barang_ok' : 'error_barang'));
        exit;

    case 'edit':
        $id       = (int) ($_POST['id'] ?? 0);
        $nama     = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $harga    = (float) ($_POST['harga'] ?? 0);
        $stok     = (int) ($_POST['stok'] ?? 0);
        $idGudang = (int) ($_POST['id_gudang'] ?? 0);

        if ($id <= 0 || $nama === '' || $kategori === '' || $idGudang <= 0) {
            header('Location: ../gudang.php?msg=error_barang');
            exit;
        }

        $stmt = $koneksi->prepare(
            'UPDATE barang SET nama_barang = ?, kategori = ?, harga = ?, stok = ?, id_gudang = ? WHERE id = ?'
        );
        $stmt->bind_param('ssdiid', $nama, $kategori, $harga, $stok, $idGudang, $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'edit_barang_ok' : 'error_barang'));
        exit;

    case 'hapus':
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ../gudang.php?msg=error_barang');
            exit;
        }

        $stmt = $koneksi->prepare('DELETE FROM barang WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'hapus_barang_ok' : 'error_barang'));
        exit;

    default:
        header('Location: ../gudang.php');
        exit;
}
