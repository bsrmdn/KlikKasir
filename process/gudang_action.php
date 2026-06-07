<?php
/**
 * process/gudang_action.php
 *
 * Handler POST untuk operasi CRUD (Create, Update, Delete) data gudang.
 * Hanya bisa diakses oleh user dengan role 'admin'.
 *
 * Parameter POST yang wajib ada: action = 'tambah' | 'edit' | 'hapus'
 *
 * Aturan bisnis penting:
 *   - Gudang TIDAK BISA dihapus jika masih ada barang di dalamnya.
 *     Sistem mengecek jumlah barang terkait sebelum eksekusi DELETE.
 *
 * Semua aksi menggunakan prepared statement.
 * Hasil redirect ke gudang.php?msg=... untuk menampilkan feedback.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan hanya admin yang bisa mengakses endpoint ini
require_role('admin');

// Ambil jenis aksi dari POST data
$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {

    // ── TAMBAH GUDANG BARU ─────────────────────────────────────────────────
    case 'tambah':
        // Ambil dan sanitasi nama gudang dan lokasi
        $nama   = trim((string) ($_POST['nama_gudang'] ?? ''));
        $lokasi = trim((string) ($_POST['lokasi'] ?? ''));

        // Validasi: kedua field harus diisi
        if ($nama === '' || $lokasi === '') {
            header('Location: ../gudang.php?msg=error_gudang');
            exit;
        }

        // INSERT gudang baru ke database
        $stmt = $koneksi->prepare('INSERT INTO gudang (nama_gudang, lokasi) VALUES (?, ?)');
        $stmt->bind_param('ss', $nama, $lokasi);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'tambah_gudang_ok' : 'error_gudang'));
        exit;

    // ── EDIT DATA GUDANG ───────────────────────────────────────────────────
    case 'edit':
        $id     = (int) ($_POST['id_gudang'] ?? 0);
        $nama   = trim((string) ($_POST['nama_gudang'] ?? ''));
        $lokasi = trim((string) ($_POST['lokasi'] ?? ''));

        // Validasi: ID, nama, dan lokasi harus valid
        if ($id <= 0 || $nama === '' || $lokasi === '') {
            header('Location: ../gudang.php?msg=error_gudang');
            exit;
        }

        // UPDATE data gudang berdasarkan ID
        $stmt = $koneksi->prepare('UPDATE gudang SET nama_gudang = ?, lokasi = ? WHERE id_gudang = ?');
        $stmt->bind_param('ssi', $nama, $lokasi, $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'edit_gudang_ok' : 'error_gudang'));
        exit;

    // ── HAPUS GUDANG ───────────────────────────────────────────────────────
    case 'hapus':
        $id = (int) ($_POST['id_gudang'] ?? 0);
        if ($id <= 0) {
            header('Location: ../gudang.php?msg=error_gudang');
            exit;
        }

        // ── Cek bisnis rule: gudang tidak boleh dihapus jika masih punya barang ──
        // Ini adalah proteksi manual selain constraint FK RESTRICT di database
        $cek = $koneksi->prepare('SELECT COUNT(*) AS c FROM barang WHERE id_gudang = ?');
        $cek->bind_param('i', $id);
        $cek->execute();
        $jumlah = (int) $cek->get_result()->fetch_assoc()['c'];
        $cek->close();

        // Jika masih ada barang di gudang, tolak penghapusan
        if ($jumlah > 0) {
            header('Location: ../gudang.php?msg=gudang_ada_barang');
            exit;
        }

        // Aman untuk dihapus: tidak ada barang di gudang ini
        $stmt = $koneksi->prepare('DELETE FROM gudang WHERE id_gudang = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../gudang.php?msg=' . ($ok ? 'hapus_gudang_ok' : 'error_gudang'));
        exit;

    // ── AKSI TIDAK DIKENAL ─────────────────────────────────────────────────
    default:
        header('Location: ../gudang.php');
        exit;
}
