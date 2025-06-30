<?php
// {"_META_file_path_": "src/views/templates/base.php"}
// Plantilla base para todas las páginas
?>
<!DOCTYPE html>
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
    <!-- Header integrado -->
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            
            <nav class="main-nav">
                <a href="<?= url('dashboard') ?>" class="nav-item">Dashboard</a>
                <a href="<?= url('tariffs') ?>" class="nav-item">Tarifas</a>
                <a href="<?= url('budgets') ?>" class="nav-item">Presupuestos</a>
            </nav>
            
            <div class="user-menu">
                <a href="<?= url('logout') ?>" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>
    
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