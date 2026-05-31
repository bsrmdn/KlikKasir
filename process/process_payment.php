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

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$items = $input['items'] ?? [];
$cashAmount = isset($input['cashAmount']) ? (float) $input['cashAmount'] : 0;

if (!is_array($items) || count($items) === 0) {
    json_response([
        'success' => false,
        'message' => 'Keranjang masih kosong.',
    ], 400);
}

if ($cashAmount <= 0) {
    json_response([
        'success' => false,
        'message' => 'Uang bayar harus diisi.',
    ], 400);
}

$normalizedItems = [];

foreach ($items as $item) {
    $id = isset($item['id']) ? (int) $item['id'] : 0;
    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;

    if ($id <= 0 || $qty <= 0) {
        json_response([
            'success' => false,
            'message' => 'Data item transaksi tidak valid.',
        ], 400);
    }

    $normalizedItems[] = [
        'id' => $id,
        'qty' => $qty,
    ];
}

$koneksi->begin_transaction();

try {
    $totalHarga = 0.0;
    $detailRows = [];
    $barangRows = [];

    $selectItem = $koneksi->prepare('SELECT id, nama_barang, harga, stok FROM barang WHERE id = ? FOR UPDATE');
    if (!$selectItem) {
        throw new RuntimeException('Gagal menyiapkan query barang: ' . $koneksi->error);
    }

    foreach ($normalizedItems as $item) {
        $itemId = $item['id'];
        $itemQty = $item['qty'];

        $selectItem->bind_param('i', $itemId);
        if (!$selectItem->execute()) {
            throw new RuntimeException('Gagal membaca data barang: ' . $selectItem->error);
        }

        $result = $selectItem->get_result();
        $barang = $result ? $result->fetch_assoc() : null;

        if (!$barang) {
            throw new RuntimeException('Ada barang yang tidak ditemukan di database.');
        }

        $barangRows[(int) $barang['id']] = $barang;

        if ((int) $barang['stok'] < $itemQty) {
            throw new RuntimeException('Stok barang ' . $barang['nama_barang'] . ' tidak mencukupi.');
        }

        $hargaSatuan = (float) $barang['harga'];
        $subtotal = $hargaSatuan * $itemQty;
        $totalHarga += $subtotal;

        $detailRows[] = [
            'id' => $itemId,
            'qty' => $itemQty,
            'harga_satuan' => $hargaSatuan,
            'subtotal' => $subtotal,
        ];
    }

    $selectItem->close();

    if ($cashAmount < $totalHarga) {
        throw new RuntimeException('Uang bayar belum mencukupi.');
    }

    $insertTransaksi = $koneksi->prepare('INSERT INTO transaksi (tgl_transaksi, total_harga, uang_bayar) VALUES (NOW(), ?, ?)');
    if (!$insertTransaksi) {
        throw new RuntimeException('Gagal menyiapkan transaksi: ' . $koneksi->error);
    }
    $insertTransaksi->bind_param('dd', $totalHarga, $cashAmount);
    if (!$insertTransaksi->execute()) {
        throw new RuntimeException('Gagal menyimpan transaksi: ' . $insertTransaksi->error);
    }

    $idTransaksi = (int) $insertTransaksi->insert_id;
    $insertTransaksi->close();

    $insertDetail = $koneksi->prepare('INSERT INTO detail_transaksi (id_transaksi, id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)');
    if (!$insertDetail) {
        throw new RuntimeException('Gagal menyiapkan detail transaksi: ' . $koneksi->error);
    }
    $updateStock = $koneksi->prepare('UPDATE barang SET stok = stok - ? WHERE id = ?');
    if (!$updateStock) {
        throw new RuntimeException('Gagal menyiapkan update stok: ' . $koneksi->error);
    }

    foreach ($detailRows as $detail) {
        $transaksiId = $idTransaksi;
        $barangId = $detail['id'];
        $jumlah = $detail['qty'];
        $hargaSatuan = $detail['harga_satuan'];
        $subtotal = $detail['subtotal'];

        $insertDetail->bind_param('iiidd', $transaksiId, $barangId, $jumlah, $hargaSatuan, $subtotal);

        if (!$insertDetail->execute()) {
            throw new RuntimeException('Gagal menyimpan detail transaksi: ' . $insertDetail->error);
        }

        $updateStock->bind_param('ii', $jumlah, $barangId);
        if (!$updateStock->execute()) {
            throw new RuntimeException('Gagal mengurangi stok: ' . $updateStock->error);
        }
    }

    $insertDetail->close();
    $updateStock->close();

    $koneksi->commit();

    $updatedItems = [];
    foreach ($detailRows as $detail) {
        $updatedItems[] = [
            'id' => $detail['id'],
            'qty' => $detail['qty'],
            'stock' => (int) $barangRows[$detail['id']]['stok'] - $detail['qty'],
        ];
    }

    json_response([
        'success' => true,
        'message' => 'Pembayaran berhasil disimpan.',
        'data' => [
            'id_transaksi' => $idTransaksi,
            'total_harga' => $totalHarga,
            'uang_bayar' => $cashAmount,
            'kembalian' => $cashAmount - $totalHarga,
            'items' => $updatedItems,
        ],
    ]);
} catch (Throwable $e) {
    $koneksi->rollback();
    json_response([
        'success' => false,
        'message' => $e->getMessage(),
    ], 400);
}
