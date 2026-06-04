<?php
require_once __DIR__ . '/config/session.php';
require_login('index.php');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

$barangResult = $koneksi->query(
    'SELECT b.id, b.nama_barang, b.kategori, b.harga, b.stok, g.nama_gudang
     FROM barang b
     INNER JOIN gudang g ON g.id_gudang = b.id_gudang
     ORDER BY b.nama_barang ASC'
);

$pageTitle   = 'Kasir — KlikKasir';
$bodyClass   = 'min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 text-slate-800';
$activePage  = 'kasir';
$pageScripts = ['assets/js/app.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="mx-auto grid max-w-7xl gap-6 px-4 py-6 lg:grid-cols-3">
    <section class="space-y-4 lg:col-span-2">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Transaksi</h1>
            <p class="text-sm text-slate-500">Cari barang dan tambahkan langsung ke keranjang.</p>
        </div>

        <input type="text" id="searchBar" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" placeholder="Cari produk...">

        <div id="productGrid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <?php if ($barangResult && $barangResult->num_rows > 0): ?>
                <?php while ($barang = $barangResult->fetch_assoc()): ?>
                    <button type="button"
                            class="group rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-1 hover:border-indigo-300 hover:shadow-lg hover:shadow-indigo-100"
                            data-product-card
                            data-id="<?= h($barang['id']) ?>"
                            data-name="<?= h($barang['nama_barang']) ?>"
                            data-category="<?= h($barang['kategori']) ?>"
                            data-price="<?= h($barang['harga']) ?>"
                            data-stock="<?= h($barang['stok']) ?>"
                            data-gudang="<?= h($barang['nama_gudang']) ?>"
                            onclick="addToCart(this)">
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= h($barang['kategori']) ?></span>
                        <div class="mt-3 font-bold text-slate-900 transition group-hover:text-indigo-600"><?= h($barang['nama_barang']) ?></div>
                        <div class="mt-2 font-extrabold text-indigo-600">Rp <?= number_format((float)$barang['harga'], 0, ',', '.') ?></div>
                        <div class="mt-1 text-xs text-slate-500">Gudang: <?= h($barang['nama_gudang']) ?></div>
                        <div class="mt-1 text-xs text-slate-500">Sisa: <span data-stock-badge><?= h($barang['stok']) ?></span></div>
                    </button>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Belum ada data barang.</div>
            <?php endif; ?>
        </div>
    </section>

    <aside class="lg:sticky lg:top-24 lg:self-start">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-900/5">
            <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
                <h3 class="text-lg font-extrabold tracking-tight text-slate-900">Keranjang</h3>
                <p class="mt-1 text-sm text-slate-500">Atur quantity dengan tombol minus dan plus.</p>
            </div>
            <div class="space-y-4 p-5">
                <div id="cartItems" class="space-y-3 rounded-2xl bg-slate-50 p-3"></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xl font-extrabold tracking-tight text-slate-900">
                        <span>Total</span>
                        <span id="totalPrice">Rp 0</span>
                    </div>
                    <input type="number" id="cashAmount" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100" placeholder="Uang Bayar">
                    <button id="btnPay" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 hover:shadow-indigo-500/30 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-50" disabled onclick="processPayment()">
                        BAYAR SEKARANG
                    </button>
                </div>
            </div>
        </div>
    </aside>
</main>

<!-- ============================================================ -->
<!-- Modal Sukses Bayar + Cetak Nota                            -->
<!-- ============================================================ -->
<div id="modalSukses" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4" onclick="closeSuksesModal()">
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl text-center" onclick="event.stopPropagation()">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-6 py-8 text-white">
            <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-4xl">✅</div>
            <h2 class="text-2xl font-extrabold">Pembayaran Berhasil!</h2>
        </div>
        <div class="space-y-3 p-6">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-left">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">No. Transaksi</span>
                    <span id="sukses_id_trx" class="font-bold text-slate-900">#-</span>
                </div>
                <div class="mt-2 flex justify-between text-sm">
                    <span class="text-slate-500">Total</span>
                    <span id="sukses_total" class="font-bold text-slate-900">-</span>
                </div>
                <div class="mt-2 flex justify-between text-sm">
                    <span class="text-slate-500">Dibayar</span>
                    <span id="sukses_bayar" class="font-bold text-slate-900">-</span>
                </div>
                <div class="mt-2 flex justify-between text-sm border-t border-slate-200 pt-2">
                    <span class="font-semibold text-slate-700">Kembalian</span>
                    <span id="sukses_kembalian" class="text-xl font-extrabold text-emerald-600">-</span>
                </div>
            </div>
            <a id="btnCetakNota" href="#" target="_blank"
               class="flex items-center justify-center gap-2 w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700">
                🖨️ Cetak Nota PDF
            </a>
            <button onclick="closeSuksesModal()" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>