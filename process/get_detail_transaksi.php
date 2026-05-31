<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if (empty($_SESSION['username'])) {
    json_response([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.',
    ], 401);
}

$idTransaksi = isset($_GET['id_transaksi']) ? (int) $_GET['id_transaksi'] : 0;
if ($idTransaksi <= 0) {
    json_response([
        'success' => false,
        'message' => 'ID transaksi tidak valid.',
    ], 400);
}

$transaksiStmt = $koneksi->prepare('SELECT id_transaksi, tgl_transaksi, total_harga, uang_bayar FROM transaksi WHERE id_transaksi = ?');
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

if (!$transaksi) {
    json_response([
        'success' => false,
        'message' => 'Transaksi tidak ditemukan.',
    ], 404);
}

$detailStmt = $koneksi->prepare(
    'SELECT b.nama_barang, dt.jumlah AS qty, dt.harga_satuan, dt.subtotal
     FROM detail_transaksi dt
     INNER JOIN barang b ON b.id = dt.id
     WHERE dt.id_transaksi = ?
     ORDER BY dt.id_detail ASC'
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

$items = [];
if ($detailResult) {
    while ($row = $detailResult->fetch_assoc()) {
        $items[] = $row;
    }
}

$detailStmt->close();

json_response([
    'success' => true,
    'transaksi' => [
        'id_transaksi' => (int) $transaksi['id_transaksi'],
        'tgl_transaksi' => $transaksi['tgl_transaksi'],
        'total_harga' => (float) $transaksi['total_harga'],
        'uang_bayar' => (float) $transaksi['uang_bayar'],
        'kembalian' => (float) $transaksi['uang_bayar'] - (float) $transaksi['total_harga'],
    ],
    'items' => $items,
]);
