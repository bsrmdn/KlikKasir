<?php
/**
 * process/barang_action.php
 *
 * Handler POST untuk operasi CRUD (Create, Read, Update, Delete) data barang.
 * Hanya bisa diakses oleh user dengan role 'admin'.
 *
 * Parameter POST yang wajib ada: action = 'tambah' | 'edit' | 'hapus'
 *
 * Semua aksi menggunakan prepared statement untuk mencegah SQL Injection.
 * Setelah berhasil/gagal, selalu redirect ke gudang.php dengan pesan feedback
 * melalui query string ?msg=...
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan hanya admin yang bisa mengakses endpoint ini
require_role('admin');

// Ambil jenis aksi dari POST data
$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {

    // ── TAMBAH BARANG BARU ──────────────────────────────────────────────────
    case 'tambah':
        // Ambil dan sanitasi input dari form
        $nama     = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $harga    = (float) ($_POST['harga'] ?? 0);
        $stok     = (int) ($_POST['stok'] ?? 0);
        $idGudang = (int) ($_POST['id_gudang'] ?? 0);

        // Validasi: nama, kategori, harga, dan gudang harus valid
        if ($nama === '' || $kategori === '' || $harga < 0 || $idGudang <= 0) {
            header('Location: ../gudang.php?msg=error_barang');
            exit;
        }

        // INSERT barang baru ke database
        $stmt = $koneksi->prepare(
            'INSERT INTO barang (nama_barang, kategori, harga, stok, id_gudang) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssdii', $nama, $kategori, $harga, $stok, $idGudang);
        $ok = $stmt->execute();
        $stmt->close();

        // Redirect dengan pesan sukses atau error
        header('Location: ../gudang.php?msg=' . ($ok ? 'tambah_barang_ok' : 'error_barang'));
        exit;

    // ── EDIT DATA BARANG ────────────────────────────────────────────────────
    case 'edit':
        // Ambil dan sanitasi semua field yang bisa diubah
        $id       = (int) ($_POST['id'] ?? 0);
        $nama     = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $harga    = (float) ($_POST['harga'] ?? 0);
        $stok     = (int) ($_POST['stok'] ?? 0);
        $idGudang = (int) ($_POST['id_gudang'] ?? 0);

        // Validasi: ID barang, nama, kategori, dan gudang harus valid
        if ($id <= 0 || $nama === '' || $kategori === '' || $idGudang <= 0) {
            header('Location: ../gudang.php?msg=error_barang');
            exit;
        }

        // UPDATE data barang yang sesuai ID
        $stmt = $koneksi->prepare(
            'UPDATE barang SET nama_barang = ?, kategori = ?, harga = ?, stok = ?, id_gudang = ? WHERE id = ?'
        );
        $stmt->bind_param('ssdiid', $nama, $kategori, $harga, $stok, $idGudang, $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'edit_barang_ok' : 'error_barang'));
        exit;

    // ── HAPUS BARANG ─────────────────────────────────────────────────────────
    case 'hapus':
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ../gudang.php?msg=error_barang');
            exit;
        }

        // DELETE barang dari database
        // Catatan: Jika barang ada di detail_transaksi, akan gagal karena RESTRICT FK
        // (barang yang pernah terjual tidak bisa dihapus)
        $stmt = $koneksi->prepare('DELETE FROM barang WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'hapus_barang_ok' : 'error_barang'));
        exit;

    // ── AKSI TIDAK DIKENAL ────────────────────────────────────────────────────
    default:
        // Jika action tidak valid, redirect ke gudang tanpa pesan
        header('Location: ../gudang.php');
        exit;
}
