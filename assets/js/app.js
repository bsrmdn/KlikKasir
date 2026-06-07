/**
 * assets/js/app.js
 *
 * JavaScript utama untuk aplikasi KlikKasir.
 * Menangani semua interaksi frontend yang dinamis:
 *
 *   1. KERANJANG BELANJA (kasir.php)
 *      - Menambah produk ke keranjang
 *      - Update quantity via tombol +/- atau input manual
 *      - Menampilkan total harga secara real-time
 *
 *   2. PEMBAYARAN (kasir.php)
 *      - Mengirim data transaksi ke process/process_payment.php via Fetch API
 *      - Menampilkan modal sukses dengan info kembalian
 *      - Update badge stok produk di grid tanpa reload halaman
 *
 *   3. DETAIL TRANSAKSI (transaksi.php)
 *      - Mengambil detail item transaksi via Fetch API
 *      - Menampilkan data dalam modal
 *
 *   4. UPDATE STOK (gudang.php)
 *      - Membuka modal update stok
 *      - Mengirim perubahan stok ke process/update_stock_action.php via Fetch API
 *      - Update tampilan badge stok dan statistik tanpa reload halaman
 *
 *   5. PENCARIAN PRODUK (kasir.php)
 *      - Filter produk secara real-time berdasarkan nama/kategori/gudang
 */

// ============================================================
// STATE APLIKASI
// ============================================================

/** @type {Array<{id: number, name: string, price: number, stock: number, qty: number}>} */
let cart = []; // Array yang menyimpan semua item dalam keranjang belanja

/**
 * State modal update stok: menyimpan data barang yang sedang diupdate
 * @type {{ id: number, name: string, currentStock: number }}
 */
let stockModalState = {
  id: 0,
  name: "",
  currentStock: 0,
};

// ============================================================
// INISIALISASI (DOMContentLoaded)
// ============================================================

/**
 * Jalankan setup awal setelah DOM selesai dimuat.
 * Mendaftarkan semua event listener menggunakan event delegation untuk efisiensi
 * (menghindari listener per-element yang boros memori).
 */
document.addEventListener("DOMContentLoaded", () => {
  // Setup pencarian produk (kasir.php)
  const search = document.getElementById("searchBar");
  if (search) search.addEventListener("input", (e) => filterProducts(e.target.value));

  // Setup event delegation untuk keranjang belanja
  const cartItems = document.getElementById("cartItems");
  if (cartItems) {
    // Tangani klik tombol + (increase) dan - (decrease) di keranjang
    cartItems.addEventListener("click", (event) => {
      const actionButton = event.target.closest("[data-cart-action]");
      if (!actionButton) return;

      updateCartQty(Number(actionButton.dataset.productId || 0), actionButton.dataset.cartAction);
    });

    // Tangani perubahan input quantity (saat user blur dari field angka)
    cartItems.addEventListener("change", (event) => {
      const quantityInput = event.target.closest("[data-cart-qty-input]");
      if (!quantityInput) return;

      commitCartQty(Number(quantityInput.dataset.productId || 0), quantityInput.value);
    });

    // Tangani Enter pada input quantity (commit segera saat tekan Enter)
    cartItems.addEventListener("keydown", (event) => {
      const quantityInput = event.target.closest("[data-cart-qty-input]");
      if (!quantityInput) return;

      if (event.key === "Enter") {
        event.preventDefault();
        commitCartQty(Number(quantityInput.dataset.productId || 0), quantityInput.value);
        quantityInput.blur(); // Unfocus input setelah commit
      }
    });

    // Render ulang keranjang saat halaman pertama kali dibuka
    updateCartUI();
  }
  
  // Event delegation untuk tombol "+ Stok" di tabel barang (gudang.php)
  // Menggunakan data-open-stock attribute sebagai penanda tombol
  document.body.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-open-stock]');
    if (!btn) return;
    const id    = Number(btn.dataset.id || 0);
    const name  = btn.dataset.name || '';
    const stock = Number(btn.dataset.stock || 0);
    openStockModal(id, name, stock);
  });
});

// ============================================================
// PENCARIAN PRODUK
// ============================================================

/**
 * Filter kartu produk secara real-time berdasarkan input pencarian.
 * Mencari di nama produk, kategori, dan nama gudang (semua case-insensitive).
 *
 * @param {string} filter  Teks yang diketik user di search bar
 */
