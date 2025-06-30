<?php
// {"_META_file_path_": "includes/header.php"}
// Cabecera común para todas las páginas

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<div class="header">
    <div class="header-content">
        <div class="logo">Generador de Presupuestos</div>
        
        <nav class="main-nav">
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="tariffs.php" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['tariffs.php', 'upload-tariff.php', 'edit-tariff.php']) ? 'active' : '' ?>">Tarifas</a>
            <a href="budgets.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'budgets.php' ? 'active' : '' ?>">Presupuestos</a>
        </nav>
        
        <div class="user-menu">
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
</div>