<?php
/**
 * includes/navbar.php
 *
 * Navigasi atas (header sticky) yang muncul di semua halaman setelah login.
 * Navbar bersifat "role-aware": menu Gudang dan Kelola Kasir hanya ditampilkan
 * untuk user dengan role 'admin'.
 *
 * Variabel yang dibaca:
 *   $activePage  (string) — nama halaman aktif untuk menentukan menu yang di-highlight
 *                           Nilai valid: 'dashboard', 'kasir', 'gudang', 'transaksi', 'kelola_kasir'
 *
 * Variabel yang dibaca dari $_SESSION:
 *   $_SESSION['role']      — role user ('admin' atau 'kasir')
 *   $_SESSION['nama']      — nama lengkap user
 *   $_SESSION['username']  — username user (fallback jika nama tidak ada)
 */

// Gunakan string kosong jika $activePage belum diset
$activePage = $activePage ?? '';

/**
 * Menghasilkan class CSS untuk item navigasi berdasarkan apakah item tersebut aktif.
 *
 * @param  string $page        Nama halaman item navigasi ini
 * @param  string $activePage  Nama halaman yang sedang aktif
 * @return string              String class Tailwind CSS
 */
function navClass(string $page, string $activePage): string
{
    return $activePage === $page
        // Class untuk item yang sedang aktif (indigo solid)
        ? 'rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700'
        // Class untuk item yang tidak aktif (abu-abu transparan)
        : 'rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700';
}

// Ambil role dan nama dari sesi untuk ditampilkan di navbar
$role      = $_SESSION['role'] ?? 'kasir';
$namaUser  = $_SESSION['nama'] ?? ($_SESSION['username'] ?? '');

// Mapping halaman ke emoji ikon yang tampil di area brand (kiri navbar)
$brandIcons = [
    'dashboard'    => '🏠',
    'kasir'        => '🛒',
    'gudang'       => '📦',
    'transaksi'    => '📋',
    'kelola_kasir' => '👥',
];

// Mapping halaman ke label teks yang tampil di area brand
$brandLabels = [
    'dashboard'    => 'Dashboard',
    'kasir'        => 'Kasir Toko',
    'gudang'       => 'Gudang Stok',
    'transaksi'    => 'Riwayat Transaksi',
    'kelola_kasir' => 'Kelola Kasir',
];

// Tentukan ikon dan label brand berdasarkan halaman aktif (fallback ke default)
$brandIcon  = $brandIcons[$activePage]  ?? '🛒';
$brandLabel = $brandLabels[$activePage] ?? 'KlikKasir';
?>
<!-- Navbar sticky di bagian atas layar dengan efek backdrop blur -->
<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">

        <!-- Area Brand: ikon + nama halaman, link ke dashboard -->
        <a href="dashboard.php" class="flex items-center gap-3 text-xl font-extrabold tracking-tight text-slate-900 hover:opacity-80 transition">
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 text-lg">
                <?= $brandIcon ?>
            </span>
            <span class="hidden sm:block"><?= htmlspecialchars($brandLabel) ?></span>
        </a>

        <!-- Navigasi utama: semua role melihat Dashboard, Kasir, Transaksi
             Admin juga melihat Gudang dan Kelola Kasir -->
        <nav class="flex items-center gap-1">
            <a href="dashboard.php" class="<?= navClass('dashboard', $activePage) ?>">Dashboard</a>
            <a href="kasir.php"     class="<?= navClass('kasir', $activePage) ?>">Kasir</a>
            <a href="transaksi.php" class="<?= navClass('transaksi', $activePage) ?>">Transaksi</a>
            <?php if ($role === 'admin'): ?>
            <!-- Menu eksklusif admin -->
            <a href="gudang.php"       class="<?= navClass('gudang', $activePage) ?>">Gudang</a>
            <a href="kelola_kasir.php" class="<?= navClass('kelola_kasir', $activePage) ?>">Kelola Kasir</a>
            <?php endif; ?>
        </nav>

        <!-- Area kanan: info user (nama + role) dan tombol logout -->
        <div class="flex items-center gap-2">
            <!-- Nama dan role user (hanya muncul di layar medium ke atas) -->
            <div class="hidden md:flex flex-col items-end leading-tight">
                <span class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($namaUser) ?></span>
                <!-- Warna label role: indigo untuk admin, emerald untuk kasir -->
                <span class="text-xs font-medium <?= $role === 'admin' ? 'text-indigo-500' : 'text-emerald-500' ?> uppercase tracking-wide"><?= htmlspecialchars($role) ?></span>
            </div>
            <!-- Tombol logout: POST ke process/logout.php untuk destroy sesi -->
            <form method="post" action="process/logout.php" class="inline">
                <button type="submit" title="Keluar" class="ml-2 flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </form>
        </div>

    </div>
</header>