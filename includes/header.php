<?php
// {"_META_file_path_": "refor/includes/header.php"}
// Cabecera común para todas las páginas

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="header">
    <div class="header-content">
        <div class="logo">Generador de Presupuestos</div>
        
        <nav class="main-nav">
            <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="tariffs.php" class="nav-item <?= $currentPage === 'tariffs' ? 'active' : '' ?>">Tarifas</a>
            <a href="budgets.php" class="nav-item <?= $currentPage === 'budgets' ? 'active' : '' ?>">Presupuestos</a>
        </nav>
        
        <div class="user-menu">
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
</div>