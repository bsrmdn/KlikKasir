<?php

$pageTitle = $pageTitle ?? 'Kasir Toko';
$bodyClass = $bodyClass ?? 'min-h-screen bg-slate-50 text-slate-800';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="/klikkasir/KlikKasir/assets/favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="<?= h($bodyClass) ?>">