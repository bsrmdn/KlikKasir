let cart = [];
let stockModalState = {
  id: 0,
  name: "",
  currentStock: 0,
};

document.addEventListener("DOMContentLoaded", () => {
  const search = document.getElementById("searchBar");
  if (search) search.addEventListener("input", (e) => filterProducts(e.target.value));

  const cartItems = document.getElementById("cartItems");
  if (cartItems) {
    cartItems.addEventListener("click", (event) => {
      const actionButton = event.target.closest("[data-cart-action]");
      if (!actionButton) return;

      updateCartQty(Number(actionButton.dataset.productId || 0), actionButton.dataset.cartAction);
    });

    cartItems.addEventListener("change", (event) => {
      const quantityInput = event.target.closest("[data-cart-qty-input]");
      if (!quantityInput) return;

      commitCartQty(Number(quantityInput.dataset.productId || 0), quantityInput.value);
    });

    cartItems.addEventListener("keydown", (event) => {
      const quantityInput = event.target.closest("[data-cart-qty-input]");
      if (!quantityInput) return;

      if (event.key === "Enter") {
        event.preventDefault();
        commitCartQty(Number(quantityInput.dataset.productId || 0), quantityInput.value);
        quantityInput.blur();
      }
    });

    updateCartUI();
  }
  
  // Event delegation for stock modal buttons (use data-* attributes)
  document.body.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-open-stock]');
    if (!btn) return;
    const id = Number(btn.dataset.id || 0);
    const name = btn.dataset.name || '';
    const stock = Number(btn.dataset.stock || 0);
    openStockModal(id, name, stock);
  });
});

function filterProducts(filter = "") {
  const normalizedFilter = filter.toLowerCase().trim();

  document.querySelectorAll("[data-product-card]").forEach((card) => {
    const searchText = [card.dataset.name, card.dataset.category, card.dataset.gudang]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();

    card.style.display = searchText.includes(normalizedFilter) ? "" : "none";
  });
}

function addToCart(button) {
  const p = {
    id: Number(button.dataset.id),
    name: button.dataset.name,
    category: button.dataset.category,
    price: Number(button.dataset.price),
    stock: Number(button.dataset.stock),
  };

  const item = cart.find((i) => i.id === p.id);
  if (item) {
    if (item.qty < p.stock) item.qty++;
    else alert("Stok habis!");
  } else {
    cart.push({ ...p, qty: 1 });
  }
  updateCartUI();
}

function updateCartUI() {
  const container = document.getElementById("cartItems");
  if (!container) return;

  const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);

  if (cart.length === 0) {
    container.innerHTML = '<div class="text-sm text-slate-500">Keranjang masih kosong.</div>';
  } else {
    container.innerHTML = cart
      .map((item) => {
        return `
          <div class="mb-3 flex items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div class="min-w-0 flex-1">
              <div class="truncate text-sm font-semibold text-slate-900">${item.name}</div>
              <div class="mt-1 text-xs text-slate-500">Rp ${item.price.toLocaleString("id-ID")} / pcs</div>

              <div class="mt-3 flex items-center gap-3">
                <input type="number" min="0" step="1" value="${item.qty}" data-cart-qty-input data-product-id="${item.id}" class="h-10 w-24 rounded-xl border border-slate-200 bg-white px-3 text-center text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" aria-label="Jumlah ${item.name}">

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

            <div class="min-w-24 text-right text-sm font-semibold text-slate-900">Rp ${(item.price * item.qty).toLocaleString("id-ID")}</div>
          </div>`;
      })
      .join("");
  }

  const totalPrice = document.getElementById("totalPrice");
  if (totalPrice) totalPrice.innerText = `Rp ${total.toLocaleString("id-ID")}`;

  const btnPay = document.getElementById("btnPay");
  if (btnPay) btnPay.disabled = cart.length === 0;

  if (window.lucide && typeof window.lucide.createIcons === "function") {
    window.lucide.createIcons();
  }
}

function updateCartQty(productId, action) {
  const itemIndex = cart.findIndex((item) => item.id === productId);
  if (itemIndex === -1) return;

  const item = cart[itemIndex];

  if (action === "increase") {
    if (item.qty >= item.stock) {
      alert("Stok barang tidak mencukupi.");
      return;
    }

    item.qty += 1;
  } else if (action === "decrease") {
    item.qty -= 1;

    if (item.qty <= 0) {
      cart.splice(itemIndex, 1);
      updateCartUI();
      return;
    }
  }

  updateCartUI();
}

