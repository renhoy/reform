<?php
// {"_META_file_path_": "public/tariffs.php"}
// Sistema completo de tarifas

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();

// Procesar cambios de estado/acceso
if ($_POST) {
    $tariffId = $_POST['tariff_id'] ?? null;
    $field = $_POST['field'] ?? null;
    $value = $_POST['value'] ?? null;
    
    if ($tariffId && $field && in_array($field, ['status', 'access'])) {
        $stmt = $pdo->prepare("UPDATE tariffs SET $field = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$value, $tariffId, $_SESSION['user_id']]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Obtener tarifas con contadores
$tariffs = $pdo->query("
    SELECT t.*, u.name as author_name,
           (SELECT COUNT(*) FROM budgets WHERE tariff_id = t.id) as budget_count
    FROM tariffs t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = {$_SESSION['user_id']}
    ORDER BY t.created_at DESC
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
    <link rel="stylesheet" href="assets/css/header-styles.css">
    <link rel="stylesheet" href="assets/css/tariffs-styles.css">
</head>
<body>
    <?php include SRC_PATH . '/views/templates/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Gestión de Tarifas</h1>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Tarifa guardada correctamente</div>
        <?php endif; ?>

        <div class="actions-bar">
            <a href="upload-tariff.php" class="btn btn-primary">Crear Tarifa</a>
            <a href="templates.php" class="btn btn-info">Plantillas</a>
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
                    <div>Nombre</div>
                    <div>Descripción</div>
                    <div>Estado</div>
                    <div>Fecha</div>
                    <div>Autor</div>
                    <div>Acceso</div>
                    <div>Contador</div>
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
                        
                        <div class="tariff-description">
                            <?= htmlspecialchars(substr($tariff['description'] ?? '', 0, 50)) ?>
                            <?= strlen($tariff['description'] ?? '') > 50 ? '...' : '' ?>
                        </div>
                        
                        <div class="status-cell">
                            <?php if ($complete): ?>
                                <select class="status-select" data-tariff-id="<?= $tariff['id'] ?>" data-field="status">
                                    <option value="active" <?= ($tariff['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Activa</option>
                                    <option value="inactive" <?= ($tariff['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactiva</option>
                                </select>
                            <?php else: ?>
                                <span class="status-pending">Pendiente</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tariff-date">
                            <?= date('d/m/Y', strtotime($tariff['created_at'])) ?>
                        </div>
                        
                        <div class="author-name">
                            <?= htmlspecialchars($tariff['author_name']) ?>
                        </div>
                        
                        <div class="access-cell">
                            <select class="access-select" data-tariff-id="<?= $tariff['id'] ?>" data-field="access">
                                <option value="private" <?= ($tariff['access'] ?? 'private') === 'private' ? 'selected' : '' ?>>Privado</option>
                                <option value="public" <?= ($tariff['access'] ?? 'private') === 'public' ? 'selected' : '' ?>>Público</option>
                            </select>
                        </div>
                        
                        <div class="counter-cell">
                            <?php if ($tariff['budget_count'] > 0): ?>
                                <button class="counter-btn" onclick="viewBudgets(<?= $tariff['id'] ?>)">
                                    <?= $tariff['budget_count'] ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tariff-actions">
                            <?php if ($complete): ?>
                                <a href="form.php?tariff_id=<?= $tariff['id'] ?>" class="btn btn-primary btn-small">Generar Presupuesto</a>
                            <?php endif; ?>
                            <a href="edit-tariff.php?id=<?= $tariff['id'] ?>" class="btn btn-secondary btn-small">Editar</a>
                            <button class="btn btn-info btn-small" onclick="duplicateTariff(<?= $tariff['id'] ?>)">Duplicar</button>
                            <button class="btn btn-danger btn-small" onclick="deleteTariff(<?= $tariff['id'] ?>)">Borrar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/tariffs-handler.js"></script>
</body>
</html>