<?php
// {"_META_file_path_": "refor/dashboard.php"}
// Dashboard principal - mantiene diseño exacto del original

require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';
require_once 'includes/budget-helpers.php';

requireAuth();

// Obtener estadísticas
$pdo = getConnection();
$user_id = $_SESSION['user_id'];

$tariffsCount = $pdo->prepare("SELECT COUNT(*) FROM tariffs WHERE user_id = ?");
$tariffsCount->execute([$user_id]);
$tariffsCount = $tariffsCount->fetchColumn();

$budgetsCount = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ?");
$budgetsCount->execute([$user_id]);
$budgetsCount = $budgetsCount->fetchColumn();

$recentBudgets = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recentBudgets->execute([$user_id]);
$recentBudgets = $recentBudgets->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            <nav class="main-nav">
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
                <a href="tariffs.php" class="nav-item">Tarifas</a>
                <a href="budgets.php" class="nav-item">Presupuestos</a>
            </nav>
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Línea 1: Título -->
        <h1 class="page-title">Dashboard</h1>

        <!-- Línea 2: Estadísticas -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?= $tariffsCount ?></div>
                <div class="stat-label">Tarifas Creadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $budgetsCount ?></div>
                <div class="stat-label">Presupuestos Generados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $recentBudgets ?></div>
                <div class="stat-label">Esta Semana</div>
            </div>
        </div>

        <!-- Línea 3: Acciones -->
        <div class="quick-actions">
            <div class="action-card">
                <div class="card-icon">
                    <i data-lucide="folder" class="action-icon orange"></i>
                </div>
                <h3>Tarifas</h3>
                <p>Crea, edita y organiza tus tarifas de precios</p>
                <a href="tariffs.php" class="btn btn-secondary">Ir a Tarifas</a>
            </div>

            <div class="action-card">
                <div class="card-icon">
                    <i data-lucide="file-text" class="action-icon green"></i>
                </div>
                <h3>Presupuestos</h3>
                <p>Consulta todos los presupuestos generados</p>
                <a href="budgets.php" class="btn btn-primary">Ir a Presupuestos</a>
            </div>

            <div class="action-card">
                <div class="card-icon">
                    <i data-lucide="plus-circle" class="action-icon orange"></i>
                </div>
                <h3>Crear Nueva Tarifa</h3>
                <p>Comienza creando una nueva tarifa de precios</p>
                <a href="tariff-form.php" class="btn btn-secondary">Nueva Tarifa</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>