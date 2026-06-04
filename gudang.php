<?php
require_once __DIR__ . '/config/session.php';
require_login('index.php');
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

$isAdmin = is_admin();

// Edit data untuk modal (dari GET)
$editBarang = null;
$editGudang = null;
if ($isAdmin && isset($_GET['edit_barang'])) {
    $eid = (int) $_GET['edit_barang'];
    $s = $koneksi->prepare('SELECT * FROM barang WHERE id = ? LIMIT 1');
    $s->bind_param('i', $eid);
    $s->execute();
    $editBarang = $s->get_result()->fetch_assoc();
    $s->close();
}
if ($isAdmin && isset($_GET['edit_gudang'])) {
    $eid = (int) $_GET['edit_gudang'];
    $s = $koneksi->prepare('SELECT * FROM gudang WHERE id_gudang = ? LIMIT 1');
    $s->bind_param('i', $eid);
    $s->execute();
    $editGudang = $s->get_result()->fetch_assoc();
    $s->close();
}

// Statistik
$stats = ['total_barang' => 0, 'total_unit' => 0, 'nilai_aset' => 0];
$statResult = $koneksi->query('SELECT COUNT(*) AS total_barang, COALESCE(SUM(stok), 0) AS total_unit, COALESCE(SUM(stok * harga), 0) AS nilai_aset FROM barang');
if ($statResult && $sd = $statResult->fetch_assoc()) {
    $stats = $sd;
}

// Daftar barang
$barangResult = $koneksi->query(
    'SELECT b.id, b.nama_barang, b.kategori, b.harga, b.stok, g.id_gudang, g.nama_gudang, g.lokasi
     FROM barang b INNER JOIN gudang g ON g.id_gudang = b.id_gudang
     ORDER BY b.nama_barang ASC'
);

// Daftar gudang (untuk dropdown & tabel)
$gudangAll = [];
$gr = $koneksi->query('SELECT id_gudang, nama_gudang, lokasi FROM gudang ORDER BY nama_gudang ASC');
while ($g = $gr->fetch_assoc()) {
    $gudangAll[] = $g;
}