function filterProducts(filter = "") {
  const normalizedFilter = filter.toLowerCase().trim();

  // Iterasi semua kartu produk dan tampilkan/sembunyikan berdasarkan filter
  document.querySelectorAll("[data-product-card]").forEach((card) => {
    // Gabungkan semua teks yang bisa dicari dari data-* attributes
    const searchText = [card.dataset.name, card.dataset.category, card.dataset.gudang]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();

    // Tampilkan kartu jika ada kecocokan, sembunyikan jika tidak
    card.style.display = searchText.includes(normalizedFilter) ? "" : "none";
  });
}

// ============================================================
// KERANJANG BELANJA
// ============================================================

/**
 * Menambahkan produk ke keranjang saat user klik kartu produk.
 * Jika produk sudah ada di keranjang, increment quantity-nya.
 * Stok tidak boleh dilampaui.
 *
 * @param {HTMLElement} button  Elemen button kartu produk yang diklik
 *                              (harus memiliki data-id, data-name, data-price, data-stock)
 */
function addToCart(button) {
  // Baca semua data produk dari data-* attributes pada tombol
  const p = {
    id:       Number(button.dataset.id),
    name:     button.dataset.name,
    category: button.dataset.category,
    price:    Number(button.dataset.price),
    stock:    Number(button.dataset.stock),
  };

  // Cek apakah produk sudah ada di keranjang
  const item = cart.find((i) => i.id === p.id);
  if (item) {
    // Sudah ada: tambah quantity, tapi jangan melebihi stok
    if (item.qty < p.stock) item.qty++;
    else alert("Stok habis!");
  } else {
    // Belum ada: tambahkan sebagai item baru dengan qty = 1
    cart.push({ ...p, qty: 1 });
  }
  updateCartUI(); // Render ulang tampilan keranjang
}

/**
 * Me-render ulang tampilan keranjang belanja berdasarkan state array cart[].
 * Mengupdate: daftar item, total harga, dan status tombol bayar.
 */
function updateCartUI() {
  const container = document.getElementById("cartItems");
  if (!container) return;

  // Hitung total harga semua item (price × qty untuk setiap item)
  const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);

  if (cart.length === 0) {
    // Tampilkan pesan kosong jika keranjang tidak ada item
    container.innerHTML = '<div class="text-sm text-slate-500">Keranjang masih kosong.</div>';
  } else {
    // Render setiap item keranjang sebagai card dengan input quantity dan tombol +/-
    container.innerHTML = cart
      .map((item) => {
        return `
          <div class="mb-3 flex items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div class="min-w-0 flex-1">
              <div class="truncate text-sm font-semibold text-slate-900">${item.name}</div>
              <div class="mt-1 text-xs text-slate-500">Rp ${item.price.toLocaleString("id-ID")} / pcs</div>

              <div class="mt-3 flex items-center gap-3">
                <!-- Input quantity: user bisa ketik langsung -->
                <input type="number" min="0" step="1" value="${item.qty}" data-cart-qty-input data-product-id="${item.id}" class="h-10 w-24 rounded-xl border border-slate-200 bg-white px-3 text-center text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" aria-label="Jumlah ${item.name}">

                <!-- Tombol +/- untuk increment/decrement quantity -->
                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white shadow-sm">
                  <button type="button" data-cart-action="decrease" data-product-id="${item.id}" class="inline-flex h-9 w-10 items-center justify-center text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Kurangi jumlah ${item.name}">
                    <i data-lucide="minus" class="h-4 w-4"></i>
                  </button>
                  <button type="button" data-cart-action="increase" data-product-id="${item.id}" class="inline-flex h-9 w-10 items-center justify-center border-l border-slate-200 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700" aria-label="Tambah jumlah ${item.name}">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Subtotal item (harga × qty) di sisi kanan -->
            <div class="min-w-24 text-right text-sm font-semibold text-slate-900">Rp ${(item.price * item.qty).toLocaleString("id-ID")}</div>
          </div>`;
      })
      .join("");
  }

  // Update tampilan total harga di bawah keranjang
  const totalPrice = document.getElementById("totalPrice");
  if (totalPrice) totalPrice.innerText = `Rp ${total.toLocaleString("id-ID")}`;

  // Nonaktifkan tombol bayar jika keranjang kosong
  const btnPay = document.getElementById("btnPay");
  if (btnPay) btnPay.disabled = cart.length === 0;

  // Re-render ikon Lucide yang baru saja ditambahkan ke DOM
  if (window.lucide && typeof window.lucide.createIcons === "function") {
    window.lucide.createIcons();
  }
}

