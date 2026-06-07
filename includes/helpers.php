<?php
/**
 * includes/helpers.php
 *
 * Kumpulan fungsi utilitas yang digunakan di seluruh aplikasi.
 * File ini di-include oleh setiap halaman yang menampilkan data ke HTML.
 */

/**
 * Escape output untuk mencegah XSS (Cross-site Scripting).
 *
 * Mengonversi nilai apa pun menjadi string lalu men-escape karakter khusus HTML
 * sehingga tidak dieksekusi sebagai markup atau skrip ketika ditampilkan di
 * halaman. Gunakan helper ini SELALU saat menampilkan data yang berasal dari
 * pengguna atau dari database.
 *
 * Contoh penggunaan:
 *   echo h($nama_barang);          // aman untuk output ke HTML
 *   echo h($_GET['search']);        // aman untuk output nilai dari URL
 *
 * @param  mixed  $value  Nilai yang akan di-escape (string, int, float, dll.)
 * @return string         Nilai yang sudah di-escape dan aman untuk output HTML.
 */
function h($value)
{
    // ENT_QUOTES: escape baik single quote (') maupun double quote (")
    // 'UTF-8': gunakan encoding UTF-8 agar karakter Indonesia aman
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
