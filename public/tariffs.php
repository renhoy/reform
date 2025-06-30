<?php
// {"_META_file_path_": "public/tariffs.php"}
// Tarifas directo y simple

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();
$tariffs = $pdo->query("
    SELECT * FROM tariffs 
    ORDER BY created_at DESC
")->fetchAll();

function isComplete($tariff) {
    return !empty($tariff['name']) && !empty($tariff['nif']) && 
           !empty($tariff['address']) && !empty($tariff['contact']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tariffs-styles.css') ?>">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            <nav class="main-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="tariffs.php" class="nav-item active">Tarifas</a>
                <a href="budgets.php" class="nav-item">Presupuestos</a>
            </nav>
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div

    <div class="container">
        <div class="page-header">
            <h1>Gestión de Tarifas</h1>
        </div>

        <?php if (isset($_GET['duplicated'])): ?>
            <div class="alert alert-success">Tarifa duplicada correctamente</div>
        <?php endif; ?>

        <div class="actions-bar">
            <a href="upload-tariff.php" class="btn btn-primary">Crear Tarifa</a>
            <a href="budgets.php" class="btn btn-secondary">Ver Presupuestos</a>
        </div>

        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <h2>No hay tarifas disponibles</h2>
                <p>Crea tu primera tarifa para comenzar</p>
                <a href="upload-tariff.php" class="btn btn-primary">Crear Primera Tarifa</a>
            </div>
        <?php else: ?>
            <div class="tariffs-table">
                <div class="table-header">
                    <div>Nombre de Tarifa</div>
                    <div>Fecha</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($tariffs as $tariff): ?>
                    <?php $complete = isComplete($tariff); ?>
                    <div class="table-row">
                        <div class="tariff-info">
                            <div class="tariff-name"><?= htmlspecialchars($tariff['title']) ?></div>
                            <?php if (!$complete): ?>
                                <span class="incomplete-badge">Incompleta</span>
                            <?php endif; ?>
                        </div>
                        <div class="tariff-date">
                            <?= date('d/m/Y H:i', strtotime($tariff['created_at'])) ?>
                        </div>
                        <div class="tariff-actions">
                            <?php if ($complete): ?>
                                <a href="form.php?tariff_id=<?= $tariff['id'] ?>" class="btn btn-primary btn-small">Generar Presupuesto</a>
                            <?php endif; ?>
                            <a href="edit-tariff.php?id=<?= $tariff['id'] ?>" class="btn btn-secondary btn-small">Editar</a>
                            <a href="duplicate-tariff.php?id=<?= $tariff['id'] ?>" class="btn btn-info btn-small" 
                               onclick="return confirm('¿Duplicar esta tarifa?')">Duplicar</a>
                            <a href="delete-tariff.php?id=<?= $tariff['id'] ?>" class="btn btn-danger btn-small" 
                               onclick="return confirm('¿Eliminar esta tarifa?')">Borrar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>