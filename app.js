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
    container.innerHTML = '<div style="color:var(--text-muted); font-size:0.9rem;">Keranjang masih kosong.</div>';
  } else {
    container.innerHTML = cart
      .map((item) => {
        total += item.price * item.qty;
        return `<div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.85rem">
            <span>${item.name} (x${item.qty})</span>
            <span>Rp ${(item.price * item.qty).toLocaleString("id-ID")}</span>
        </div>`;
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

  fetch("add_stock.php", {
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
        stockBadge.classList.toggle("stock-low", payload.data.stok <= 10);
        stockBadge.classList.toggle("stock-ok", payload.data.stok > 10);
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

  fetch("process_payment.php", {
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
        card.style.opacity = item.stock <= 0 ? "0.5" : "1";
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
function logout() {
  if (confirm("Apakah Anda yakin ingin keluar?")) {
    window.location.href = "index.html";
  }
}
