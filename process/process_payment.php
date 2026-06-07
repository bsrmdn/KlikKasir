<?php
/**
 * process/process_payment.php
 *
 * API endpoint JSON untuk memproses pembayaran transaksi penjualan.
 * Dipanggil via fetch() dari app.js dengan metode POST dan body JSON.
 *
 * Alur kerja:
 *   1. Decode JSON input (items[] dan cashAmount)
 *   2. Validasi input dasar
 *   3. Mulai database transaction (InnoDB)
 *   4. Untuk setiap item: lock baris, cek stok, hitung subtotal
 *   5. Validasi uang bayar >= total
 *   6. INSERT ke tabel transaksi → dapatkan ID baru
 *   7. INSERT ke detail_transaksi + UPDATE stok barang (untuk setiap item)
 *   8. COMMIT jika sukses, atau ROLLBACK jika ada error
 *   9. Return JSON dengan data transaksi dan stok terbaru
 *
 * Request JSON:
 *   { "cashAmount": 50000, "items": [{"id": 1, "qty": 2}, ...] }
 *
 * Response JSON (sukses):
 *   { "success": true, "data": { "id_transaksi": 42, "kembalian": 39000, ... } }
 *
 * Response JSON (gagal):
 *   { "success": false, "message": "Pesan error" }
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Selalu return Content-Type JSON agar client tahu format respons
header('Content-Type: application/json; charset=utf-8');

/**
 * Helper: kirim respons JSON dan hentikan eksekusi.
 *
 * @param array $payload     Data yang akan di-encode ke JSON
 * @param int   $statusCode  HTTP status code (default: 200)
 */
function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Baca body request sebagai JSON (untuk fetch() dari JavaScript)
// Fallback ke $_POST jika body bukan JSON valid
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Ekstrak data dari input
$items      = $input['items'] ?? [];       // Array item: [{id, qty}, ...]
$cashAmount = isset($input['cashAmount']) ? (float) $input['cashAmount'] : 0;

// Validasi: keranjang tidak boleh kosong
if (!is_array($items) || count($items) === 0) {
    json_response([
        'success' => false,
        'message' => 'Keranjang masih kosong.',
    ], 400);
}

// Validasi: uang bayar harus positif
if ($cashAmount <= 0) {
    json_response([
        'success' => false,
        'message' => 'Uang bayar harus diisi.',
    ], 400);
}

// Normalisasi dan validasi setiap item dalam keranjang
$normalizedItems = [];

foreach ($items as $item) {
    $id  = isset($item['id'])  ? (int) $item['id']  : 0;
    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;

    // Setiap item harus memiliki id dan qty yang valid (positif)
    if ($id <= 0 || $qty <= 0) {
        json_response([
            'success' => false,
            'message' => 'Data item transaksi tidak valid.',
        ], 400);
    }

    $normalizedItems[] = [
        'id'  => $id,
        'qty' => $qty,
    ];
}

// Mulai database transaction: jika salah satu langkah gagal, semua perubahan di-rollback
$koneksi->begin_transaction();

