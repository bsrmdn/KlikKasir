<?php
/**
 * includes/footer.php
 *
 * Template penutup HTML yang di-include di bagian bawah setiap halaman.
 * Tugasnya adalah:
 *   1. Meng-inject script JavaScript tambahan yang didaftarkan halaman via $pageScripts
 *   2. Menutup tag </body> dan </html>
 *
 * Variabel yang dibaca:
 *   $pageScripts  (array) — daftar path file .js yang akan di-inject sebelum </body>
 *                           Contoh: $pageScripts = ['assets/js/app.js'];
 *
 * Meng-inject script di bagian akhir body memastikan DOM sudah selesai dimuat
 * sebelum script dieksekusi (tidak perlu defer/async untuk script global).
 */
?>
<?php foreach ($pageScripts ?? [] as $script): ?>
    <!-- Script tambahan yang didaftarkan oleh halaman pemanggil -->
    <script src="<?= h($script) ?>"></script>
<?php endforeach; ?>
</body>

</html>