<?php
/**
 * process/add_stock.php
 *
 * API endpoint JSON untuk MENAMBAH stok barang (operasi penambahan/increment).
 * Endpoint ini menambahkan sejumlah unit ke stok yang sudah ada (stok += amount).
 *
 * Catatan: Endpoint ini adalah versi "increment only". Untuk operasi yang lebih
 * fleksibel (set langsung, tambah, atau kurangi), gunakan update_stock_action.php.
 *
 * Dipanggil via: fetch() POST dari JavaScript (app.js)
 *
 * Request JSON: { "id": 1, "amount": 50 }
 *
 * Response JSON (sukses):
 *   { "success": true, "data": { "id": 1, "stok": 170, "stok_lama": 120, "delta_unit": 50, ... } }
 *
 * Response JSON (gagal):
 *   { "success": false, "message": "Pesan error" }
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

// Baca body request sebagai JSON; fallback ke $_POST jika bukan JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Ekstrak parameter dari input
$id     = isset($input['id'])     ? (int) $input['id']     : 0;   // ID barang
$amount = isset($input['amount']) ? (int) $input['amount'] : 0;   // Jumlah yang akan ditambahkan

// Validasi: ID dan jumlah harus positif
if ($id <= 0 || $amount <= 0) {
    json_response([
        'success' => false,
        'message' => 'ID barang dan jumlah stok harus valid.',
    ], 400);
}

// Verifikasi barang ada di database dan ambil data saat ini
$check = $koneksi->prepare('SELECT id, nama_barang, harga, stok FROM barang WHERE id = ?');
$check->bind_param('i', $id);
$check->execute();
$result = $check->get_result();
$barang = $result ? $result->fetch_assoc() : null;
$check->close();

// Jika barang tidak ditemukan, return 404
if (!$barang) {
    json_response([
        'success' => false,
        'message' => 'Barang tidak ditemukan.',
    ], 404);
}

// Tambahkan stok menggunakan operasi atomic (stok = stok + amount)
// Lebih aman daripada membaca lalu menulis karena tidak ada race condition
$update = $koneksi->prepare('UPDATE barang SET stok = stok + ? WHERE id = ?');
$update->bind_param('ii', $amount, $id);

if (!$update->execute()) {
    json_response([
        'success' => false,
        'message' => 'Gagal menambah stok: ' . $update->error,
    ], 500);
}

$update->close();

// Hitung stok baru untuk dikirimkan ke frontend
$newStock = (int) $barang['stok'] + $amount;

// Kirim respons sukses dengan detail perubahan stok
json_response([
    'success' => true,
    'message' => 'Stok berhasil ditambahkan.',
    'data'    => [
        'id'          => $id,
        'nama_barang' => $barang['nama_barang'],
        'harga'       => (float) $barang['harga'],
        'stok_lama'   => (int) $barang['stok'],    // Stok sebelum penambahan
        'stok'        => $newStock,                  // Stok setelah penambahan
        'delta_unit'  => $amount,                    // Jumlah unit yang ditambahkan
        'delta_value' => (float) $barang['harga'] * $amount, // Nilai rupiah yang bertambah
    ],
]);