/**
 * Mengubah quantity item di keranjang via tombol + atau -.
 * Jika quantity turun ke 0 atau kurang, item dihapus dari keranjang.
 *
 * @param {number} productId  ID produk yang akan diubah
 * @param {string} action     'increase' untuk tambah, 'decrease' untuk kurangi
 */
function updateCartQty(productId, action) {
  const itemIndex = cart.findIndex((item) => item.id === productId);
  if (itemIndex === -1) return; // Produk tidak ada di keranjang

  const item = cart[itemIndex];

  if (action === "increase") {
    // Cegah melebihi stok yang tersedia
    if (item.qty >= item.stock) {
      alert("Stok barang tidak mencukupi.");
      return;
    }
    item.qty += 1;
  } else if (action === "decrease") {
    item.qty -= 1;

    // Hapus item dari keranjang jika quantity = 0
    if (item.qty <= 0) {
      cart.splice(itemIndex, 1);
      updateCartUI();
      return;
    }
  }

  updateCartUI();
}

/**
 * Meng-commit nilai quantity yang diketik langsung oleh user ke input field.
 * Menangani edge case: nilai negatif, non-integer, atau melebihi stok.
 *
 * @param {number} productId  ID produk yang quantity-nya diubah
 * @param {string} rawValue   Nilai mentah dari input field (string)
 */
function commitCartQty(productId, rawValue) {
  const itemIndex = cart.findIndex((item) => item.id === productId);
  if (itemIndex === -1) return;

  const parsedQty = Number(rawValue);

  // Validasi: harus integer non-negatif
  if (!Number.isInteger(parsedQty) || parsedQty < 0) {
    updateCartUI(); // Reset tampilan ke nilai sebelumnya
    return;
  }

  const item = cart[itemIndex];
  // Batasi qty maksimum ke stok yang tersedia
  const nextQty = Math.min(parsedQty, item.stock);

  if (nextQty <= 0) {
    // Qty 0 → hapus item dari keranjang
    cart.splice(itemIndex, 1);
  } else {
    item.qty = nextQty;
  }

  updateCartUI();
}

// ============================================================
// MODAL UPDATE STOK (gudang.php)
// ============================================================

/**
 * Membuka modal update stok dari baris tabel (fungsi legacy, dipanggil via onclick).
 * Mencari data barang dari DOM berdasarkan data-barang-row attribute.
 *
 * @param {number|string} id  ID barang
 */
function addStock(id) {
  const row = document.querySelector(`[data-barang-row="${id}"]`);
  if (!row) return;

  // Baca nama dan stok dari DOM element
  openStockModal(
    Number(id),
    row.querySelector(".font-semibold.text-slate-900")?.textContent?.trim() || "",
    Number(row.dataset.stockValue || row.querySelector("[data-stock-badge]")?.textContent || 0)
  );
}

/**
 * Membuka modal update stok dengan data barang yang dipilih.
 * Menyimpan state ke stockModalState untuk digunakan oleh processStockAction().
 *
 * @param {number} id            ID barang
 * @param {string} nama          Nama barang untuk ditampilkan di modal
 * @param {number} stokSekarang  Stok saat ini untuk ditampilkan dan kalkulasi
 */
function openStockModal(id, nama, stokSekarang) {
  // Simpan state ke objek global
  stockModalState = {
    id:           Number(id) || 0,
    name:         nama || "",
    currentStock: Number(stokSekarang) || 0,
  };

  // Referensi elemen-elemen modal
  const modal        = document.getElementById("stockModal");
  const title        = document.getElementById("stockModalTitle");
  const name         = document.getElementById("stockModalName");
  const currentStock = document.getElementById("stockModalCurrentStock");
  const amountInput  = document.getElementById("stockAmount");

  if (!modal || !title || !name || !currentStock || !amountInput) return;

  // Isi konten modal dengan data barang yang dipilih
  title.textContent        = `#${stockModalState.id}`;
  name.textContent         = stockModalState.name;
  currentStock.textContent = `${stockModalState.currentStock} pcs`;
  amountInput.value        = ""; // Reset input jumlah
  amountInput.focus();           // Auto-focus ke input jumlah

  // Tampilkan modal (ubah 'hidden' menjadi 'flex')
  modal.classList.remove("hidden");
  modal.classList.add("flex");

  // Re-render ikon Lucide di dalam modal
  if (window.lucide && typeof window.lucide.createIcons === "function") {
    window.lucide.createIcons();
  }
}

