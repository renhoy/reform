<?php
// {"_META_file_path_": "refor/includes/header.php"}
// Cabecera común para todas las páginas

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Generador de Presupuestos');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= PAGE_TITLE ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= asset('css/' . $css . '.css') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            
            <nav class="main-nav">
                <a href="dashboard.php" class="nav-item <?= isCurrentPage('dashboard') ? 'active' : '' ?>">Dashboard</a>
                <a href="tariffs.php" class="nav-item <?= isCurrentPage('tariffs') ? 'active' : '' ?>">Tarifas</a>
                <a href="budgets.php" class="nav-item <?= isCurrentPage('budgets') ? 'active' : '' ?>">Presupuestos</a>
            </nav>
            
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>
    
    <main class="main-content"><?php // El contenido de la página se insertará aquí ?>