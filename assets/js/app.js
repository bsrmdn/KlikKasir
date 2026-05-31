let cart = [];

document.addEventListener("DOMContentLoaded", () => {
  const search = document.getElementById("searchBar");
  if (search) search.addEventListener("input", (e) => filterProducts(e.target.value));

  if (document.getElementById("cartItems")) updateCartUI();
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

  let total = 0;

  if (cart.length === 0) {
    container.innerHTML = '<div class="text-sm text-slate-500">Keranjang masih kosong.</div>';
  } else {
    container.innerHTML = cart
      .map((item) => {
        total += item.price * item.qty;
        return `<div class="mb-2 flex items-center justify-between gap-4 text-sm text-slate-700"><span>${item.name} (x${item.qty})</span><span>Rp ${(item.price * item.qty).toLocaleString("id-ID")}</span></div>`;
      })
      .join("");
  }

  const totalPrice = document.getElementById("totalPrice");
  if (totalPrice) totalPrice.innerText = `Rp ${total.toLocaleString("id-ID")}`;

  const btnPay = document.getElementById("btnPay");
  if (btnPay) btnPay.disabled = cart.length === 0;
}

function addStock(id) {
  const amountInput = prompt("Tambah stok berapa?", "10");
  if (amountInput === null) return;

  const amount = Number(amountInput);
  if (!Number.isInteger(amount) || amount <= 0) {
    alert("Jumlah stok harus berupa angka bulat lebih dari 0.");
    return;
  }

  fetch("process/add_stock.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ id, amount }),
  })
    .then(async (response) => {
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload) throw new Error(payload?.message || "Gagal menambah stok.");
      if (!payload.success) throw new Error(payload.message || "Gagal menambah stok.");

      const row = document.querySelector(`[data-barang-row="${id}"]`);
      const stockBadge = row?.querySelector("[data-stock-badge]");
      if (stockBadge) {
        stockBadge.textContent = `${payload.data.stok} pcs`;
        stockBadge.classList.toggle("bg-rose-100", payload.data.stok <= 10);
        stockBadge.classList.toggle("text-rose-700", payload.data.stok <= 10);
        stockBadge.classList.toggle("bg-emerald-100", payload.data.stok > 10);
        stockBadge.classList.toggle("text-emerald-700", payload.data.stok > 10);
      }

      const productCardStock = document.querySelector(`[data-product-card][data-id="${id}"] [data-stock-badge]`);
      if (productCardStock) {
        productCardStock.textContent = payload.data.stok;
      }

      const statUnits = document.getElementById("statUnits");
      if (statUnits) {
        const currentUnits = Number(statUnits.textContent || 0);
        statUnits.textContent = String(currentUnits + payload.data.delta_unit);
      }

      const statValue = document.getElementById("statValue");
      if (statValue) {
        const currentValue = Number(statValue.textContent.replace(/[^0-9]/g, "") || 0);
        const nextValue = currentValue + payload.data.delta_value;
        statValue.textContent = `Rp ${nextValue.toLocaleString("id-ID")}`;
      }

      alert(payload.message || "Stok berhasil ditambahkan.");
    })
    .catch((error) => {
      alert(error.message || "Gagal menambah stok.");
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

      alert(`Pembayaran Berhasil! Kembalian: Rp ${payload.data.kembalian.toLocaleString("id-ID")}`);
    })
    .catch((error) => {
      alert(error.message || "Gagal memproses pembayaran.");
    });
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
