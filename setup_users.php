<?php
/**
 * Setup Users — Jalankan SEKALI untuk membuat akun admin & kasir.
 * Setelah selesai, HAPUS file ini dari server.
 *
 * Akses: http://localhost/Terakhir/KlikKasir/setup_users.php
 */
require_once __DIR__ . '/config/database.php';

$users = [
    ['nama' => 'Administrator', 'username' => 'admin',  'password' => 'admin123', 'role' => 'admin'],
    ['nama' => 'Kasir Utama',   'username' => 'kasir1', 'password' => 'kasir123', 'role' => 'kasir'],
];

$results = [];
foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = $koneksi->prepare(
        "INSERT INTO users (nama, username, password, role)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           nama     = VALUES(nama),
           password = VALUES(password),
           role     = VALUES(role)"
    );
    $stmt->bind_param('ssss', $u['nama'], $u['username'], $hash, $u['role']);
    $ok = $stmt->execute();
    $results[] = [
        'username' => $u['username'],
        'password' => $u['password'],
        'hash'     => $hash,
        'status'   => $ok ? '✅ Berhasil' : '❌ Gagal: ' . $stmt->error,
    ];
    $stmt->close();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Setup Users — KlikKasir</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
  <div class="w-full max-w-xl rounded-2xl bg-white p-8 shadow-lg">
    <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Setup Users KlikKasir</h1>
    <p class="text-sm text-slate-500 mb-6">Akun berikut telah dibuat / diperbarui di database.</p>

    <div class="space-y-4">
      <?php foreach ($results as $r): ?>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-slate-900"><?= htmlspecialchars($r['username']) ?></span>
            <span class="text-sm <?= str_starts_with($r['status'], '✅') ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $r['status'] ?></span>
          </div>
          <div class="text-sm text-slate-600">Password: <code class="bg-white px-2 py-0.5 rounded border"><?= htmlspecialchars($r['password']) ?></code></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
      ⚠️ <strong>Penting:</strong> Hapus file <code>setup_users.php</code> setelah setup selesai untuk keamanan.
    </div>

    <div class="mt-6 flex gap-3">
      <a href="index.php" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
        Ke Halaman Login →
      </a>
    </div>
  </div>
</body>
</html>