try {
    $totalHarga = 0.0;   // Akumulator total harga semua item
    $detailRows = [];    // Data detail yang akan di-insert
    $barangRows = [];    // Cache data barang yang sudah dibaca (untuk kalkulasi stok)

    // Prepared statement untuk membaca barang dengan row lock (FOR UPDATE)
    // FOR UPDATE: mencegah race condition jika ada transaksi bersamaan
    $selectItem = $koneksi->prepare('SELECT id, nama_barang, harga, stok FROM barang WHERE id = ? FOR UPDATE');
    if (!$selectItem) {
        throw new RuntimeException('Gagal menyiapkan query barang: ' . $koneksi->error);
    }

    // Proses setiap item dalam keranjang
    foreach ($normalizedItems as $item) {
        $itemId  = $item['id'];
        $itemQty = $item['qty'];

        // Baca data barang dari database (dengan row lock untuk keamanan concurrency)
        $selectItem->bind_param('i', $itemId);
        if (!$selectItem->execute()) {
            throw new RuntimeException('Gagal membaca data barang: ' . $selectItem->error);
        }

        $result = $selectItem->get_result();
        $barang = $result ? $result->fetch_assoc() : null;

        // Pastikan barang ditemukan di database
        if (!$barang) {
            throw new RuntimeException('Ada barang yang tidak ditemukan di database.');
        }

        // Simpan data barang ke cache untuk kalkulasi stok di akhir
        $barangRows[(int) $barang['id']] = $barang;

        // Validasi stok mencukupi untuk jumlah yang diminta
        if ((int) $barang['stok'] < $itemQty) {
            throw new RuntimeException('Stok barang ' . $barang['nama_barang'] . ' tidak mencukupi.');
        }

        // Hitung subtotal item dan tambahkan ke total keseluruhan
        $hargaSatuan = (float) $barang['harga'];
        $subtotal    = $hargaSatuan * $itemQty;
        $totalHarga  += $subtotal;

        // Simpan data detail untuk INSERT ke tabel detail_transaksi
        $detailRows[] = [
            'id'           => $itemId,
            'qty'          => $itemQty,
            'harga_satuan' => $hargaSatuan,
            'subtotal'     => $subtotal,
        ];
    }

    $selectItem->close();

    // Validasi final: uang bayar harus >= total belanja
    if ($cashAmount < $totalHarga) {
        throw new RuntimeException('Uang bayar belum mencukupi.');
    }

    // INSERT transaksi induk ke tabel transaksi
    $insertTransaksi = $koneksi->prepare('INSERT INTO transaksi (tgl_transaksi, total_harga, uang_bayar) VALUES (NOW(), ?, ?)');
    if (!$insertTransaksi) {
        throw new RuntimeException('Gagal menyiapkan transaksi: ' . $koneksi->error);
    }
    $insertTransaksi->bind_param('dd', $totalHarga, $cashAmount);
    if (!$insertTransaksi->execute()) {
        throw new RuntimeException('Gagal menyimpan transaksi: ' . $insertTransaksi->error);
    }

    // Ambil ID transaksi yang baru saja di-insert (auto increment)
    $idTransaksi = (int) $insertTransaksi->insert_id;
    $insertTransaksi->close();

    // Siapkan prepared statement untuk INSERT detail dan UPDATE stok
    $insertDetail = $koneksi->prepare('INSERT INTO detail_transaksi (id_transaksi, id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)');
    if (!$insertDetail) {
        throw new RuntimeException('Gagal menyiapkan detail transaksi: ' . $koneksi->error);
    }
    $updateStock = $koneksi->prepare('UPDATE barang SET stok = stok - ? WHERE id = ?');
    if (!$updateStock) {
        throw new RuntimeException('Gagal menyiapkan update stok: ' . $koneksi->error);
    }

    // Untuk setiap item: insert detail transaksi dan kurangi stok barang
    foreach ($detailRows as $detail) {
        $transaksiId = $idTransaksi;
        $barangId    = $detail['id'];
        $jumlah      = $detail['qty'];
        $hargaSatuan = $detail['harga_satuan'];
        $subtotal    = $detail['subtotal'];

        // INSERT baris detail transaksi
        $insertDetail->bind_param('iiidd', $transaksiId, $barangId, $jumlah, $hargaSatuan, $subtotal);
        if (!$insertDetail->execute()) {
            throw new RuntimeException('Gagal menyimpan detail transaksi: ' . $insertDetail->error);
        }

        // Kurangi stok barang sesuai jumlah yang dibeli
        $updateStock->bind_param('ii', $jumlah, $barangId);
        if (!$updateStock->execute()) {
            throw new RuntimeException('Gagal mengurangi stok: ' . $updateStock->error);
        }
    }

    $insertDetail->close();
    $updateStock->close();

    // Semua operasi berhasil → commit transaction ke database
    $koneksi->commit();

    // Hitung stok terbaru setiap item untuk dikirim ke frontend (update UI tanpa reload)
    $updatedItems = [];
    foreach ($detailRows as $detail) {
        $updatedItems[] = [
            'id'    => $detail['id'],
            'qty'   => $detail['qty'],
            // Stok terbaru = stok lama (dari cache) - jumlah yang baru dibeli
            'stock' => (int) $barangRows[$detail['id']]['stok'] - $detail['qty'],
        ];
    }

    // Kirim respons sukses dengan data transaksi lengkap
    json_response([
        'success' => true,
        'message' => 'Pembayaran berhasil disimpan.',
        'data'    => [
            'id_transaksi' => $idTransaksi,
            'total_harga'  => $totalHarga,
            'uang_bayar'   => $cashAmount,
            'kembalian'    => $cashAmount - $totalHarga,  // Uang kembalian
            'items'        => $updatedItems,               // Data stok terbaru per item
        ],
    ]);
} catch (Throwable $e) {
    // Jika terjadi error di titik mana pun → rollback semua perubahan database
    $koneksi->rollback();
    json_response([
        'success' => false,
        'message' => $e->getMessage(),
    ], 400);
}