function commitCartQty(productId, rawValue) {
  const itemIndex = cart.findIndex((item) => item.id === productId);
  if (itemIndex === -1) return;

  const parsedQty = Number(rawValue);
  if (!Number.isInteger(parsedQty) || parsedQty < 0) {
    updateCartUI();
    return;
  }

  const item = cart[itemIndex];
  const nextQty = Math.min(parsedQty, item.stock);

  if (nextQty <= 0) {
    cart.splice(itemIndex, 1);
  } else {
    item.qty = nextQty;
  }

  updateCartUI();
}

function addStock(id) {
  const row = document.querySelector(`[data-barang-row="${id}"]`);
  if (!row) return;

  openStockModal(
    Number(id),
    row.querySelector(".font-semibold.text-slate-900")?.textContent?.trim() || "",
    Number(row.dataset.stockValue || row.querySelector("[data-stock-badge]")?.textContent || 0)
  );
}

function openStockModal(id, nama, stokSekarang) {
  stockModalState = {
    id: Number(id) || 0,
    name: nama || "",
    currentStock: Number(stokSekarang) || 0,
  };

  const modal = document.getElementById("stockModal");
  const title = document.getElementById("stockModalTitle");
  const name = document.getElementById("stockModalName");
  const currentStock = document.getElementById("stockModalCurrentStock");
  const amountInput = document.getElementById("stockAmount");

  if (!modal || !title || !name || !currentStock || !amountInput) return;

  title.textContent = `#${stockModalState.id}`;
  name.textContent = stockModalState.name;
  currentStock.textContent = `${stockModalState.currentStock} pcs`;
  amountInput.value = "";
  amountInput.focus();

  modal.classList.remove("hidden");
  modal.classList.add("flex");

  if (window.lucide && typeof window.lucide.createIcons === "function") {
    window.lucide.createIcons();
  }
}

function closeStockModal() {
  const modal = document.getElementById("stockModal");
  if (!modal) return;

  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

function processStockAction(action) {
  const amountInput = document.getElementById("stockAmount");
  if (!amountInput) return;

  const amount = Number(amountInput.value);
  if (!Number.isInteger(amount) || amount < 0) {
    alert("Jumlah stok harus berupa angka bulat 0 atau lebih.");
    return;
  }

  let stokBaru = stockModalState.currentStock;

  if (action === "set") {
    stokBaru = amount;
  } else if (action === "add") {
    stokBaru += amount;
  } else if (action === "subtract") {
    stokBaru -= amount;
  }

  if (stokBaru < 0) {
    alert("Stok tidak boleh kurang dari 0.");
    return;
  }

  const previousStock = stockModalState.currentStock;

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

      stockModalState.currentStock = Number(payload.data.stok);
  const deltaStock = stockModalState.currentStock - previousStock;

      const row = document.querySelector(`[data-barang-row="${payload.data.id}"]`);
      const stockBadge = row?.querySelector("[data-stock-badge]");
      if (row) row.dataset.stockValue = String(payload.data.stok);
      if (stockBadge) {
        stockBadge.textContent = `${payload.data.stok} pcs`;
        stockBadge.classList.toggle("bg-rose-100", payload.data.stok <= 10);
        stockBadge.classList.toggle("text-rose-700", payload.data.stok <= 10);
        stockBadge.classList.toggle("bg-emerald-100", payload.data.stok > 10);
        stockBadge.classList.toggle("text-emerald-700", payload.data.stok > 10);
      }

      const statUnits = document.getElementById("statUnits");
      if (statUnits) {
        const currentUnits = Number(statUnits.textContent || 0);
        statUnits.textContent = String(currentUnits + deltaStock);
      }

      const statValue = document.getElementById("statValue");
      if (statValue) {
        const currentValue = Number(statValue.textContent.replace(/[^0-9]/g, "") || 0);
        const unitPrice = Number(row?.dataset.priceValue || 0);
        statValue.textContent = `Rp ${(currentValue + deltaStock * unitPrice).toLocaleString("id-ID")}`;
      }

      closeStockModal();
      alert(payload.message || "Stok berhasil diperbarui.");
    })
    .catch((error) => {
      alert(error.message || "Gagal memperbarui stok.");
    });
}

