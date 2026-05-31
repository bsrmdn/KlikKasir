<?php
require_once __DIR__ . '/config/session.php';
require_login('index.php');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

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

$pageTitle = 'Gudang - Stok';
$bodyClass = 'min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 text-slate-800';
$activePage = 'gudang';
$pageScripts = ['assets/js/app.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="mx-auto max-w-7xl space-y-6 px-4 py-6">
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Barang</div>
            <div id="statSKU" class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600"><?= h($stats['total_barang'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Unit</div>
            <div id="statUnits" class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600"><?= h($stats['total_unit'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Nilai Aset</div>
            <div id="statValue" class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600">Rp <?= number_format((float) ($stats['nilai_aset'] ?? 0), 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama Barang</th>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 font-semibold">Gudang</th>
                        <th class="px-4 py-3 text-right font-semibold">Harga</th>
                        <th class="px-4 py-3 text-center font-semibold">Stok</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($barangResult && $barangResult->num_rows > 0): ?>
                        <?php while ($barang = $barangResult->fetch_assoc()): ?>
                            <?php $stockBadgeClass = ((int) $barang['stok'] <= 10) ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'; ?>
                            <tr data-barang-row="<?= h($barang['id']) ?>" class="hover:bg-slate-50/80">
                                <td class="px-4 py-4 font-semibold text-slate-900"><?= h($barang['nama_barang']) ?></td>
                                <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= h($barang['kategori']) ?></span></td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    <div class="font-medium text-slate-900"><?= h($barang['nama_gudang']) ?></div>
                                    <div class="text-slate-500"><?= h($barang['lokasi']) ?></div>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-700">Rp <?= number_format((float) $barang['harga'], 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-center">
                                    <span data-stock-badge class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $stockBadgeClass ?>"><?= h($barang['stok']) ?> pcs</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <button type="button" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700" onclick="addStock(<?= (int) $barang['id'] ?>)">+ Stok</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data barang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>