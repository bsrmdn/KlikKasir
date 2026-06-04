<?php
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

$error = isset($_GET['error']) ? (string) $_GET['error'] : '';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — KlikKasir</title>
    <meta name="description" content="Halaman login sistem kasir toko KlikKasir. Masukkan username dan password untuk mengakses sistem." />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 px-4 text-slate-800 flex items-center justify-center">

    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white/90 p-8 shadow-xl shadow-slate-200/70 backdrop-blur">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 text-2xl">
                🛒
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">KlikKasir</h1>
            <p class="mt-2 text-sm text-slate-500">Silakan masuk ke akun Anda</p>
        </div>

        <?php if ($error === '1'): ?>
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                Username atau password salah.
            </div>
        <?php endif; ?>

        <form id="loginForm" action="process/login_process.php" method="POST" class="space-y-5">
            <div>
                <label for="username" class="mb-2 block text-sm font-semibold uppercase tracking-wide text-slate-500">USERNAME</label>
                <div class="relative">
                    <i data-lucide="user" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="username" name="username" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100" placeholder="admin" required autocomplete="username" />
                </div>
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold uppercase tracking-wide text-slate-500">PASSWORD</label>
                <div class="relative">
                    <i data-lucide="lock" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" id="password" name="password" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100" placeholder="••••••••" required autocomplete="current-password" />
                </div>
            </div>

            <button type="submit" id="btnLogin" class="mt-1 inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 hover:shadow-indigo-500/30 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                MASUK SEKARANG
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500">
            Belum punya akses?
            <button type="button" id="btnHubungiAdmin" onclick="openKontakModal()" class="font-semibold text-indigo-600 no-underline transition hover:text-indigo-700 cursor-pointer bg-transparent border-0 p-0">
                Hubungi Admin
            </button>
        </div>
    </div>

    <!-- Modal Hubungi Admin -->
    <div id="kontakModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4" onclick="closeKontakModal()">
        <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium opacity-80">Butuh Bantuan?</p>
                        <h2 class="text-xl font-extrabold tracking-tight">Hubungi Admin</h2>
                    </div>
                    <button onclick="closeKontakModal()" class="rounded-xl p-2 hover:bg-white/20 transition" aria-label="Tutup">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4 p-6">
                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 text-xl">👤</div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide font-medium">Administrator</div>
                        <div class="font-semibold text-slate-900">Admin KlikKasir</div>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-600">
                        <i data-lucide="phone" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide font-medium">WhatsApp</div>
                        <div class="font-semibold text-slate-900">+62 812-3456-7890</div>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                        <i data-lucide="mail" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide font-medium">Email</div>
                        <div class="font-semibold text-slate-900">admin@klikkasir.id</div>
                    </div>
                </div>

                <a href="https://wa.me/6281234567890?text=Halo%20Admin%2C%20saya%20membutuhkan%20akses%20ke%20sistem%20KlikKasir." target="_blank" rel="noopener noreferrer"
                   class="flex items-center justify-center gap-2 w-full rounded-xl bg-green-500 px-4 py-3 font-semibold text-white shadow transition hover:bg-green-600">
                    <i data-lucide="message-circle" class="h-5 w-5"></i>
                    Buka WhatsApp
                </a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openKontakModal() {
            const modal = document.getElementById('kontakModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeKontakModal() {
            const modal = document.getElementById('kontakModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeKontakModal();
        });
    </script>
</body>
</html>