function processPayment() {
  const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  if (total === 0) {
    alert("Keranjang masih kosong.");
    return;
  }

  const cashAmount = Number(document.getElementById("cashAmount")?.value || 0);
  if (cashAmount < total) {
    alert("Uang bayar belum mencukupi.");
    return;
  }

  fetch("process/process_payment.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({
      cashAmount,
      items: cart.map((item) => ({ id: item.id, qty: item.qty })),
    }),
  })
    .then(async (response) => {
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload) throw new Error(payload?.message || "Gagal memproses pembayaran.");
      if (!payload.success) throw new Error(payload.message || "Gagal memproses pembayaran.");

      payload.data.items.forEach((item) => {
        const card = document.querySelector(`[data-product-card][data-id="${item.id}"]`);
        if (!card) return;
        card.dataset.stock = String(item.stock);
        const stockBadge = card.querySelector("[data-stock-badge]");
        if (stockBadge) stockBadge.textContent = item.stock;
        card.classList.toggle("opacity-50", item.stock <= 0);
      });

      cart = [];
      const cashInput = document.getElementById("cashAmount");
      if (cashInput) cashInput.value = "";
      updateCartUI();

      // Tampilkan modal sukses
      showSuksesModal(payload.data);
    })
    .catch((error) => {
      alert(error.message || "Gagal memproses pembayaran.");
    });
}

function showSuksesModal(data) {
  const modal = document.getElementById("modalSukses");
  if (!modal) {
    alert(`Pembayaran Berhasil! Kembalian: Rp ${data.kembalian.toLocaleString("id-ID")}`);
    return;
  }

  const fmt = (n) => `Rp ${Number(n).toLocaleString("id-ID")}`;
  document.getElementById("sukses_id_trx").textContent    = `#${data.id_transaksi}`;
  document.getElementById("sukses_total").textContent     = fmt(data.total_harga);
  document.getElementById("sukses_bayar").textContent     = fmt(data.uang_bayar);
  document.getElementById("sukses_kembalian").textContent = fmt(data.kembalian);

  const btnNota = document.getElementById("btnCetakNota");
  if (btnNota) btnNota.href = `process/cetak_nota.php?id=${data.id_transaksi}`;

  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeSuksesModal() {
  const modal = document.getElementById("modalSukses");
  if (!modal) return;
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

function showDetail(idTransaksi) {
  const modal = document.getElementById("detailModal");
  const label = document.getElementById("modalTransactionLabel");
  const date = document.getElementById("modalTransactionDate");
  const total = document.getElementById("modalTransactionTotal");
  const pay = document.getElementById("modalTransactionPay");
  const itemsContainer = document.getElementById("detailItems");

  if (!modal || !label || !date || !total || !pay || !itemsContainer) return;

  label.textContent = `#${idTransaksi}`;
  date.textContent = "Memuat...";
  total.textContent = "Memuat...";
  pay.textContent = "Memuat...";
  itemsContainer.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Memuat detail transaksi...</td></tr>';

  modal.classList.remove("hidden");
  modal.classList.add("flex");

  fetch(`process/get_detail_transaksi.php?id_transaksi=${encodeURIComponent(idTransaksi)}`)
    .then(async (response) => {
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload) throw new Error(payload?.message || "Gagal memuat detail transaksi.");
      if (!payload.success) throw new Error(payload.message || "Gagal memuat detail transaksi.");

      const transaksi = payload.transaksi;
      label.textContent = `#${transaksi.id_transaksi}`;
      date.textContent = new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      }).format(new Date(transaksi.tgl_transaksi));
      total.textContent = `Rp ${Number(transaksi.total_harga).toLocaleString("id-ID")}`;
      pay.textContent = `Rp ${Number(transaksi.uang_bayar).toLocaleString("id-ID")} / Rp ${Number(transaksi.kembalian).toLocaleString("id-ID")}`;

      if (payload.items.length === 0) {
        itemsContainer.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">Tidak ada detail item.</td></tr>';
        return;
      }

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
      itemsContainer.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-rose-600">${error.message || "Gagal memuat detail transaksi."}</td></tr>`;
    });
}

function closeModal() {
  const modal = document.getElementById("detailModal");
  if (!modal) return;

  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

function logout() {
  if (confirm("Apakah Anda yakin ingin keluar?")) {
    window.location.href = "index.php";
  }
}
