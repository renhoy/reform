<?php
// {"_META_file_path_": "public/budget-success.php"}
// Página de éxito del presupuesto

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$uuid = $_GET['uuid'] ?? null;
if (!$uuid) {
    header('Location: budgets.php');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT b.*, t.name as tariff_name, c.name as company_name
    FROM budgets b 
    LEFT JOIN tariffs t ON b.tariff_id = t.id 
    LEFT JOIN company_config c ON t.id = c.tariff_id 
    WHERE b.uuid = ?
");
$stmt->execute([$uuid]);
$budget = $stmt->fetch();

if (!$budget) {
    header('Location: budgets.php');
    exit;
}

$client_data = json_decode($budget['client_data'], true);
$budget_data = json_decode($budget['budget_data'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto Generado - <?= $uuid ?></title>
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tariffs-styles.css') ?>">
    <style>
        .success-container { max-width: 800px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .success-header { text-align: center; color: #109c61; margin-bottom: 2rem; }
        .budget-info { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .info-section h3 { color: #e8951c; margin-bottom: 1rem; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .items-table th, .items-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .totals-summary { background: #f8f9fa; padding: 1rem; border-radius: 4px; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .total-final { font-weight: bold; font-size: 1.2rem; color: #e8951c; border-top: 2px solid #e8951c; padding-top: 0.5rem; }
    </style>
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

    <div class="success-container">
        <div class="success-header">
            <h1>✅ Presupuesto Generado Correctamente</h1>
            <p>UUID: <strong><?= htmlspecialchars($uuid) ?></strong></p>
        </div>

        <div class="budget-info">
            <div class="info-section">
                <h3>Datos del Cliente</h3>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($client_data['name']) ?></p>
                <p><strong>NIF/NIE:</strong> <?= htmlspecialchars($client_data['nif_nie']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($client_data['email']) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($client_data['phone']) ?></p>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($client_data['address']) ?></p>
            </div>
            
            <div class="info-section">
                <h3>Información del Presupuesto</h3>
                <p><strong>Empresa:</strong> <?= htmlspecialchars($budget['company_name']) ?></p>
                <p><strong>Tarifa:</strong> <?= htmlspecialchars($budget['tariff_name']) ?></p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($budget['created_at'])) ?></p>
                <p><strong>Estado:</strong> <?= ucfirst($budget['status']) ?></p>
            </div>
        </div>

        <?php if (!empty($budget_data['items'])): ?>
            <h3>Partidas del Presupuesto</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>PVP</th>
                        <th>IVA %</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budget_data['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= number_format($item['quantity'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($item['unit']) ?></td>
                            <td><?= number_format($item['pvp'], 2, ',', '.') ?> €</td>
                            <td><?= number_format($item['iva_rate'], 2, ',', '.') ?>%</td>
                            <td><?= number_format($item['total'], 2, ',', '.') ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="totals-summary">
            <div class="total-line">
                <span>Base Imponible:</span>
                <span><?= number_format($budget_data['totals']['base'], 2, ',', '.') ?> €</span>
            </div>
            
            <?php foreach ($budget_data['totals']['iva_breakdown'] as $rate => $amount): ?>
                <div class="total-line">
                    <span>IVA <?= number_format($rate, 2, ',', '.') ?>%:</span>
                    <span><?= number_format($amount, 2, ',', '.') ?> €</span>
                </div>
            <?php endforeach; ?>
            
            <div class="total-line total-final">
                <span>TOTAL PRESUPUESTO:</span>
                <span><?= number_format($budget_data['totals']['final'], 2, ',', '.') ?> €</span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="budgets.php" class="btn btn-primary">Ver Todos los Presupuestos</a>
            <a href="tariffs.php" class="btn btn-secondary" style="margin-left: 1rem;">Volver a Tarifas</a>
        </div>
    </div>
</body>
</html>