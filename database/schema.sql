CREATE DATABASE IF NOT EXISTS kasir_penjualan
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kasir_penjualan;

-- Tabel Users (Login & Role)
CREATE TABLE IF NOT EXISTS users (
  id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed users: jalankan setup_users.php sekali untuk generate hash yang benar
-- admin: admin123 | kasir1: kasir123
INSERT INTO users (nama, username, password, role) VALUES
  ('Administrator','admin','$2y$10$placeholder_run_setup_users','admin'),
  ('Kasir Utama','kasir1','$2y$10$placeholder_run_setup_users','kasir')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

CREATE TABLE IF NOT EXISTS gudang (
  id_gudang INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_gudang VARCHAR(100) NOT NULL,
  lokasi VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barang (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_barang VARCHAR(150) NOT NULL,
  kategori VARCHAR(100) NOT NULL,
  harga DECIMAL(12,2) NOT NULL DEFAULT 0,
  stok INT UNSIGNED NOT NULL DEFAULT 0,
  id_gudang INT UNSIGNED NOT NULL,
  CONSTRAINT fk_barang_gudang
    FOREIGN KEY (id_gudang)
    REFERENCES gudang (id_gudang)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi (
  id_transaksi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tgl_transaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
  uang_bayar DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detail_transaksi (
  id_detail INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT UNSIGNED NOT NULL,
  id INT UNSIGNED NOT NULL,
  jumlah INT UNSIGNED NOT NULL DEFAULT 1,
  harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_detail_transaksi_transaksi
    FOREIGN KEY (id_transaksi)
    REFERENCES transaksi (id_transaksi)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_detail_transaksi_barang
    FOREIGN KEY (id)
    REFERENCES barang (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO gudang (id_gudang, nama_gudang, lokasi) VALUES
  (1, 'Gudang Utama', 'Jl. Melati No. 10'),
  (2, 'Gudang Barat', 'Jl. Kenanga No. 21')
ON DUPLICATE KEY UPDATE
  nama_gudang = VALUES(nama_gudang),
  lokasi = VALUES(lokasi);

INSERT INTO barang (id, nama_barang, kategori, harga, stok, id_gudang) VALUES
  (1, 'Indomie Goreng', 'Makanan', 3500.00, 120, 1),
  (2, 'Aqua 600ml', 'Minuman', 4000.00, 75, 1),
  (3, 'Beras 5kg', 'Sembako', 65000.00, 15, 2),
  (4, 'Sabun Lifebuoy', 'Kebersihan', 4500.00, 50, 2),
  (5, 'Pasta Gigi', 'Kebersihan', 12000.00, 8, 1),
  (6, 'Kopi Sachet', 'Minuman', 2500.00, 40, 1),
  (7, 'Minyak Goreng 1L', 'Sembako', 18000.00, 25, 2)
ON DUPLICATE KEY UPDATE
  nama_barang = VALUES(nama_barang),
  kategori = VALUES(kategori),
  harga = VALUES(harga),
  stok = VALUES(stok),
  id_gudang = VALUES(id_gudang);

INSERT INTO transaksi (id_transaksi, tgl_transaksi, total_harga, uang_bayar) VALUES
  (1, '2026-05-30 09:15:00', 11000.00, 20000.00)
ON DUPLICATE KEY UPDATE
  tgl_transaksi = VALUES(tgl_transaksi),
  total_harga = VALUES(total_harga),
  uang_bayar = VALUES(uang_bayar);

INSERT INTO detail_transaksi (id_detail, id_transaksi, id, jumlah, harga_satuan, subtotal) VALUES
  (1, 1, 1, 2, 3500.00, 7000.00),
  (2, 1, 2, 1, 4000.00, 4000.00)
ON DUPLICATE KEY UPDATE
  id_transaksi = VALUES(id_transaksi),
  id = VALUES(id),
  jumlah = VALUES(jumlah),
  harga_satuan = VALUES(harga_satuan),
  subtotal = VALUES(subtotal);
