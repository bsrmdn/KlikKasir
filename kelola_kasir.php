<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_role('admin'); // Admin only

$aksi     = isset($_GET['aksi']) ? (string) $_GET['aksi'] : '';
$editUser = null;

if ($aksi === 'edit' && isset($_GET['id'])) {
    $idEdit = (int) $_GET['id'];
    $stmt = $koneksi->prepare('SELECT id_user, nama, username, role FROM users WHERE id_user = ? AND role = "kasir" LIMIT 1');
    $stmt->bind_param('i', $idEdit);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Ambil daftar kasir
$kasirList = $koneksi->query('SELECT id_user, nama, username, role, created_at FROM users WHERE role = "kasir" ORDER BY created_at DESC');

$pageTitle  = 'Kelola Kasir — KlikKasir';
$bodyClass  = 'min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 text-slate-800';
$activePage = 'kelola_kasir';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$msg = '';
if (isset($_GET['msg'])) {
    $msgs = [
        'tambah_ok' => ['type' => 'success', 'text' => 'Kasir berhasil ditambahkan.'],
        'edit_ok'   => ['type' => 'success', 'text' => 'Data kasir berhasil diperbarui.'],
        'hapus_ok'  => ['type' => 'success', 'text' => 'Kasir berhasil dihapus.'],
        'error'     => ['type' => 'error',   'text' => 'Terjadi kesalahan. Silakan coba lagi.'],
        'duplikat'  => ['type' => 'error',   'text' => 'Username sudah digunakan. Pilih username lain.'],
    ];
    $msg = $msgs[$_GET['msg']] ?? null;
}
?>
<main class="mx-auto max-w-5xl space-y-6 px-4 py-8">

    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Kelola Kasir</h1>
        <p class="text-sm text-slate-500">Tambah, edit, atau hapus akun kasir. Hanya dapat diakses oleh admin.</p>
    </div>

    <?php if ($msg): ?>
        <div class="rounded-xl border px-4 py-3 text-sm <?= $msg['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
            <?= h($msg['text']) ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-5">

        <!-- Form Tambah / Edit -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-5 text-lg font-extrabold tracking-tight text-slate-900">
                    <?= $editUser ? '✏️ Edit Kasir' : '➕ Tambah Kasir Baru' ?>
                </h2>
                <form method="POST" action="process/kasir_action.php" class="space-y-4">
                    <input type="hidden" name="action" value="<?= $editUser ? 'edit' : 'tambah' ?>">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="id_user" value="<?= h($editUser['id_user']) ?>">
                    <?php endif; ?>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Lengkap</label>
                        <input type="text" name="nama" id="inputNama" required
                               value="<?= h($editUser['nama'] ?? '') ?>"
                               placeholder="Budi Santoso"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Username</label>
                        <input type="text" name="username" id="inputUsername" required
                               value="<?= h($editUser['username'] ?? '') ?>"
                               placeholder="kasir2"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Password <?= $editUser ? '<span class="font-normal text-slate-400">(kosongkan jika tidak diubah)</span>' : '' ?>
                        </label>
                        <input type="password" name="password" id="inputPassword"
                               <?= $editUser ? '' : 'required' ?>
                               placeholder="••••••••"
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 rounded-xl bg-indigo-600 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <?= $editUser ? 'Simpan Perubahan' : 'Tambah Kasir' ?>
                        </button>
                        <?php if ($editUser): ?>
                            <a href="kelola_kasir.php" class="flex-1 rounded-xl border border-slate-300 bg-white py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Kasir -->
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                    <h2 class="font-extrabold text-slate-900">Daftar Kasir</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold">Username</th>
                                <th class="px-4 py-3 text-left font-semibold">Dibuat</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($kasirList && $kasirList->num_rows > 0): ?>
                                <?php while ($k = $kasirList->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 font-semibold text-slate-900"><?= h($k['nama']) ?></td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                <?= h($k['username']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500"><?= h(date('d M Y', strtotime($k['created_at']))) ?></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex gap-2">
                                                <a href="kelola_kasir.php?aksi=edit&id=<?= (int)$k['id_user'] ?>"
                                                   class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                                                    Edit
                                                </a>
                                                <button type="button"
                                                        onclick="confirmHapus(<?= (int)$k['id_user'] ?>, '<?= h($k['nama']) ?>')"
                                                        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">
                                        Belum ada kasir terdaftar. Tambahkan kasir baru di sebelah kiri.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Konfirmasi Hapus (Form tersembunyi) -->
<form id="formHapus" method="POST" action="process/kasir_action.php" class="hidden">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id_user" id="hapusIdUser" value="">
</form>

<script>
    lucide.createIcons();

    function confirmHapus(id, nama) {
        if (confirm(`Hapus kasir "${nama}"? Tindakan ini tidak dapat dibatalkan.`)) {
            document.getElementById('hapusIdUser').value = id;
            document.getElementById('formHapus').submit();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
