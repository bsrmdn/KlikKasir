<?php
require_once __DIR__ . '/database.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$stats = [
    'total_barang' => 0,
    'total_unit' => 0,
    'nilai_aset' => 0,
];

$statResult = $koneksi->query(
    'SELECT COUNT(*) AS total_barang, COALESCE(SUM(stok), 0) AS total_unit, COALESCE(SUM(stok * harga), 0) AS nilai_aset FROM barang'
);

if ($statResult) {
    $statData = $statResult->fetch_assoc();
    if ($statData) {
        $stats = $statData;
    }
}

$barangResult = $koneksi->query(
    'SELECT b.id, b.nama_barang, b.kategori, b.harga, b.stok, g.nama_gudang, g.lokasi
     FROM barang b
     INNER JOIN gudang g ON g.id_gudang = b.id_gudang
     ORDER BY b.nama_barang ASC'
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gudang - Stok</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <header>
        <div class="nav-container">
            <div class="logo">📦 Gudang Stok</div>
            <nav class="nav-links">
                <a href="kasir.php" class="nav-item">Kasir</a>
                <a href="gudang.php" class="nav-item active">Gudang</a>
                <button onclick="logout()" class="nav-item" style="border:none; background:none; cursor:pointer;">Keluar</button>
            </nav>
        </div>
    </header>

    <main>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Barang</div>
                <div id="statSKU" class="stat-value"><?= h($stats['total_barang'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Unit</div>
                <div id="statUnits" class="stat-value"><?= h($stats['total_unit'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Nilai Aset</div>
                <div id="statValue" class="stat-value">Rp <?= number_format((float) ($stats['nilai_aset'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="card table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Gudang</th>
                        <th style="text-align: right;">Harga</th>
                        <th style="text-align: center;">Stok</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($barangResult && $barangResult->num_rows > 0): ?>
                        <?php while ($barang = $barangResult->fetch_assoc()): ?>
                            <tr data-barang-row="<?= h($barang['id']) ?>">
                                <td style="font-weight:700"><?= h($barang['nama_barang']) ?></td>
                                <td><span class="badge"><?= h($barang['kategori']) ?></span></td>
                                <td>
                                    <?= h($barang['nama_gudang']) ?><br>
                                    <small style="color: var(--text-muted);"><?= h($barang['lokasi']) ?></small>
                                </td>
                                <td style="text-align:right">Rp <?= number_format((float) $barang['harga'], 0, ',', '.') ?></td>
                                <td style="text-align:center">
                                    <span data-stock-badge class="stock-badge <?= ((int) $barang['stok'] <= 10) ? 'stock-low' : 'stock-ok' ?>"><?= h($barang['stok']) ?> pcs</span>
                                </td>
                                <td style="text-align:right">
                                    <button type="button" class="btn-primary" style="width:auto; padding: 8px 12px;" onclick="addStock(<?= (int) $barang['id'] ?>)">+ Stok</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color: var(--text-muted);">Belum ada data barang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="app.js"></script>
</body>

</html>