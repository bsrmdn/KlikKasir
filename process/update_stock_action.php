<?php
/**
 * process/update_stock_action.php
 *
 * API endpoint JSON untuk mengubah stok barang dengan nilai absolut (SET langsung).
 * Dipanggil via fetch() POST dari app.js saat user menggunakan modal Update Stok.
 *
 * Berbeda dengan add_stock.php (yang hanya increment), endpoint ini:
 *   - Menerima nilai stok_baru yang langsung diset sebagai nilai stok barang
 *   - Mendukung operasi: Set Langsung, Tambah, dan Kurangi (kalkulasi dilakukan di JS)
 *
 * Akses: semua user yang sudah login (admin maupun kasir)
 *
 * Request JSON: { "id_barang": 1, "stok_baru": 150 }
 * Alternatif:   { "id": 1, "stok_baru": 150 }  (kompatibilitas backward)
 *
 * Response JSON (sukses):
 *   { "success": true, "data": { "id": 1, "nama_barang": "...", "stok_lama": 120, "stok": 150 } }
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Selalu return Content-Type JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Helper: kirim respons JSON dan hentikan eksekusi.
 *
 * @param array $payload     Data yang akan di-encode ke JSON
 * @param int   $statusCode  HTTP status code
 */
function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Baca body JSON; fallback ke $_POST
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Validasi autentikasi: harus sudah login
if (empty($_SESSION['username'])) {
    json_response([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.',
    ], 401);
}

// Ambil ID barang: support dua nama field (id_barang atau id) untuk kompatibilitas
$idBarang = isset($input['id_barang'])
    ? (int) $input['id_barang']
    : (isset($input['id']) ? (int) $input['id'] : 0);

// Ambil nilai stok baru yang akan di-set (harus >= 0, tidak boleh negatif)
$stokBaru = isset($input['stok_baru']) ? (int) $input['stok_baru'] : -1;

// Validasi parameter
if ($idBarang <= 0 || $stokBaru < 0) {
    json_response([
        'success' => false,
        'message' => 'ID barang dan stok baru harus valid.',
    ], 400);
}

// Verifikasi barang ada di database (dan baca data lama untuk response)
$checkStmt = $koneksi->prepare('SELECT id, nama_barang, stok FROM barang WHERE id = ?');
if (!$checkStmt) {
    json_response([
        'success' => false,
        'message' => 'Gagal menyiapkan validasi barang: ' . $koneksi->error,
    ], 500);
}

$checkStmt->bind_param('i', $idBarang);
$checkStmt->execute();
$result = $checkStmt->get_result();
$barang = $result ? $result->fetch_assoc() : null;
$checkStmt->close();

// Jika barang tidak ditemukan, return 404
if (!$barang) {
    json_response([
        'success' => false,
        'message' => 'Barang tidak ditemukan.',
    ], 404);
}

// UPDATE stok barang langsung ke nilai stok_baru (bukan increment/decrement)
$updateStmt = $koneksi->prepare('UPDATE barang SET stok = ? WHERE id = ?');
if (!$updateStmt) {
    json_response([
        'success' => false,
        'message' => 'Gagal menyiapkan update stok: ' . $koneksi->error,
    ], 500);
}

$updateStmt->bind_param('ii', $stokBaru, $idBarang);

if (!$updateStmt->execute()) {
    json_response([
        'success' => false,
        'message' => 'Gagal memperbarui stok: ' . $updateStmt->error,
    ], 500);
}

$updateStmt->close();

// Kirim respons sukses dengan data perubahan stok untuk update UI di frontend
json_response([
    'success' => true,
    'message' => 'Stok berhasil diperbarui.',
    'data'    => [
        'id'          => $idBarang,
        'nama_barang' => $barang['nama_barang'],
        'stok_lama'   => (int) $barang['stok'],  // Stok sebelum diubah
        'stok'        => $stokBaru,               // Stok setelah diubah
    ],
]);
