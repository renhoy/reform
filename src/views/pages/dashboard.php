<?php
// {"_META_file_path_": "src/views/pages/dashboard.php"}
// Vista del dashboard

requireAuth();

$pdo = getConnection();
$tariffsCount = $pdo->query("SELECT COUNT(*) FROM tariffs WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();
$budgetsCount = $pdo->query("SELECT COUNT(*) FROM budgets WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();
$recentBudgets = $pdo->query("SELECT COUNT(*) FROM budgets WHERE user_id = " . $_SESSION['user_id'] . " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Generador de Presupuestos</title>
    <link rel="stylesheet" href="assets/css/header-styles.css">
    <link rel="stylesheet" href="assets/css/dashboard-styles.css">
</head>
<body>
    <?php include SRC_PATH . '/views/templates/header.php'; ?>

    <div class="container">
        <div class="welcome-section">
            <h1>Bienvenido al Generador de Presupuestos</h1>
            <p>Gestiona tus tarifas y genera presupuestos profesionales de forma rápida y sencilla.</p>
        </div>

        <div class="quick-actions">
            <div class="action-card">
                <div class="card-icon">📊</div>
                <h3>Gestionar Tarifas</h3>
                <p>Crea, edita y organiza tus tarifas de precios</p>
                <a href="tariffs.php" class="btn btn-primary">Ir a Tarifas</a>
            </div>

            <div class="action-card">
                <div class="card-icon">📋</div>
                <h3>Ver Presupuestos</h3>
                <p>Consulta todos los presupuestos generados</p>
                <a href="budgets.php" class="btn btn-secondary">Ver Presupuestos</a>
            </div>

            <div class="action-card">
                <div class="card-icon">✨</div>
                <h3>Crear Nueva Tarifa</h3>
                <p>Comienza creando una nueva tarifa de precios</p>
                <a href="upload-tariff.php" class="btn btn-info">Nueva Tarifa</a>
            </div>
        </div>

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
    </div>
</body>
</html>