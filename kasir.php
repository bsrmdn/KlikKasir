<?php
require_once __DIR__ . '/database.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$barangResult = $koneksi->query(
    'SELECT b.id, b.nama_barang, b.kategori, b.harga, b.stok, g.nama_gudang
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
    <title>Kasir - Toko</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <header>
        <div class="nav-container">
            <div class="logo">🛒 Kasir Toko</div>
            <nav class="nav-links">
                <a href="kasir.php" class="nav-item active">Kasir</a>
                <a href="gudang.php" class="nav-item">Gudang</a>
                <button onclick="logout()" class="nav-item" style="border:none; background:none; cursor:pointer;">Keluar</button>
            </nav>
        </div>
    </header>

    <main class="cashier-layout">
        <section>
            <h2>Transaksi</h2>
            <input type="text" id="searchBar" class="search-input" placeholder="Cari produk...">
            <div id="productGrid" class="product-grid">
                <?php if ($barangResult && $barangResult->num_rows > 0): ?>
                    <?php while ($barang = $barangResult->fetch_assoc()): ?>
                        <button type="button"
                            class="product-btn"
                            data-product-card
                            data-id="<?= h($barang['id']) ?>"
                            data-name="<?= h($barang['nama_barang']) ?>"
                            data-category="<?= h($barang['kategori']) ?>"
                            data-price="<?= h($barang['harga']) ?>"
                            data-stock="<?= h($barang['stok']) ?>"
                            data-gudang="<?= h($barang['nama_gudang']) ?>"
                            onclick="addToCart(this)">
                            <span class="badge"><?= h($barang['kategori']) ?></span>
                            <div style="font-weight: 700; margin: 8px 0;"><?= h($barang['nama_barang']) ?></div>
                            <div style="color: var(--primary); font-weight: 800;">Rp <?= number_format((float) $barang['harga'], 0, ',', '.') ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">Gudang: <?= h($barang['nama_gudang']) ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">Sisa: <span data-stock-badge><?= h($barang['stok']) ?></span></div>
                        </button>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card p-4" style="grid-column: 1 / -1; color: var(--text-muted);">Belum ada data barang.</div>
                <?php endif; ?>
            </div>
        </section>

        <aside>
            <div class="card p-4">
                <h3 style="margin-bottom: 1rem;">Keranjang</h3>
                <div id="cartItems" style="min-height: 200px;"></div>
                <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 1.2rem;">
                    <span>Total</span>
                    <span id="totalPrice">Rp 0</span>
                </div>
                <input type="number" id="cashAmount" class="search-input" style="margin-top: 1rem;" placeholder="Uang Bayar">
                <button id="btnPay" class="btn-primary" disabled onclick="processPayment()">BAYAR SEKARANG</button>
            </div>
        </aside>
    </main>

    <script src="app.js"></script>
</body>

</html>