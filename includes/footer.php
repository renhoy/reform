<?php
// {"_META_file_path_": "refor/includes/footer.php"}
// Footer común para todas las páginas
?>
    </main>
    
    <!-- Scripts comunes -->
    <script src="<?= asset('js/main.js') ?>"></script>
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= asset('js/' . $js . '.js') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineJS)): ?>
        <script><?= $inlineJS ?></script>
    <?php endif; ?>
</body>
</html>