$pageTitle  = 'Gudang — KlikKasir';
$bodyClass  = 'min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 text-slate-800';
$activePage = 'gudang';
$pageScripts = ['assets/js/app.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Pesan feedback
$feedbackMsgs = [
    'tambah_barang_ok'  => ['type' => 'success', 'text' => 'Barang berhasil ditambahkan.'],
    'edit_barang_ok'    => ['type' => 'success', 'text' => 'Barang berhasil diperbarui.'],
    'hapus_barang_ok'   => ['type' => 'success', 'text' => 'Barang berhasil dihapus.'],
    'tambah_gudang_ok'  => ['type' => 'success', 'text' => 'Gudang berhasil ditambahkan.'],
    'edit_gudang_ok'    => ['type' => 'success', 'text' => 'Gudang berhasil diperbarui.'],
    'hapus_gudang_ok'   => ['type' => 'success', 'text' => 'Gudang berhasil dihapus.'],
    'gudang_ada_barang' => ['type' => 'error', 'text' => 'Gudang tidak dapat dihapus karena masih memiliki barang.'],
    'error_barang'      => ['type' => 'error', 'text' => 'Terjadi kesalahan pada data barang.'],
    'error_gudang'      => ['type' => 'error', 'text' => 'Terjadi kesalahan pada data gudang.'],
];
$feedback = isset($_GET['msg']) ? ($feedbackMsgs[$_GET['msg']] ?? null) : null;
?>

<main class="mx-auto max-w-7xl space-y-6 px-4 py-6">

    <?php if ($feedback): ?>
        <div class="rounded-xl border px-4 py-3 text-sm flex items-center gap-2 <?= $feedback['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
            <?= h($feedback['text']) ?>
        </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Jenis Barang</div>
            <div id="statSKU" class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600"><?= h($stats['total_barang'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Unit Stok</div>
            <div id="statUnits" class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600"><?= h($stats['total_unit'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Nilai Aset</div>
            <div id="statValue" class="mt-1 text-2xl font-extrabold tracking-tight text-indigo-600">Rp <?= number_format((float)($stats['nilai_aset'] ?? 0), 0, ',', '.') ?></div>
        </div>
    </div>

    <!-- ===================== TABEL BARANG ===================== -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-5 py-4">
            <h2 class="font-extrabold text-slate-900">Daftar Barang</h2>
            <?php if ($isAdmin): ?>
            <button type="button" onclick="openModalTambahBarang()"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah Barang
            </button>
            <?php endif; ?>
        </div>
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
                            <?php $stockBadgeClass = ((int)$barang['stok'] <= 10) ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'; ?>
                            <tr data-barang-row="<?= h($barang['id']) ?>" data-stock-value="<?= h($barang['stok']) ?>" data-price-value="<?= h($barang['harga']) ?>" class="hover:bg-slate-50/80">
                                <td class="px-4 py-4 font-semibold text-slate-900"><?= h($barang['nama_barang']) ?></td>
                                <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= h($barang['kategori']) ?></span></td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    <div class="font-medium text-slate-900"><?= h($barang['nama_gudang']) ?></div>
                                    <div class="text-slate-500"><?= h($barang['lokasi']) ?></div>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-700">Rp <?= number_format((float)$barang['harga'], 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-center">
                                    <span data-stock-badge class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $stockBadgeClass ?>"><?= h($barang['stok']) ?> pcs</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button" data-open-stock data-id="<?= (int)$barang['id'] ?>" data-name="<?= h($barang['nama_barang']) ?>" data-stock="<?= (int)$barang['stok'] ?>"
                                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                            + Stok
                                        </button>
                                        <?php if ($isAdmin): ?>
                                        <button type="button"
                                                onclick="openModalEditBarang(<?= (int)$barang['id'] ?>, '<?= h($barang['nama_barang']) ?>', '<?= h($barang['kategori']) ?>', <?= (float)$barang['harga'] ?>, <?= (int)$barang['stok'] ?>, <?= (int)$barang['id_gudang'] ?>)"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                                            Edit
                                        </button>
                                        <button type="button"
                                                onclick="confirmHapusBarang(<?= (int)$barang['id'] ?>, '<?= h($barang['nama_barang']) ?>')"
                                                class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                                            Hapus
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data barang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================== TABEL GUDANG (Admin Only) ===================== -->
    <?php if ($isAdmin): ?>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-5 py-4">
            <h2 class="font-extrabold text-slate-900">Daftar Gudang</h2>
            <button type="button" onclick="openModalTambahGudang()"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah Gudang
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama Gudang</th>
                        <th class="px-4 py-3 font-semibold">Lokasi</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($gudangAll) > 0): ?>
                        <?php foreach ($gudangAll as $gd): ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-4 font-semibold text-slate-900"><?= h($gd['nama_gudang']) ?></td>
                                <td class="px-4 py-4 text-slate-600"><?= h($gd['lokasi']) ?></td>
                                <td class="px-4 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button"
                                                onclick="openModalEditGudang(<?= (int)$gd['id_gudang'] ?>, '<?= h($gd['nama_gudang']) ?>', '<?= h($gd['lokasi']) ?>')"
                                                class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                                            Edit
                                        </button>
                                        <button type="button"
                                                onclick="confirmHapusGudang(<?= (int)$gd['id_gudang'] ?>, '<?= h($gd['nama_gudang']) ?>')"
                                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data gudang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- ============================================================ -->
<!-- Modal: Update Stok Barang (untuk semua role)               -->
<!-- ============================================================ -->
<div id="stockModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6" onclick="closeStockModal()">
    <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                    <i data-lucide="package-plus" class="h-5 w-5"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Update Stok Barang</p>
                    <h3 id="stockModalTitle" class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Pilih Barang</h3>
                </div>
            </div>
            <button type="button" onclick="closeStockModal()" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="space-y-6 p-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Nama Barang</div>
                    <div id="stockModalName" class="mt-1 text-lg font-semibold text-slate-900">-</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Stok Saat Ini</div>
                    <div id="stockModalCurrentStock" class="mt-1 text-lg font-semibold text-slate-900">-</div>
                </div>
            </div>
            <div class="space-y-2">
                <label for="stockAmount" class="text-sm font-semibold text-slate-700">Jumlah</label>
                <input id="stockAmount" type="number" min="0" step="1"
                       class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                       placeholder="Masukkan jumlah">
            </div>
            <div class="grid gap-3 md:grid-cols-3">
                <button type="button" onclick="processStockAction('set')" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i data-lucide="refresh-cw" class="h-4 w-4"></i> Set Langsung
                </button>
                <button type="button" onclick="processStockAction('add')" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i data-lucide="plus" class="h-4 w-4"></i> Tambah (+)
                </button>
                <button type="button" onclick="processStockAction('subtract')" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                    <i data-lucide="minus" class="h-4 w-4"></i> Kurangi (-)
                </button>
            </div>
            <div class="flex justify-end border-t border-slate-200 pt-4">
                <button type="button" onclick="closeStockModal()" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                    Batal / Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- ============================================================ -->
