<?php
require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$id = isset($input['id']) ? (int) $input['id'] : 0;
$amount = isset($input['amount']) ? (int) $input['amount'] : 0;

if ($id <= 0 || $amount <= 0) {
    json_response([
        'success' => false,
        'message' => 'ID barang dan jumlah stok harus valid.',
    ], 400);
}

$check = $koneksi->prepare('SELECT id, nama_barang, harga, stok FROM barang WHERE id = ?');
$check->bind_param('i', $id);
$check->execute();
$result = $check->get_result();
$barang = $result ? $result->fetch_assoc() : null;
$check->close();

if (!$barang) {
    json_response([
        'success' => false,
        'message' => 'Barang tidak ditemukan.',
    ], 404);
}

$update = $koneksi->prepare('UPDATE barang SET stok = stok + ? WHERE id = ?');
$update->bind_param('ii', $amount, $id);

if (!$update->execute()) {
    json_response([
        'success' => false,
        'message' => 'Gagal menambah stok: ' . $update->error,
    ], 500);
}

$update->close();

$newStock = (int) $barang['stok'] + $amount;

json_response([
    'success' => true,
    'message' => 'Stok berhasil ditambahkan.',
    'data' => [
        'id' => $id,
        'nama_barang' => $barang['nama_barang'],
        'harga' => (float) $barang['harga'],
        'stok_lama' => (int) $barang['stok'],
        'stok' => $newStock,
        'delta_unit' => $amount,
        'delta_value' => (float) $barang['harga'] * $amount,
    ],
]);
