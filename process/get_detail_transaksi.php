<?php
/**
 * process/get_detail_transaksi.php
 *
 * API endpoint JSON untuk mengambil detail lengkap sebuah transaksi.
 * Dipanggil via fetch() GET dari app.js ketika user klik tombol "Detail" 
 * pada tabel riwayat transaksi.
 *
 * Alur:
 *   1. Validasi autentikasi (harus sudah login)
 *   2. Validasi parameter id_transaksi dari GET
 *   3. Query data header transaksi
 *   4. Query detail item (JOIN dengan tabel barang)
 *   5. Return JSON dengan data transaksi + array item
 *
 * Akses: semua user yang sudah login
 *
 * Request:  GET process/get_detail_transaksi.php?id_transaksi=42
 *
 * Response JSON (sukses):
 *   {
 *     "success": true,
 *     "transaksi": { "id_transaksi": 42, "tgl_transaksi": "...", "total_harga": 11000, ... },
 *     "items": [{ "nama_barang": "...", "qty": 2, "harga_satuan": 3500, "subtotal": 7000 }, ...]
 *   }
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Selalu return Content-Type JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Helper: kirim respons JSON dan hentikan eksekusi.
 */
function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Validasi autentikasi: harus sudah login
if (empty($_SESSION['username'])) {
    json_response([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.',
    ], 401);
}

// Validasi parameter: id_transaksi harus positif
$idTransaksi = isset($_GET['id_transaksi']) ? (int) $_GET['id_transaksi'] : 0;
if ($idTransaksi <= 0) {
    json_response([
        'success' => false,
        'message' => 'ID transaksi tidak valid.',
    ], 400);
}

// ── Query 1: Ambil data header transaksi ────────────────────────────────────
$transaksiStmt = $koneksi->prepare(
    'SELECT id_transaksi, tgl_transaksi, total_harga, uang_bayar FROM transaksi WHERE id_transaksi = ?'
);
if (!$transaksiStmt) {
    json_response([
        'success' => false,
        'message' => 'Gagal menyiapkan data transaksi: ' . $koneksi->error,
    ], 500);
}

$transaksiStmt->bind_param('i', $idTransaksi);
$transaksiStmt->execute();
$transaksiResult = $transaksiStmt->get_result();
$transaksi = $transaksiResult ? $transaksiResult->fetch_assoc() : null;
$transaksiStmt->close();

// Jika transaksi tidak ditemukan, return 404
if (!$transaksi) {
    json_response([
        'success' => false,
        'message' => 'Transaksi tidak ditemukan.',
    ], 404);
}

// ── Query 2: Ambil detail item (JOIN dengan tabel barang) ───────────────────
// Menggunakan INNER JOIN untuk mendapatkan nama barang dari tabel barang
$detailStmt = $koneksi->prepare(
    'SELECT b.nama_barang, dt.jumlah AS qty, dt.harga_satuan, dt.subtotal
     FROM detail_transaksi dt
     INNER JOIN barang b ON b.id = dt.id
     WHERE dt.id_transaksi = ?
     ORDER BY dt.id_detail ASC'    // Urutkan sesuai urutan item ditambahkan
);

if (!$detailStmt) {
    json_response([
        'success' => false,
        'message' => 'Gagal menyiapkan detail transaksi: ' . $koneksi->error,
    ], 500);
}

$detailStmt->bind_param('i', $idTransaksi);
$detailStmt->execute();
$detailResult = $detailStmt->get_result();

// Kumpulkan semua item ke dalam array
$items = [];
if ($detailResult) {
    while ($row = $detailResult->fetch_assoc()) {
        $items[] = $row;
    }
}

$detailStmt->close();

// Kirim respons sukses dengan data transaksi lengkap
json_response([
    'success'   => true,
    'transaksi' => [
        'id_transaksi'  => (int) $transaksi['id_transaksi'],
        'tgl_transaksi' => $transaksi['tgl_transaksi'],
        'total_harga'   => (float) $transaksi['total_harga'],
        'uang_bayar'    => (float) $transaksi['uang_bayar'],
        // Hitung kembalian di sisi server agar konsisten
        'kembalian'     => (float) $transaksi['uang_bayar'] - (float) $transaksi['total_harga'],
    ],
    'items'     => $items,  // Array berisi semua item yang dibeli dalam transaksi ini
]);