/**
 * Menutup modal update stok.
 */
function closeStockModal() {
  const modal = document.getElementById("stockModal");
  if (!modal) return;

  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

/**
 * Memproses aksi update stok dan mengirimnya ke server via Fetch API.
 * Menghitung stok baru berdasarkan aksi, lalu mengirim ke update_stock_action.php.
 * Setelah berhasil, update badge stok dan statistik di halaman tanpa reload.
 *
 * @param {string} action  'set' (set langsung), 'add' (tambah), atau 'subtract' (kurangi)
 */
function processStockAction(action) {
  const amountInput = document.getElementById("stockAmount");
  if (!amountInput) return;

  const amount = Number(amountInput.value);

  // Validasi: jumlah harus angka bulat non-negatif
  if (!Number.isInteger(amount) || amount < 0) {
    alert("Jumlah stok harus berupa angka bulat 0 atau lebih.");
    return;
  }

  // Hitung stok baru berdasarkan aksi yang dipilih
  let stokBaru = stockModalState.currentStock;

  if (action === "set") {
    stokBaru = amount;              // Set langsung ke nilai yang diinput
  } else if (action === "add") {
    stokBaru += amount;             // Tambahkan ke stok saat ini
  } else if (action === "subtract") {
    stokBaru -= amount;             // Kurangi dari stok saat ini
  }

  // Stok tidak boleh negatif
  if (stokBaru < 0) {
    alert("Stok tidak boleh kurang dari 0.");
    return;
  }

  // Simpan stok sebelumnya untuk menghitung delta (perubahan)
  const previousStock = stockModalState.currentStock;

  // Kirim request ke server via Fetch API (JSON)
  fetch("process/update_stock_action.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({
      id_barang: stockModalState.id,
      stok_baru: stokBaru,
    }),
  })
    .then(async (response) => {
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload) throw new Error(payload?.message || "Gagal memperbarui stok.");
      if (!payload.success) throw new Error(payload.message || "Gagal memperbarui stok.");

      // Update state modal dengan stok baru dari server
      stockModalState.currentStock = Number(payload.data.stok);
      const deltaStock = stockModalState.currentStock - previousStock; // Selisih stok

      // ── Update badge stok di baris tabel barang ──────────────────────────
      const row        = document.querySelector(`[data-barang-row="${payload.data.id}"]`);
      const stockBadge = row?.querySelector("[data-stock-badge]");

      // Update data attribute di baris tabel
      if (row) row.dataset.stockValue = String(payload.data.stok);

      // Update badge teks dan warna (merah jika stok ≤ 10, hijau jika > 10)
      if (stockBadge) {
        stockBadge.textContent = `${payload.data.stok} pcs`;
        stockBadge.classList.toggle("bg-rose-100",    payload.data.stok <= 10);
        stockBadge.classList.toggle("text-rose-700",  payload.data.stok <= 10);
        stockBadge.classList.toggle("bg-emerald-100", payload.data.stok > 10);
        stockBadge.classList.toggle("text-emerald-700", payload.data.stok > 10);
      }

      // ── Update kartu statistik "Total Unit Stok" ─────────────────────────
      const statUnits = document.getElementById("statUnits");
      if (statUnits) {
        const currentUnits = Number(statUnits.textContent || 0);
        statUnits.textContent = String(currentUnits + deltaStock);
      }

      // ── Update kartu statistik "Nilai Aset" ──────────────────────────────
      const statValue = document.getElementById("statValue");
      if (statValue) {
        // Ambil nilai aset saat ini (hapus "Rp " dan pemisah ribuan)
        const currentValue = Number(statValue.textContent.replace(/[^0-9]/g, "") || 0);
        const unitPrice    = Number(row?.dataset.priceValue || 0); // Harga per unit dari data attribute
        // Nilai aset baru = nilai lama + (delta stok × harga satuan)
        statValue.textContent = `Rp ${(currentValue + deltaStock * unitPrice).toLocaleString("id-ID")}`;
      }

      // Tutup modal dan tampilkan notifikasi sukses
      closeStockModal();
      alert(payload.message || "Stok berhasil diperbarui.");
    })
    .catch((error) => {
      alert(error.message || "Gagal memperbarui stok.");
    });
}

