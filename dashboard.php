<?php
/**
 * dashboard.php — Halaman Dashboard Utama KlikKasir
 *
 * Menampilkan ringkasan statistik toko dan 5 transaksi terbaru.
 * Dapat diakses oleh semua role (admin dan kasir), namun quick actions
 * yang ditampilkan berbeda berdasarkan role.
 *
 * Data yang ditampilkan:
 *   - Jumlah jenis barang & total unit stok
 *   - Jumlah transaksi & total pendapatan kotor
 *   - 5 transaksi terbaru (ID, tanggal, total)
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Wajib login; redirect ke index.php jika belum
require_login('index.php');

// ── Ambil statistik dashboard ───────────────────────────────────────────────────────
$statBarang     = 0;  // Jumlah jenis barang (SKU)
$statStok       = 0;  // Total unit stok semua barang
$statTrx        = 0;  // Jumlah transaksi yang pernah terjadi
$statPendapatan = 0;  // Total pendapatan kotor (sum total_harga)

// Query 1: Hitung jumlah barang dan total unit stok
$r1 = $koneksi->query('SELECT COUNT(*) AS c, COALESCE(SUM(stok),0) AS s FROM barang');
if ($r1 && $d = $r1->fetch_assoc()) {
    $statBarang = (int) $d['c'];
    $statStok   = (int) $d['s'];
}

// Query 2: Hitung jumlah transaksi dan total pendapatan
$r2 = $koneksi->query('SELECT COUNT(*) AS c, COALESCE(SUM(total_harga),0) AS p FROM transaksi');
if ($r2 && $d = $r2->fetch_assoc()) {
    $statTrx        = (int) $d['c'];
    $statPendapatan = (float) $d['p'];
}

// Query 3: Ambil 5 transaksi terbaru untuk ditampilkan di tabel
$recentTrx = $koneksi->query(
    'SELECT id_transaksi, tgl_transaksi, total_harga, uang_bayar FROM transaksi ORDER BY tgl_transaksi DESC LIMIT 5'
);

// Konfigurasi halaman (digunakan oleh header.php dan navbar.php)
$pageTitle  = 'Dashboard — KlikKasir';
$bodyClass  = 'min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 text-slate-800';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Cek apakah ada pesan akses ditolak (dari require_role redirect)
$aksesMsg = isset($_GET['akses']) && $_GET['akses'] === 'ditolak';
?>
<main class="mx-auto max-w-7xl space-y-8 px-4 py-8">

  <?php if ($aksesMsg): ?>
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700 flex items-center gap-2">
      <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.293 4.293a1 1 0 011.414 0L21 13.586V20a1 1 0 01-1 1H4a1 1 0 01-1-1v-6.414L10.293 4.293z"/></svg>
      Anda tidak memiliki hak akses ke halaman tersebut.
    </div>
  <?php endif; ?>

  <!-- Header Sambutan -->
  <section class="flex flex-col gap-1">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
      Selamat datang, <span class="text-indigo-600"><?= h(user_nama()) ?></span> 👋
    </h1>
    <p class="text-sm text-slate-500">
      <?= is_admin() ? 'Panel Admin — kelola toko, kasir, dan stok barang.' : 'Panel Kasir — mulai transaksi atau lihat riwayat penjualan.' ?>
    </p>
  </section>

  <!-- Kartu Statistik -->
  <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">

    <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
          <i data-lucide="package" class="h-6 w-6"></i>
        </div>
        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600">Barang</span>
      </div>
      <div class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900"><?= h($statBarang) ?></div>
      <div class="mt-1 text-sm text-slate-500">Jenis barang tersedia</div>
      <div class="absolute inset-x-0 bottom-0 h-1 rounded-b-2xl bg-indigo-500 scale-x-0 transition-transform group-hover:scale-x-100 origin-left"></div>
    </div>

    <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
          <i data-lucide="boxes" class="h-6 w-6"></i>
        </div>
        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600">Stok</span>
      </div>
      <div class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900"><?= number_format($statStok, 0, ',', '.') ?></div>
      <div class="mt-1 text-sm text-slate-500">Total unit di gudang</div>
      <div class="absolute inset-x-0 bottom-0 h-1 rounded-b-2xl bg-emerald-500 scale-x-0 transition-transform group-hover:scale-x-100 origin-left"></div>
    </div>

    <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">
          <i data-lucide="receipt" class="h-6 w-6"></i>
        </div>
        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-600">Transaksi</span>
      </div>
      <div class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900"><?= h($statTrx) ?></div>
      <div class="mt-1 text-sm text-slate-500">Total transaksi selesai</div>
      <div class="absolute inset-x-0 bottom-0 h-1 rounded-b-2xl bg-violet-500 scale-x-0 transition-transform group-hover:scale-x-100 origin-left"></div>
    </div>

    <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
          <i data-lucide="trending-up" class="h-6 w-6"></i>
        </div>
        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-600">Pendapatan</span>
      </div>
      <div class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">Rp <?= number_format($statPendapatan, 0, ',', '.') ?></div>
      <div class="mt-1 text-sm text-slate-500">Total pendapatan kotor</div>
      <div class="absolute inset-x-0 bottom-0 h-1 rounded-b-2xl bg-amber-500 scale-x-0 transition-transform group-hover:scale-x-100 origin-left"></div>
    </div>

  </div>

  <!-- Quick Actions & Transaksi Terbaru -->
  <div class="grid gap-6 lg:grid-cols-5">

    <!-- Quick Actions -->
    <div class="lg:col-span-2 space-y-4">
      <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Aksi Cepat</h2>
      <div class="grid gap-3">
        <a href="kasir.php" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md group">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-400/30">
            <i data-lucide="shopping-cart" class="h-5 w-5"></i>
          </div>
          <div>
            <div class="font-semibold text-slate-900 group-hover:text-indigo-700">Buat Transaksi</div>
            <div class="text-xs text-slate-500">Mulai transaksi penjualan baru</div>
          </div>
          <i data-lucide="chevron-right" class="ml-auto h-4 w-4 text-slate-400 group-hover:text-indigo-500"></i>
        </a>

        <a href="transaksi.php" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-md group">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow-md shadow-violet-400/30">
            <i data-lucide="history" class="h-5 w-5"></i>
          </div>
          <div>
            <div class="font-semibold text-slate-900 group-hover:text-violet-700">Riwayat Transaksi</div>
            <div class="text-xs text-slate-500">Lihat & cari transaksi lalu</div>
          </div>
          <i data-lucide="chevron-right" class="ml-auto h-4 w-4 text-slate-400 group-hover:text-violet-500"></i>
        </a>

        <?php if (is_admin()): ?>
        <a href="gudang.php" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md group">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-400/30">
            <i data-lucide="warehouse" class="h-5 w-5"></i>
          </div>
          <div>
            <div class="font-semibold text-slate-900 group-hover:text-emerald-700">Kelola Gudang</div>
            <div class="text-xs text-slate-500">Barang & stok gudang</div>
          </div>
          <i data-lucide="chevron-right" class="ml-auto h-4 w-4 text-slate-400 group-hover:text-emerald-500"></i>
        </a>

        <a href="kelola_kasir.php" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-md group">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-md shadow-rose-400/30">
            <i data-lucide="users" class="h-5 w-5"></i>
          </div>
          <div>
            <div class="font-semibold text-slate-900 group-hover:text-rose-700">Kelola Kasir</div>
            <div class="text-xs text-slate-500">Tambah & kelola akun kasir</div>
          </div>
          <i data-lucide="chevron-right" class="ml-auto h-4 w-4 text-slate-400 group-hover:text-rose-500"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="lg:col-span-3 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Transaksi Terbaru</h2>
        <a href="transaksi.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Lihat Semua →</a>
      </div>
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">ID</th>
              <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
              <th class="px-4 py-3 text-right font-semibold">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if ($recentTrx && $recentTrx->num_rows > 0): ?>
              <?php while ($t = $recentTrx->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50/80">
                  <td class="px-4 py-3 font-semibold text-indigo-600">#<?= h($t['id_transaksi']) ?></td>
                  <td class="px-4 py-3 text-slate-700"><?= h(date('d M Y, H:i', strtotime($t['tgl_transaksi']))) ?></td>
                  <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp <?= number_format((float)$t['total_harga'], 0, ',', '.') ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada transaksi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<script>lucide.createIcons();</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
