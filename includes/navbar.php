<?php

$activePage = $activePage ?? '';

function navClass(string $page, string $activePage): string
{
    return $activePage === $page
        ? 'rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700'
        : 'rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700';
}

$role = $_SESSION['role'] ?? 'kasir';
$namaUser = $_SESSION['nama'] ?? ($_SESSION['username'] ?? '');

// Label & Icon untuk brand area berdasarkan halaman
$brandIcons = [
    'dashboard' => '🏠',
    'kasir'     => '🛒',
    'gudang'    => '📦',
    'transaksi' => '📋',
    'kelola_kasir' => '👥',
];
$brandLabels = [
    'dashboard'    => 'Dashboard',
    'kasir'        => 'Kasir Toko',
    'gudang'       => 'Gudang Stok',
    'transaksi'    => 'Riwayat Transaksi',
    'kelola_kasir' => 'Kelola Kasir',
];
$brandIcon  = $brandIcons[$activePage]  ?? '🛒';
$brandLabel = $brandLabels[$activePage] ?? 'KlikKasir';
?>
<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">

        <!-- Brand -->
        <a href="dashboard.php" class="flex items-center gap-3 text-xl font-extrabold tracking-tight text-slate-900 hover:opacity-80 transition">
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 text-lg">
                <?= $brandIcon ?>
            </span>
            <span class="hidden sm:block"><?= htmlspecialchars($brandLabel) ?></span>
        </a>

        <!-- Navigation -->
        <nav class="flex items-center gap-1">
            <a href="dashboard.php" class="<?= navClass('dashboard', $activePage) ?>">Dashboard</a>
            <a href="kasir.php"     class="<?= navClass('kasir', $activePage) ?>">Kasir</a>
            <a href="transaksi.php" class="<?= navClass('transaksi', $activePage) ?>">Transaksi</a>
            <?php if ($role === 'admin'): ?>
            <a href="gudang.php"       class="<?= navClass('gudang', $activePage) ?>">Gudang</a>
            <a href="kelola_kasir.php" class="<?= navClass('kelola_kasir', $activePage) ?>">Kelola Kasir</a>
            <?php endif; ?>
        </nav>

        <!-- User Info + Logout -->
        <div class="flex items-center gap-2">
            <div class="hidden md:flex flex-col items-end leading-tight">
                <span class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($namaUser) ?></span>
                <span class="text-xs font-medium <?= $role === 'admin' ? 'text-indigo-500' : 'text-emerald-500' ?> uppercase tracking-wide"><?= htmlspecialchars($role) ?></span>
            </div>
            <form method="post" action="process/logout.php" class="inline">
                <button type="submit" title="Keluar" class="ml-2 flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </form>
        </div>

    </div>
</header>