// ============================================================
// PROSES PEMBAYARAN (kasir.php)
// ============================================================

/**
 * Memproses pembayaran transaksi.
 * Memvalidasi keranjang dan uang bayar, lalu mengirim data ke server.
 * Setelah berhasil: update UI produk, kosongkan keranjang, tampilkan modal sukses.
 */
function processPayment() {
  // Hitung total belanja dari keranjang
  const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  if (total === 0) {
    alert("Keranjang masih kosong.");
    return;
  }

  // Ambil uang bayar dari input field
  const cashAmount = Number(document.getElementById("cashAmount")?.value || 0);
  if (cashAmount < total) {
    alert("Uang bayar belum mencukupi.");
    return;
  }

  // Kirim data transaksi ke server via Fetch API (JSON)
  fetch("process/process_payment.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({
      cashAmount,
      // Kirim hanya id dan qty, harga diambil dari database untuk keamanan
      items: cart.map((item) => ({ id: item.id, qty: item.qty })),
    }),
  })
    .then(async (response) => {
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload) throw new Error(payload?.message || "Gagal memproses pembayaran.");
      if (!payload.success) throw new Error(payload.message || "Gagal memproses pembayaran.");

      // ── Update tampilan stok produk di grid (tanpa reload halaman) ────────
      payload.data.items.forEach((item) => {
        const card = document.querySelector(`[data-product-card][data-id="${item.id}"]`);
        if (!card) return;
        // Update data-stock attribute agar addToCart() mendapat nilai terbaru
        card.dataset.stock = String(item.stock);
        // Update teks badge stok di kartu produk
        const stockBadge = card.querySelector("[data-stock-badge]");
        if (stockBadge) stockBadge.textContent = item.stock;
        // Beri tampilan redup jika stok habis (stok = 0)
        card.classList.toggle("opacity-50", item.stock <= 0);
      });

      // ── Reset keranjang dan input uang bayar ─────────────────────────────
      cart = [];
      const cashInput = document.getElementById("cashAmount");
      if (cashInput) cashInput.value = "";
      updateCartUI();

      // ── Tampilkan modal sukses dengan info transaksi ─────────────────────
      showSuksesModal(payload.data);
    })
    .catch((error) => {
      alert(error.message || "Gagal memproses pembayaran.");
    });
}

/**
 * Menampilkan modal konfirmasi pembayaran berhasil.
 * Mengisi detail transaksi (nomor, total, bayar, kembalian) dan link cetak nota.
 *
 * @param {{ id_transaksi: number, total_harga: number, uang_bayar: number, kembalian: number }} data
 *   Data transaksi dari respons server
 */
