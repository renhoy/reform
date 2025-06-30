<?php
// {"_META_file_path_": "src/views/templates/header.php"}
// Cabecera común actualizada con nuevas rutas

$currentPage = basename($_SERVER['REQUEST_URI']);
?>
<div class="header">
    <div class="header-content">
        <div class="logo">Generador de Presupuestos</div>
        
        <nav class="main-nav">
            <a href="<?= url('dashboard') ?>" class="nav-item <?= in_array($currentPage, ['', 'dashboard']) ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= url('tariffs') ?>" class="nav-item <?= strpos($currentPage, 'tariff') !== false ? 'active' : '' ?>">Tarifas</a>
            <a href="<?= url('budgets') ?>" class="nav-item <?= strpos($currentPage, 'budget') !== false ? 'active' : '' ?>">Presupuestos</a>
        </nav>
        
        <div class="user-menu">
            <a href="<?= url('logout') ?>" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
</div>