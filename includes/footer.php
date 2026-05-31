<?php foreach ($pageScripts ?? [] as $script): ?>
    <script src="<?= h($script) ?>"></script>
<?php endforeach; ?>
</body>

</html>