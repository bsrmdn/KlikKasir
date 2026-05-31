<?php
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: kasir.php');
    exit;
}

$error = isset($_GET['error']) ? (string) $_GET['error'] : '';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — Kasir Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 px-4 text-slate-800 flex items-center justify-center">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white/90 p-8 shadow-xl shadow-slate-200/70 backdrop-blur">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/30">
                <i data-lucide="shopping-bag"></i>
            </div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Selamat Datang</h2>
            <p class="mt-2 text-sm text-slate-500">
                Silakan masuk ke akun kasir Anda
            </p>
        </div>

        <?php
        require_once __DIR__ . '/config/session.php';
        require_once __DIR__ . '/includes/helpers.php';

        if (!empty($_SESSION['username'])) {
            header('Location: kasir.php');
            exit;
        }

        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $pageTitle = 'Login — Kasir Toko';
        $bodyClass = 'flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 px-4 text-slate-800';
        require_once __DIR__ . '/includes/header.php';
        ?>
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white/90 p-8 shadow-xl shadow-slate-200/70 backdrop-blur">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/30">
                    <i data-lucide="shopping-bag"></i>
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Selamat Datang</h2>
                <p class="mt-2 text-sm text-slate-500">Silakan masuk ke akun kasir Anda</p>
            </div>

            <?php if ($error === '1'): ?>
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Username atau password salah.</div>
            <?php endif; ?>

            <form id="loginForm" action="process/login_process.php" method="POST" class="space-y-5">
                <div>
                    <label for="username" class="mb-2 block text-sm font-semibold uppercase tracking-wide text-slate-500">USERNAME</label>
                    <div class="relative">
                        <i data-lucide="user" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="username" name="username" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100" placeholder="admin" required />
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold uppercase tracking-wide text-slate-500">PASSWORD</label>
                    <div class="relative">
                        <i data-lucide="lock" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="password" name="password" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100" placeholder="••••••••" required />
                    </div>
                </div>

                <button type="submit" class="mt-1 inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 hover:shadow-indigo-500/30 focus:outline-none focus:ring-4 focus:ring-indigo-200">MASUK SEKARANG</button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-500">
                Belum punya akses?
                <a href="#" class="font-semibold text-indigo-600 no-underline transition hover:text-indigo-700">Hubungi Admin</a>
            </div>
        </div>

        <script>
            lucide.createIcons();
        </script>
        <?php require_once __DIR__ . '/includes/footer.php'; ?>