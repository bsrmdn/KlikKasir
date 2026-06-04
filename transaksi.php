<?php
require_once __DIR__ . '/config/session.php';
require_login('index.php');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Filter tanggal
$tglDari   = isset($_GET['tgl_dari']) && $_GET['tgl_dari'] !== '' ? (string) $_GET['tgl_dari'] : '';
$tglSampai = isset($_GET['tgl_sampai']) && $_GET['tgl_sampai'] !== '' ? (string) $_GET['tgl_sampai'] : '';

// Query summary (disesuaikan filter)
if ($tglDari !== '' && $tglSampai !== '') {
    $stmtSum = $koneksi->prepare(
        "SELECT COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total_harga),0) AS total_pendapatan
         FROM transaksi WHERE DATE(tgl_transaksi) BETWEEN ? AND ?"
    );
    $stmtSum->bind_param('ss', $tglDari, $tglSampai);
    $stmtSum->execute();
    $summaryData = $stmtSum->get_result()->fetch_assoc();
    $stmtSum->close();

    $stmtTrx = $koneksi->prepare(
        "SELECT id_transaksi, tgl_transaksi, total_harga, uang_bayar
         FROM transaksi WHERE DATE(tgl_transaksi) BETWEEN ? AND ?
         ORDER BY tgl_transaksi DESC, id_transaksi DESC"
    );
    $stmtTrx->bind_param('ss', $tglDari, $tglSampai);
    $stmtTrx->execute();
    $transaksiResult = $stmtTrx->get_result();
    $stmtTrx->close();
} else {
    $summaryData = $koneksi->query('SELECT COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total_harga),0) AS total_pendapatan FROM transaksi')->fetch_assoc();
    $transaksiResult = $koneksi->query('SELECT id_transaksi, tgl_transaksi, total_harga, uang_bayar FROM transaksi ORDER BY tgl_transaksi DESC, id_transaksi DESC');
}

$summary = ['jumlah_transaksi' => 0, 'total_pendapatan' => 0];
if ($summaryData) $summary = $summaryData;

$pageTitle   = 'Riwayat Transaksi — KlikKasir';
$bodyClass   = 'min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 text-slate-800';
$activePage  = 'transaksi';
$pageScripts = ['assets/js/app.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="mx-auto max-w-7xl space-y-6 px-4 py-6">

    <section class="space-y-2">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Riwayat Penjualan</h1>
        <p class="text-sm text-slate-500">Daftar transaksi penjualan dan detail item yang dibeli.</p>
    </section>

    <!-- Kartu Ringkasan -->
    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Pendapatan <?= ($tglDari && $tglSampai) ? h("($tglDari s/d $tglSampai)") : '' ?></div>
            <div class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600">Rp <?= number_format((float)($summary['total_pendapatan'] ?? 0), 0, ',', '.') ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Jumlah Transaksi</div>
            <div class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600"><?= h($summary['jumlah_transaksi'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Filter Tanggal -->
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Dari Tanggal</label>
                <input type="date" name="tgl_dari" value="<?= h($tglDari) ?>"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai Tanggal</label>
                <input type="date" name="tgl_sampai" value="<?= h($tglSampai) ?>"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            </div>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Cari
            </button>
            <?php if ($tglDari || $tglSampai): ?>
                <a href="transaksi.php" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Transaksi -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">ID Transaksi</th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-right font-semibold">Total Harga</th>
                        <th class="px-4 py-3 text-right font-semibold">Uang Bayar</th>
                        <th class="px-4 py-3 text-right font-semibold">Kembalian</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($transaksiResult && $transaksiResult->num_rows > 0): ?>
                        <?php while ($transaksi = $transaksiResult->fetch_assoc()): ?>
                            <?php $kembalian = (float)$transaksi['uang_bayar'] - (float)$transaksi['total_harga']; ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-4 font-semibold text-indigo-600">#<?= h($transaksi['id_transaksi']) ?></td>
                                <td class="px-4 py-4 text-sm text-slate-700"><?= h(date('d M Y, H:i', strtotime($transaksi['tgl_transaksi']))) ?></td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-700">Rp <?= number_format((float)$transaksi['total_harga'], 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-700">Rp <?= number_format((float)$transaksi['uang_bayar'], 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-right font-semibold text-emerald-600">Rp <?= number_format($kembalian, 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button"
                                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700"
                                                onclick="showDetail(<?= (int)$transaksi['id_transaksi'] ?>)">
                                            Detail
                                        </button>
                                        <a href="process/cetak_nota.php?id=<?= (int)$transaksi['id_transaksi'] ?>" target="_blank"
                                           class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                                            🖨️ Nota
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                <?= ($tglDari || $tglSampai) ? 'Tidak ada transaksi pada rentang tanggal tersebut.' : 'Belum ada riwayat transaksi.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Detail Transaksi -->
<div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4 py-6">
    <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Detail Transaksi</p>
                <h3 id="modalTransactionLabel" class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">#-</h3>
            </div>
            <button type="button" onclick="closeModal()" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="space-y-6 p-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Tanggal</div>
                    <div id="modalTransactionDate" class="mt-1 font-semibold text-slate-900">-</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Total Harga</div>
                    <div id="modalTransactionTotal" class="mt-1 font-semibold text-slate-900">-</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Bayar / Kembalian</div>
                    <div id="modalTransactionPay" class="mt-1 font-semibold text-slate-900">-</div>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nama Barang</th>
                                <th class="px-4 py-3 text-center font-semibold">Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Harga Satuan</th>
                                <th class="px-4 py-3 text-right font-semibold">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detailItems" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-2">
                <a id="modalBtnCetak" href="#" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    🖨️ Cetak Nota
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Extend showDetail untuk update link cetak nota di modal
const _origShowDetail = showDetail;
window.showDetail = function(idTransaksi) {
    _origShowDetail(idTransaksi);
    const cetakBtn = document.getElementById('modalBtnCetak');
    if (cetakBtn) cetakBtn.href = `process/cetak_nota.php?id=${idTransaksi}`;
};
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>