function showSuksesModal(data) {
  const modal = document.getElementById("modalSukses");
  if (!modal) {
    // Fallback jika modal tidak ada di halaman
    alert(`Pembayaran Berhasil! Kembalian: Rp ${data.kembalian.toLocaleString("id-ID")}`);
    return;
  }

  // Helper untuk format angka ke format Rupiah
  const fmt = (n) => `Rp ${Number(n).toLocaleString("id-ID")}`;

  // Isi elemen-elemen modal dengan data transaksi
  document.getElementById("sukses_id_trx").textContent    = `#${data.id_transaksi}`;
  document.getElementById("sukses_total").textContent     = fmt(data.total_harga);
  document.getElementById("sukses_bayar").textContent     = fmt(data.uang_bayar);
  document.getElementById("sukses_kembalian").textContent = fmt(data.kembalian);

  // Update link tombol cetak nota dengan ID transaksi yang benar
  const btnNota = document.getElementById("btnCetakNota");
  if (btnNota) btnNota.href = `process/cetak_nota.php?id=${data.id_transaksi}`;

  // Tampilkan modal
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

/**
 * Menutup modal sukses pembayaran.
 */
function closeSuksesModal() {
  const modal = document.getElementById("modalSukses");
  if (!modal) return;
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

// ============================================================
// MODAL DETAIL TRANSAKSI (transaksi.php)
// ============================================================

/**
 * Membuka modal detail transaksi dan mengambil data dari server.
 * Menampilkan loading state saat data sedang diambil.
 *
 * @param {number} idTransaksi  ID transaksi yang akan ditampilkan detailnya
 */
function showDetail(idTransaksi) {
  // Referensi elemen-elemen modal
  const modal          = document.getElementById("detailModal");
  const label          = document.getElementById("modalTransactionLabel");
  const date           = document.getElementById("modalTransactionDate");
  const total          = document.getElementById("modalTransactionTotal");
  const pay            = document.getElementById("modalTransactionPay");
  const itemsContainer = document.getElementById("detailItems");

  if (!modal || !label || !date || !total || !pay || !itemsContainer) return;

  // ── Tampilkan loading state ────────────────────────────────────────────────
  label.textContent = `#${idTransaksi}`;
  date.textContent  = "Memuat...";
  total.textContent = "Memuat...";
  pay.textContent   = "Memuat...";
  itemsContainer.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Memuat detail transaksi...</td></tr>';

  // Tampilkan modal
  modal.classList.remove("hidden");
  modal.classList.add("flex");

  // ── Ambil data dari server via Fetch API ─────────────────────────────────
  fetch(`process/get_detail_transaksi.php?id_transaksi=${encodeURIComponent(idTransaksi)}`)
    .then(async (response) => {
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload) throw new Error(payload?.message || "Gagal memuat detail transaksi.");
      if (!payload.success) throw new Error(payload.message || "Gagal memuat detail transaksi.");

      const transaksi = payload.transaksi;

      // Isi header modal dengan data transaksi
      label.textContent = `#${transaksi.id_transaksi}`;

      // Format tanggal ke bahasa Indonesia menggunakan Intl.DateTimeFormat
      date.textContent = new Intl.DateTimeFormat("id-ID", {
        day:    "2-digit",
        month:  "short",
        year:   "numeric",
        hour:   "2-digit",
        minute: "2-digit",
      }).format(new Date(transaksi.tgl_transaksi));

      total.textContent = `Rp ${Number(transaksi.total_harga).toLocaleString("id-ID")}`;
      // Tampilkan "Bayar / Kembalian" dalam satu baris
      pay.textContent   = `Rp ${Number(transaksi.uang_bayar).toLocaleString("id-ID")} / Rp ${Number(transaksi.kembalian).toLocaleString("id-ID")}`;

      // Tangani kasus tidak ada item (data sudah dihapus, dll)
      if (payload.items.length === 0) {
        itemsContainer.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Tidak ada detail item.</td></tr>';
        return;
      }

      // Render tabel item transaksi
      itemsContainer.innerHTML = payload.items
        .map(
          (item) => `
            <tr class="hover:bg-slate-50/80">
              <td class="px-4 py-4 font-semibold text-slate-900">${item.nama_barang}</td>
              <td class="px-4 py-4 text-center text-slate-700">${item.qty}</td>
              <td class="px-4 py-4 text-right text-slate-700">Rp ${Number(item.harga_satuan).toLocaleString("id-ID")}</td>
              <td class="px-4 py-4 text-right font-semibold text-slate-700">Rp ${Number(item.subtotal).toLocaleString("id-ID")}</td>
            </tr>`
        )
        .join("");
    })
    .catch((error) => {
      // Tampilkan pesan error di dalam tabel jika fetch gagal
      itemsContainer.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-rose-600">${error.message || "Gagal memuat detail transaksi."}</td></tr>`;
    });
}

/**
 * Menutup modal detail transaksi.
 */
function closeModal() {
  const modal = document.getElementById("detailModal");
  if (!modal) return;

  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

// ============================================================
// LOGOUT (legacy / fallback)
// ============================================================

/**
 * Fungsi logout dengan konfirmasi (tidak digunakan oleh form navbar,
 * disediakan sebagai fallback jika ada tombol logout via JavaScript).
 */
function logout() {
  if (confirm("Apakah Anda yakin ingin keluar?")) {
    window.location.href = "index.php";
  }
}