<!-- Modal: Tambah / Edit Barang (Admin Only)                   -->
<!-- ============================================================ -->
<div id="modalBarang" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4" onclick="closeModalBarang()">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <h3 id="modalBarangTitle" class="text-xl font-extrabold text-slate-900">Tambah Barang</h3>
            <button onclick="closeModalBarang()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form method="POST" action="process/barang_action.php" class="space-y-4 p-6">
            <input type="hidden" name="action" id="barangAction" value="tambah">
            <input type="hidden" name="id" id="barangId" value="">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Barang</label>
                    <input type="text" name="nama_barang" id="barangNama" required placeholder="Nama produk"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori</label>
                    <input type="text" name="kategori" id="barangKategori" required placeholder="Makanan / Minuman"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Harga (Rp)</label>
                    <input type="number" name="harga" id="barangHarga" min="0" step="100" required placeholder="5000"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Stok Awal</label>
                    <input type="number" name="stok" id="barangStok" min="0" required placeholder="0"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Gudang</label>
                    <select name="id_gudang" id="barangGudang" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                        <?php foreach ($gudangAll as $gd): ?>
                            <option value="<?= (int)$gd['id_gudang'] ?>"><?= h($gd['nama_gudang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" onclick="closeModalBarang()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- Modal: Tambah / Edit Gudang (Admin Only)                   -->
<!-- ============================================================ -->
<div id="modalGudang" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4" onclick="closeModalGudang()">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <h3 id="modalGudangTitle" class="text-xl font-extrabold text-slate-900">Tambah Gudang</h3>
            <button onclick="closeModalGudang()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form method="POST" action="process/gudang_action.php" class="space-y-4 p-6">
            <input type="hidden" name="action" id="gudangAction" value="tambah">
            <input type="hidden" name="id_gudang" id="gudangId" value="">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Gudang</label>
                <input type="text" name="nama_gudang" id="gudangNama" required placeholder="Gudang Utama"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Lokasi</label>
                <input type="text" name="lokasi" id="gudangLokasi" required placeholder="Jl. Contoh No. 1"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" onclick="closeModalGudang()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus Barang -->
<form id="formHapusBarang" method="POST" action="process/barang_action.php" class="hidden">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id" id="hapusBarangId" value="">
</form>

<!-- Form Hapus Gudang -->
<form id="formHapusGudang" method="POST" action="process/gudang_action.php" class="hidden">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id_gudang" id="hapusGudangId" value="">
</form>

<script>
function openModalTambahBarang() {
    document.getElementById('modalBarangTitle').textContent = 'Tambah Barang Baru';
    document.getElementById('barangAction').value = 'tambah';
    document.getElementById('barangId').value = '';
    document.getElementById('barangNama').value = '';
    document.getElementById('barangKategori').value = '';
    document.getElementById('barangHarga').value = '';
    document.getElementById('barangStok').value = '';
    showModal('modalBarang');
}

function openModalEditBarang(id, nama, kategori, harga, stok, idGudang) {
    document.getElementById('modalBarangTitle').textContent = 'Edit Barang';
    document.getElementById('barangAction').value = 'edit';
    document.getElementById('barangId').value = id;
    document.getElementById('barangNama').value = nama;
    document.getElementById('barangKategori').value = kategori;
    document.getElementById('barangHarga').value = harga;
    document.getElementById('barangStok').value = stok;
    document.getElementById('barangGudang').value = idGudang;
    showModal('modalBarang');
}

function closeModalBarang() { hideModal('modalBarang'); }

function openModalTambahGudang() {
    document.getElementById('modalGudangTitle').textContent = 'Tambah Gudang Baru';
    document.getElementById('gudangAction').value = 'tambah';
    document.getElementById('gudangId').value = '';
    document.getElementById('gudangNama').value = '';
    document.getElementById('gudangLokasi').value = '';
    showModal('modalGudang');
}

function openModalEditGudang(id, nama, lokasi) {
    document.getElementById('modalGudangTitle').textContent = 'Edit Gudang';
    document.getElementById('gudangAction').value = 'edit';
    document.getElementById('gudangId').value = id;
    document.getElementById('gudangNama').value = nama;
    document.getElementById('gudangLokasi').value = lokasi;
    showModal('modalGudang');
}

function closeModalGudang() { hideModal('modalGudang'); }

function confirmHapusBarang(id, nama) {
    if (confirm(`Hapus barang "${nama}"? Stok akan hilang permanen.`)) {
        document.getElementById('hapusBarangId').value = id;
        document.getElementById('formHapusBarang').submit();
    }
}

function confirmHapusGudang(id, nama) {
    if (confirm(`Hapus gudang "${nama}"? Pastikan tidak ada barang di gudang ini.`)) {
        document.getElementById('hapusGudangId').value = id;
        document.getElementById('formHapusGudang').submit();
    }
}

function showModal(id) {
    const m = document.getElementById(id);
    m.classList.remove('hidden');
    m.classList.add('flex');
    if (window.lucide) lucide.createIcons();
}

function hideModal(id) {
    const m = document.getElementById(id);
    m.classList.add('hidden');
    m.classList.remove('flex');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModalBarang();
        closeModalGudang();
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>