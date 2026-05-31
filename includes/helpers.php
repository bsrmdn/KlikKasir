<?php

/**
 * Escape output untuk mencegah XSS (Cross-site Scripting).
 *
 * Mengonversi nilai apa pun menjadi string lalu men-escape karakter khusus HTML
 * sehingga tidak dieksekusi sebagai markup atau skrip ketika ditampilkan di
 * halaman. Gunakan helper ini saat menampilkan data yang berasal dari pengguna
 * atau dari database.
 *
 * @param mixed $value Nilai yang akan di-escape.
 * @return string Nilai yang sudah di-escape untuk output HTML.
 */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
