<?php

$activePage = $activePage ?? '';
$kasirClass = $activePage === 'kasir'
    ? 'rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700'
    : 'rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700';
$gudangClass = $activePage === 'gudang'
    ? 'rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700'
    : 'rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700';
$transaksiClass = $activePage === 'transaksi'
    ? 'rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700'
    : 'rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700';
?>
<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
        <div class="flex items-center gap-3 text-xl font-extrabold tracking-tight text-slate-900">
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                <?= $activePage === 'gudang' ? '📦' : '🛒' ?>
            </span>
            <?= $activePage === 'gudang' ? 'Gudang Stok' : ($activePage === 'transaksi' ? 'Riwayat Transaksi' : 'Kasir Toko') ?>
        </div>
        <nav class="flex items-center gap-2">
            <a href="kasir.php" class="<?= $kasirClass ?>">Kasir</a>
            <a href="gudang.php" class="<?= $gudangClass ?>">Gudang</a>
            <a href="transaksi.php" class="<?= $transaksiClass ?>">Transaksi</a>
            <button onclick="logout()" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">Keluar</button>
        </nav>
    </div>
</header>