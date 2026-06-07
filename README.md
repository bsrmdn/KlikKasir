# 🛒 KlikKasir — Sistem Kasir Toko Berbasis Web

KlikKasir adalah aplikasi kasir toko berbasis web yang dibangun dengan **PHP native** (tanpa framework) dan **MySQL** sebagai database. Aplikasi ini mendukung dua peran pengguna: **Admin** dan **Kasir**, masing-masing dengan hak akses yang berbeda.

---

## 📋 Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Struktur Direktori](#struktur-direktori)
- [Skema Database](#skema-database)
- [Alur Logika Keseluruhan](#alur-logika-keseluruhan)
  - [1. Autentikasi (Login & Logout)](#1-autentikasi-login--logout)
  - [2. Dashboard](#2-dashboard)
  - [3. Modul Kasir (Transaksi Penjualan)](#3-modul-kasir-transaksi-penjualan)
  - [4. Riwayat Transaksi](#4-riwayat-transaksi)
  - [5. Manajemen Gudang & Barang](#5-manajemen-gudang--barang)
  - [6. Kelola Kasir (Admin Only)](#6-kelola-kasir-admin-only)
  - [7. Cetak Nota](#7-cetak-nota)
- [Sistem Kontrol Akses (Role-Based Access)](#sistem-kontrol-akses-role-based-access)
- [API Endpoints (JSON)](#api-endpoints-json)
- [Cara Instalasi & Menjalankan](#cara-instalasi--menjalankan)
- [Akun Default](#akun-default)

---

## Fitur Utama

| Fitur | Admin | Kasir |
|---|:---:|:---:|
| Login & Logout | ✅ | ✅ |
| Dashboard statistik | ✅ | ✅ |
| Transaksi penjualan (Kasir) | ✅ | ✅ |
| Riwayat transaksi | ✅ | ✅ |
| Cetak nota PDF | ✅ | ✅ |
| Update stok barang | ✅ | ✅ |
| Tambah/Edit/Hapus barang | ✅ | ❌ |
| Tambah/Edit/Hapus gudang | ✅ | ❌ |
| Kelola akun kasir | ✅ | ❌ |

---

## Teknologi yang Digunakan

- **Backend**: PHP 8+ (native, tanpa framework)
- **Database**: MySQL / MariaDB dengan ekstensi `mysqli`
- **Frontend**: HTML5, TailwindCSS (via CDN), JavaScript (vanilla)
- **Ikon**: [Lucide Icons](https://lucide.dev/) (via CDN)
- **Keamanan**: `htmlspecialchars` untuk XSS prevention, `password_hash` / `password_verify` untuk password, prepared statements untuk SQL Injection prevention

---

## Struktur Direktori

```
KlikKasir/
│
├── index.php               # Halaman login
├── dashboard.php           # Dashboard utama (statistik & ringkasan)
├── kasir.php               # Halaman transaksi penjualan
├── transaksi.php           # Riwayat transaksi
├── gudang.php              # Manajemen gudang & barang
├── kelola_kasir.php        # Manajemen akun kasir (admin only)
├── error.php               # Halaman error generik
│
├── config/
│   ├── database.php        # Konfigurasi & koneksi database (mysqli)
│   └── session.php         # Manajemen sesi & fungsi helper autentikasi
│
├── includes/
│   ├── header.php          # Template head HTML (title, CDN, meta)
│   ├── navbar.php          # Navigasi atas (role-aware)
│   ├── footer.php          # Penutup body HTML + inject script
│   └── helpers.php         # Fungsi h() untuk XSS escaping
│
├── process/
│   ├── login_process.php       # Handler POST login
│   ├── logout.php              # Handler logout (destroy session)
│   ├── process_payment.php     # API JSON: proses transaksi penjualan
│   ├── get_detail_transaksi.php # API JSON: detail item transaksi
│   ├── cetak_nota.php          # Halaman print-friendly nota
│   ├── barang_action.php       # Handler POST CRUD barang (admin)
│   ├── gudang_action.php       # Handler POST CRUD gudang (admin)
│   ├── kasir_action.php        # Handler POST CRUD akun kasir (admin)
│   ├── add_stock.php           # API JSON: tambah stok (legacy)
│   └── update_stock_action.php # API JSON: update stok barang
│
├── assets/
│   ├── css/style.css       # Stylesheet tambahan (minimal)
│   └── js/app.js           # JavaScript utama (keranjang, modal, fetch)
│
└── database/
    └── schema.sql          # DDL + seed data database
```

---

## Skema Database

```
users          gudang         barang              transaksi         detail_transaksi
─────────      ──────────     ──────────────      ─────────────     ────────────────
id_user   ←   id_gudang ←─── id_gudang (FK)      id_transaksi  ←── id_transaksi (FK)
nama           nama_gudang    id           ────────────────────────► id (FK → barang)
username       lokasi         nama_barang                           jumlah
password                      kategori                              harga_satuan
role                          harga                                 subtotal
created_at                    stok
                              id_gudang
```

### Relasi Antar Tabel

- **`barang.id_gudang`** → `gudang.id_gudang` (RESTRICT delete: gudang tidak bisa dihapus jika masih ada barang)
- **`detail_transaksi.id_transaksi`** → `transaksi.id_transaksi` (CASCADE delete)
- **`detail_transaksi.id`** → `barang.id` (RESTRICT delete: barang tidak bisa dihapus jika ada di riwayat transaksi)

---

## Alur Logika Keseluruhan

### 1. Autentikasi (Login & Logout)

```
[Browser] → GET index.php
    │
    ├─ Jika $_SESSION['username'] sudah ada → redirect ke dashboard.php
    └─ Tampilkan form login

[User submit form] → POST process/login_process.php
    │
    ├─ Validasi: username & password tidak kosong
    ├─ Query: SELECT dari tabel users WHERE username = ?
    ├─ Verifikasi password: password_verify() atau plain-text (fallback)
    │
    ├─ SUKSES:
    │   ├─ session_regenerate_id(true)  ← cegah session fixation
    │   ├─ Set $_SESSION: id_user, username, nama, role
    │   ├─ role = 'admin' → redirect dashboard.php
    │   └─ role = 'kasir' → redirect kasir.php
    │
    └─ GAGAL → redirect index.php?error=1

[User klik Keluar] → POST process/logout.php
    ├─ Kosongkan $_SESSION = []
    ├─ Hapus cookie sesi
    ├─ session_destroy()
    └─ redirect index.php
```

---

### 2. Dashboard

```
[Browser] → GET dashboard.php
    │
    ├─ require_login() → cek $_SESSION['username'], redirect jika belum login
    ├─ Query 1: COUNT barang + SUM stok dari tabel barang
    ├─ Query 2: COUNT transaksi + SUM total_harga dari tabel transaksi
    ├─ Query 3: 5 transaksi terbaru ORDER BY tgl_transaksi DESC
    │
    └─ Render:
        ├─ 4 kartu statistik (Barang, Stok, Transaksi, Pendapatan)
        ├─ Quick Actions (link ke modul lain, admin mendapat lebih banyak)
        └─ Tabel 5 transaksi terbaru
```

---

### 3. Modul Kasir (Transaksi Penjualan)

Ini adalah alur paling kompleks dan melibatkan komunikasi **frontend ↔ backend melalui Fetch API (JSON)**.

```
[Browser] → GET kasir.php
    │
    ├─ Query: SELECT semua barang JOIN gudang (ORDER BY nama_barang)
    └─ Render grid produk (data ditempatkan di data-* attribute setiap kartu)

── FASE PEMILIHAN BARANG (JavaScript / app.js) ──────────────────────────
    │
    ├─ User ketik di #searchBar → filterProducts() menyaring kartu secara real-time
    │
    └─ User klik kartu produk → addToCart(button)
        ├─ Baca data-id, data-name, data-price, data-stock dari button
        ├─ Jika sudah ada di cart: qty++ (cek batas stok)
        ├─ Jika belum: push item baru ke array cart[]
        └─ updateCartUI() → render ulang sidebar keranjang

── FASE PEMBAYARAN ──────────────────────────────────────────────────────
    │
    ├─ User isi #cashAmount → tombol #btnPay aktif
    │
    └─ User klik "BAYAR SEKARANG" → processPayment()
        ├─ Validasi lokal: cart tidak kosong, uang >= total
        └─ fetch POST → process/process_payment.php (JSON body)

── BACKEND: process/process_payment.php ─────────────────────────────────
    │
    ├─ Decode JSON input (items[], cashAmount)
    ├─ Validasi: items tidak kosong, cashAmount > 0
    │
    ├─ BEGIN TRANSACTION (InnoDB)
    │   ├─ Untuk setiap item:
    │   │   ├─ SELECT barang FOR UPDATE (row lock untuk concurrency safety)
    │   │   ├─ Cek stok mencukupi
    │   │   └─ Hitung subtotal, akumulasi totalHarga
    │   │
    │   ├─ Cek cashAmount >= totalHarga
    │   ├─ INSERT INTO transaksi (total_harga, uang_bayar) → dapat id_transaksi baru
    │   ├─ Untuk setiap item:
    │   │   ├─ INSERT INTO detail_transaksi
    │   │   └─ UPDATE barang SET stok = stok - qty
    │   │
    │   └─ COMMIT
    │
    ├─ Return JSON: {success: true, data: {id_transaksi, kembalian, items[]}}
    └─ Jika error → ROLLBACK, return JSON error

── FRONTEND setelah sukses ──────────────────────────────────────────────
    │
    ├─ Update data-stock pada kartu produk (tanpa reload)
    ├─ Kosongkan cart[]
    └─ showSuksesModal(data) → tampilkan ID transaksi, total, kembalian
        └─ Link "Cetak Nota PDF" → process/cetak_nota.php?id=...
```

---

### 4. Riwayat Transaksi

```
[Browser] → GET transaksi.php (opsional: ?tgl_dari=...&tgl_sampai=...)
    │
    ├─ Filter tanggal dari GET parameter (prepared statement jika ada filter)
    ├─ Query summary: COUNT + SUM total_harga (sesuai filter)
    ├─ Query list: SELECT transaksi ORDER BY tgl_transaksi DESC
    │
    └─ Render:
        ├─ 2 kartu ringkasan (Total Pendapatan, Jumlah Transaksi)
        ├─ Form filter tanggal (GET form)
        └─ Tabel transaksi + tombol Detail & Nota

[User klik Detail] → showDetail(idTransaksi) [app.js]
    └─ fetch GET → process/get_detail_transaksi.php?id_transaksi=X
        ├─ SELECT transaksi WHERE id = X
        ├─ SELECT detail_transaksi JOIN barang WHERE id_transaksi = X
        └─ Return JSON → render ke dalam modal #detailModal
```

---

### 5. Manajemen Gudang & Barang

```
[Browser] → GET gudang.php
    │
    ├─ require_login() ← semua role bisa akses
    ├─ is_admin() → mengontrol tombol tambah/edit/hapus barang & gudang
    │
    ├─ Query statistik: total jenis barang, total unit stok, nilai aset
    ├─ Query barang: SELECT barang JOIN gudang
    └─ Query gudang: SELECT semua gudang

── AKSI BARANG (Admin) ────────────────────────────────────────────────
    │
    ├─ Tambah: openModalTambahBarang() → form POST → barang_action.php?action=tambah
    │   └─ INSERT INTO barang, redirect gudang.php?msg=tambah_barang_ok
    │
    ├─ Edit: openModalEditBarang(...) → form POST → barang_action.php?action=edit
    │   └─ UPDATE barang WHERE id = ?, redirect gudang.php?msg=edit_barang_ok
    │
    └─ Hapus: confirmHapusBarang() → confirm() → form POST → barang_action.php?action=hapus
        └─ DELETE FROM barang WHERE id = ?

── AKSI GUDANG (Admin) ────────────────────────────────────────────────
    │
    ├─ Tambah/Edit: serupa dengan barang, via gudang_action.php
    └─ Hapus:
        ├─ Cek dulu: SELECT COUNT(*) FROM barang WHERE id_gudang = ?
        ├─ Jika masih ada barang → redirect msg=gudang_ada_barang (gagal)
        └─ Jika kosong → DELETE FROM gudang

── UPDATE STOK (Semua Role) ────────────────────────────────────────────
    │
    ├─ Klik tombol "+ Stok" → openStockModal(id, nama, stokSekarang) [app.js]
    ├─ Pilih aksi: Set Langsung / Tambah (+) / Kurangi (-)
    └─ processStockAction(action) → fetch POST → update_stock_action.php
        ├─ Hitung stok_baru berdasarkan action
        ├─ UPDATE barang SET stok = stok_baru WHERE id = ?
        ├─ Return JSON
        └─ Frontend update badge stok & statistik tanpa reload halaman
```

---

### 6. Kelola Kasir (Admin Only)

```
[Browser] → GET kelola_kasir.php
    │
    ├─ require_role('admin') ← redirect jika bukan admin
    ├─ Query: SELECT users WHERE role = 'kasir'
    └─ Render form tambah/edit + tabel daftar kasir

[Tambah Kasir] → POST kasir_action.php?action=tambah
    ├─ password_hash($password, PASSWORD_DEFAULT)
    ├─ INSERT INTO users (nama, username, password, role='kasir')
    ├─ Error 1062 (duplicate username) → redirect msg=duplikat
    └─ Sukses → redirect msg=tambah_ok

[Edit Kasir] → GET kelola_kasir.php?aksi=edit&id=X
    ├─ Prefill form dengan data kasir yang dipilih
    └─ POST kasir_action.php?action=edit
        ├─ Jika password diisi: UPDATE dengan hash baru
        └─ Jika password kosong: UPDATE nama & username saja

[Hapus Kasir] → confirm() → POST kasir_action.php?action=hapus
    ├─ Cegah admin hapus dirinya sendiri (cek id_user = SESSION id)
    └─ DELETE FROM users WHERE id_user = ? AND role = 'kasir'
```

---

### 7. Cetak Nota

```
[Browser] → GET process/cetak_nota.php?id=X
    │
    ├─ require_login() ← cek sesi
    ├─ SELECT transaksi WHERE id_transaksi = X
    ├─ SELECT detail_transaksi JOIN barang WHERE id_transaksi = X
    │
    └─ Render halaman HTML print-friendly (tanpa navbar, layout nota kasir)
        ├─ Ukuran 320px (simulasi kertas struk)
        ├─ @media print: hilangkan tombol, perlebar ke 100%
        └─ Tombol "Cetak Nota" → window.print()
```

---

## Sistem Kontrol Akses (Role-Based Access)

Kontrol akses diimplementasikan melalui dua fungsi di `config/session.php`:

```php
require_login(string $redirectTo)
// Cek $_SESSION['username'] → redirect jika belum login

require_role($roles, string $redirect)
// Cek $_SESSION['role'] ada di array yang diizinkan
// Memanggil require_login() terlebih dahulu
// Contoh: require_role('admin') pada halaman admin-only
```

| Halaman / Endpoint | require_login | require_role |
|---|:---:|:---:|
| `dashboard.php` | ✅ | — |
| `kasir.php` | ✅ | — |
| `transaksi.php` | ✅ | — |
| `gudang.php` | ✅ | — |
| `kelola_kasir.php` | (via require_role) | `admin` |
| `process/barang_action.php` | (via require_role) | `admin` |
| `process/gudang_action.php` | (via require_role) | `admin` |
| `process/kasir_action.php` | (via require_role) | `admin` |
| `process/process_payment.php` | ✅ (via session check) | — |
| `process/get_detail_transaksi.php` | ✅ (via session check) | — |
| `process/update_stock_action.php` | ✅ (via session check) | — |
| `process/cetak_nota.php` | ✅ | — |

---

## API Endpoints (JSON)

Tiga endpoint berikut menerima/mengembalikan JSON dan dipanggil via `fetch()` dari JavaScript:

### `POST process/process_payment.php`
**Request body:**
```json
{
  "cashAmount": 50000,
  "items": [
    { "id": 1, "qty": 2 },
    { "id": 3, "qty": 1 }
  ]
}
```
**Response sukses:**
```json
{
  "success": true,
  "data": {
    "id_transaksi": 42,
    "total_harga": 11000,
    "uang_bayar": 50000,
    "kembalian": 39000,
    "items": [{"id": 1, "qty": 2, "stock": 118}]
  }
}
```

### `GET process/get_detail_transaksi.php?id_transaksi=X`
**Response:**
```json
{
  "success": true,
  "transaksi": { "id_transaksi": 42, "tgl_transaksi": "...", "total_harga": 11000, "uang_bayar": 50000, "kembalian": 39000 },
  "items": [
    { "nama_barang": "Indomie Goreng", "qty": 2, "harga_satuan": 3500, "subtotal": 7000 }
  ]
}
```

### `POST process/update_stock_action.php`
**Request body:**
```json
{ "id_barang": 1, "stok_baru": 150 }
```
**Response:**
```json
{
  "success": true,
  "data": { "id": 1, "nama_barang": "Indomie Goreng", "stok_lama": 120, "stok": 150 }
}
```

---

## Cara Instalasi & Menjalankan

### Prasyarat
- PHP 8.0+
- MySQL / MariaDB
- Web server (Apache/Nginx) atau XAMPP/Laragon

### Langkah-langkah

1. **Clone / copy** folder project ke direktori web server (misal: `htdocs/KlikKasir`)

2. **Import database:**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Konfigurasi koneksi database** di `config/database.php`:
   ```php
   $host     = "localhost";
   $username = "root";
   $password = "";
   $database = "kasir_penjualan";
   ```
   > ⚠️ Untuk production, pindahkan kredensial ke file `.env` dan jangan commit ke Git.

4. **Jalankan** melalui browser: `http://localhost/KlikKasir/`

---

## Akun Default

| Nama | Username | Password | Role |
|---|---|---|---|
| Administrator | `admin` | `admin123` | Admin |
| Kasir Utama | `kasir1` | `kasir123` | Kasir |

> ⚠️ **Penting:** Password di `schema.sql` tersimpan sebagai plain-text. Setelah pertama kali login, disarankan admin segera mengubah password melalui halaman Kelola Kasir, atau gunakan `password_hash()` secara manual untuk seed data production.

---

## Catatan Keamanan

- Semua output ke HTML menggunakan `h()` (wrapper `htmlspecialchars`) → **cegah XSS**
- Semua query database menggunakan **prepared statements** → **cegah SQL Injection**
- Password user disimpan dengan **`password_hash()`** (bcrypt)
- Session di-regenerate setelah login (`session_regenerate_id(true)`) → **cegah session fixation**
- Hapus akun admin tidak bisa dilakukan melalui UI (dibatasi oleh filter `role = 'kasir'`)
