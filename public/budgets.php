<?php
// {"_META_file_path_": "public/budgets.php"}
// Presupuestos directo y simple

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();
$budgets = $pdo->query("
    SELECT b.*, t.name as tariff_name 
    FROM budgets b 
    LEFT JOIN tariffs t ON b.tariff_id = t.id 
    ORDER BY b.created_at DESC 
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tariffs-styles.css') ?>">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            <nav class="main-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="tariffs.php" class="nav-item">Tarifas</a>
                <a href="budgets.php" class="nav-item active">Presupuestos</a>
            </nav>
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>Presupuestos Generados</h1>
        </div>

        <?php if (empty($budgets)): ?>
            <div class="empty-state">
                <h2>No hay presupuestos</h2>
                <p>Crea tu primera tarifa para generar presupuestos</p>
                <a href="tariffs.php" class="btn btn-primary">Ver Tarifas</a>
            </div>
        <?php else: ?>
            <div class="tariffs-table">
                <div class="table-header">
                    <div>Cliente / Tarifa</div>
                    <div>Estado</div>
                    <div>Fecha</div>
                </div>
                
                <?php foreach ($budgets as $budget): ?>
                    <?php $client = json_decode($budget['client_data'], true); ?>
                    <div class="table-row">
                        <div class="tariff-info">
                            <div class="tariff-name"><?= htmlspecialchars($client['name'] ?? 'Sin nombre') ?></div>
                            <small>Tarifa: <?= htmlspecialchars($budget['tariff_name']) ?></small>
                        </div>
                        <div class="tariff-date">
                            <span class="incomplete-badge"><?= ucfirst($budget['status']) ?></span>
                        </div>
                        <div class="tariff-date">
                            <?= date('d/m/Y H:i', strtotime($budget['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>