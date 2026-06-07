<?php
/**
 * includes/header.php
 *
 * Template pembuka HTML yang di-include oleh setiap halaman utama.
 * Menyertakan CDN TailwindCSS dan Lucide Icons, serta mengatur judul halaman.
 *
 * Variabel yang dapat dikonfigurasi sebelum include:
 *   $pageTitle  (string) — judul halaman yang muncul di tab browser (default: 'Kasir Toko')
 *   $bodyClass  (string) — class CSS untuk tag <body> (default: 'min-h-screen bg-slate-50 text-slate-800')
 *
 * Catatan: helpers.php harus sudah di-include sebelum file ini agar fungsi h() tersedia.
 */

// Gunakan nilai default jika variabel belum diset oleh halaman pemanggil
$pageTitle = $pageTitle ?? 'Kasir Toko';
$bodyClass = $bodyClass ?? 'min-h-screen bg-slate-50 text-slate-800';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Judul halaman, di-escape untuk keamanan XSS -->
    <title><?= h($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="/klikkasir/KlikKasir/assets/favicon.png" />
    <!-- TailwindCSS via CDN untuk utility-first styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons via CDN untuk ikon SVG yang ringan dan konsisten -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<!-- Body dengan class yang dapat dikustomisasi per halaman -->
<body class="<?= h($bodyClass) ?>">