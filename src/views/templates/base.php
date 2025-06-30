<?php
// {"_META_file_path_": "src/views/templates/base.php"}
// Plantilla base para todas las páginas

requireAuth();
?>
<!DOCTYPE html>
<!-- {"_META_file_path_": "src/views/templates/base.php"} -->
<!-- Plantilla base para todas las páginas -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?>Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= asset('css/' . $style . '.css') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php include SRC_PATH . '/views/templates/header.php'; ?>
    
    <main>
        <?= $content ?? '' ?>
    </main>
    
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= asset('js/' . $script . '.js') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>