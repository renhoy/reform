<?php
// {"_META_file_path_": "src/views/templates/header.php"}
// Header reutilizable

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="header">
    <div class="header-content">
        <div class="logo">Generador de Presupuestos</div>
        <nav class="main-nav">
            <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="tariffs.php" class="nav-item <?= $currentPage === 'tariffs.php' ? 'active' : '' ?>">Tarifas</a>
            <a href="budgets.php" class="nav-item <?= $currentPage === 'budgets.php' ? 'active' : '' ?>">Presupuestos</a>
        </nav>
        <div class="user-menu">
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
</div>