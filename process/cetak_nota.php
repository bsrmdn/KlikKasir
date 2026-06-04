<?php
/**
 * process/cetak_nota.php
 * Generate nota transaksi sebagai halaman print-friendly HTML.
 * Bisa dicetak langsung dari browser (Ctrl+P) atau disimpan sebagai PDF.
 *
 * Akses: process/cetak_nota.php?id=<id_transaksi>
 */
require_once __DIR__ . '/../config/session.php';
require_login('../index.php');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$idTransaksi = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idTransaksi <= 0) {
    die('ID transaksi tidak valid.');
}

// Ambil data transaksi
$stmtTrx = $koneksi->prepare(
    'SELECT id_transaksi, tgl_transaksi, total_harga, uang_bayar FROM transaksi WHERE id_transaksi = ? LIMIT 1'
);
$stmtTrx->bind_param('i', $idTransaksi);
$stmtTrx->execute();
$transaksi = $stmtTrx->get_result()->fetch_assoc();
$stmtTrx->close();

if (!$transaksi) {
    die('Transaksi tidak ditemukan.');
}

// Ambil detail item
$stmtDetail = $koneksi->prepare(
    'SELECT b.nama_barang, dt.jumlah AS qty, dt.harga_satuan, dt.subtotal
     FROM detail_transaksi dt
     INNER JOIN barang b ON b.id = dt.id
     WHERE dt.id_transaksi = ?
     ORDER BY dt.id_detail ASC'
);
$stmtDetail->bind_param('i', $idTransaksi);
$stmtDetail->execute();
$detailResult = $stmtDetail->get_result();
$stmtDetail->close();

$items = [];
while ($row = $detailResult->fetch_assoc()) {
    $items[] = $row;
}

$kembalian = (float)$transaksi['uang_bayar'] - (float)$transaksi['total_harga'];
$tglFormatted = date('d F Y, H:i', strtotime($transaksi['tgl_transaksi']));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #<?= h($transaksi['id_transaksi']) ?> — KlikKasir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .nota {
            background: white;
            width: 320px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 0;
            overflow: hidden;
        }

        /* Header Nota */
        .nota-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            text-align: center;
            padding: 20px 16px;
        }
        .nota-header .logo { font-size: 28px; margin-bottom: 4px; }
        .nota-header h1 { font-size: 20px; font-weight: 800; letter-spacing: 1px; }
        .nota-header p { font-size: 11px; opacity: 0.8; margin-top: 2px; }

        /* Info Transaksi */
        .nota-info {
            background: #f8fafc;
            border-bottom: 1px dashed #cbd5e1;
            padding: 12px 16px;
            font-size: 11px;
        }
        .nota-info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .nota-info-row span:first-child { color: #64748b; }
        .nota-info-row span:last-child { font-weight: 700; color: #1e293b; }

        /* Divider dashed */
        .divider {
            border-top: 1px dashed #cbd5e1;
            margin: 0 16px;
        }

        /* Tabel Item */
        .nota-items { padding: 12px 16px; }
        .nota-items-header {
            display: grid;
            grid-template-columns: 1fr 40px 70px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .nota-item {
            display: grid;
            grid-template-columns: 1fr 40px 70px;
            font-size: 11px;
            margin-bottom: 6px;
            line-height: 1.4;
        }
        .nota-item .name { color: #1e293b; }
        .nota-item .qty { text-align: center; color: #64748b; }
        .nota-item .subtotal { text-align: right; font-weight: 700; color: #1e293b; }
        .nota-item .price-per { grid-column: 1 / -1; font-size: 9px; color: #94a3b8; margin-top: 1px; padding-left: 2px; }

        /* Total Area */
        .nota-total {
            background: #f8fafc;
            border-top: 1px dashed #cbd5e1;
            padding: 12px 16px;
            font-size: 12px;
        }
        .nota-total-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .nota-total-row span:first-child { color: #64748b; }
        .nota-total-grand { display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px; color: #4f46e5; }
        .nota-kembalian { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; color: #059669; margin-top: 4px; }

        /* Footer Nota */
        .nota-footer {
            text-align: center;
            padding: 14px 16px;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
        }
        .nota-footer .terimakasih { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 4px; }

        /* Tombol Print (tidak tercetak) */
        .print-controls {
            text-align: center;
            margin-top: 24px;
        }
        .btn-print {
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-print:hover { background: #4338ca; }
        .btn-back {
            background: white;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 8px;
            text-decoration: none;
        }

        @media print {
            body { background: white; padding: 0; display: block; }
            .nota {
                width: 100%;
                border: none;
                box-shadow: none;
                border-radius: 0;
            }
            .print-controls { display: none; }
        }
    </style>
</head>
<body>

<div class="nota">
    <!-- Header -->
    <div class="nota-header">
        <div class="logo">🛒</div>
        <h1>KlikKasir</h1>
        <p>Toko Serba Ada — Jl. Contoh No. 1</p>
    </div>

    <!-- Info Transaksi -->
    <div class="nota-info">
        <div class="nota-info-row">
            <span>No. Transaksi</span>
            <span>#<?= h($transaksi['id_transaksi']) ?></span>
        </div>
        <div class="nota-info-row">
            <span>Tanggal</span>
            <span><?= h($tglFormatted) ?></span>
        </div>
        <div class="nota-info-row">
            <span>Kasir</span>
            <span><?= h($_SESSION['nama'] ?? $_SESSION['username'] ?? '-') ?></span>
        </div>
    </div>

    <!-- Items -->
    <div class="nota-items">
        <div class="nota-items-header">
            <span>Barang</span>
            <span style="text-align:center">Qty</span>
            <span style="text-align:right">Subtotal</span>
        </div>
        <?php foreach ($items as $item): ?>
        <div class="nota-item">
            <span class="name"><?= h($item['nama_barang']) ?></span>
            <span class="qty"><?= h($item['qty']) ?></span>
            <span class="subtotal">Rp <?= number_format((float)$item['subtotal'], 0, ',', '.') ?></span>
            <span class="price-per">@ Rp <?= number_format((float)$item['harga_satuan'], 0, ',', '.') ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="divider"></div>

    <!-- Total -->
    <div class="nota-total">
        <div class="nota-total-row">
            <span>Subtotal Barang</span>
            <span><?= count($items) ?> item(s)</span>
        </div>
        <div class="nota-total-grand">
            <span>TOTAL</span>
            <span>Rp <?= number_format((float)$transaksi['total_harga'], 0, ',', '.') ?></span>
        </div>
        <div class="nota-total-row" style="margin-top:6px">
            <span>Uang Bayar</span>
            <span style="font-weight:700; color:#1e293b">Rp <?= number_format((float)$transaksi['uang_bayar'], 0, ',', '.') ?></span>
        </div>
        <div class="nota-kembalian">
            <span>Kembalian</span>
            <span>Rp <?= number_format($kembalian, 0, ',', '.') ?></span>
        </div>
    </div>

    <!-- Footer -->
    <div class="nota-footer">
        <div class="terimakasih">✨ Terima Kasih! ✨</div>
        <p>Barang yang sudah dibeli tidak dapat dikembalikan.</p>
        <p style="margin-top:4px">Simpan nota ini sebagai bukti pembelian.</p>
    </div>
</div>

<div class="print-controls">
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Nota</button>
    <a href="javascript:window.close()" class="btn-back">Tutup</a>
</div>

</body>
</html>
