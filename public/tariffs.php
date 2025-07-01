<?php
// {"_META_file_path_": "public/tariffs.php"}
// Página tarifas rediseñada con iconos

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();

// Filtros
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$access_filter = $_GET['access'] ?? '';
$author_filter = $_GET['author'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Construir consulta con filtros
$where_conditions = ["t.user_id = " . $_SESSION['user_id']];
$params = [];

if ($search) {
    $where_conditions[] = "t.title LIKE ?";
    $params[] = "%$search%";
}

if ($status_filter) {
    if ($status_filter === 'complete') {
        $where_conditions[] = "(t.name IS NOT NULL AND t.nif IS NOT NULL AND t.address IS NOT NULL AND t.contact IS NOT NULL)";
    } elseif ($status_filter === 'incomplete') {
        $where_conditions[] = "(t.name IS NULL OR t.nif IS NULL OR t.address IS NULL OR t.contact IS NULL)";
    }
}

if ($access_filter) {
    $where_conditions[] = "t.access = ?";
    $params[] = $access_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$tariffs = $pdo->prepare("
    SELECT t.*, u.name as author_name,
           (SELECT COUNT(*) FROM budgets b WHERE b.json_tariff_data LIKE CONCAT('%\"uuid\":\"', t.uuid, '\"%')) as budget_count
    FROM tariffs t 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE $where_clause
    ORDER BY t.created_at DESC
");
$tariffs->execute($params);
$tariffs = $tariffs->fetchAll();

function isComplete($tariff) {
    return !empty($tariff['name']) && 
           !empty($tariff['nif']) && 
           !empty($tariff['address']) && 
           !empty($tariff['contact']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/common-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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
    </div>

    <div class="container">
        <!-- Línea 1: Título y botones -->
        <div class="page-header-row">
            <h1 class="page-title">Tarifas</h1>
            <div class="header-buttons">
                <a href="upload-tariff.php" class="btn btn-secondary">Crear Tarifas</a>
                <a href="templates.php" class="btn btn-secondary">Plantillas</a>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Tarifa creada correctamente</div>
        <?php endif; ?>

        <!-- Línea 3: Filtros -->
        <div class="filters-row">
            <form method="GET" class="filters-form">
                <input type="text" name="search" placeholder="Buscar por nombre de tarifa..." value="<?= htmlspecialchars($search) ?>" class="filter-input">
                
                <select name="status" class="filter-select">
                    <option value="">Todos los estados</option>
                    <option value="complete" <?= $status_filter === 'complete' ? 'selected' : '' ?>>Completas</option>
                    <option value="incomplete" <?= $status_filter === 'incomplete' ? 'selected' : '' ?>>Incompletas</option>
                </select>
                
                <select name="access" class="filter-select">
                    <option value="">Todos los accesos</option>
                    <option value="private" <?= $access_filter === 'private' ? 'selected' : '' ?>>Privado</option>
                    <option value="public" <?= $access_filter === 'public' ? 'selected' : '' ?>>Público</option>
                </select>
                
                <input type="date" name="date_from" value="<?= $date_from ?>" class="filter-date">
                <input type="date" name="date_to" value="<?= $date_to ?>" class="filter-date">
                
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="tariffs.php" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <h2>No hay tarifas disponibles</h2>
                <p>Crea tu primera tarifa para comenzar</p>
                <a href="upload-tariff.php" class="btn btn-secondary">Crear Primera Tarifa</a>
            </div>
        <?php else: ?>
            <!-- Línea 4: Tabla -->
            <div class="data-table">
                <div class="table-header" style="grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1.5fr 1fr;">
                    <div>Nombre</div>
                    <div>Descripción</div>
                    <div>Estado</div>
                    <div>Acceso</div>
                    <div>Fecha</div>
                    <div>Autor</div>
                    <div>Presupuesto</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($tariffs as $tariff): ?>
                    <?php $complete = isComplete($tariff); ?>
                    <div class="table-row" style="grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1.5fr 1fr;">
                        <div class="tariff-info">
                            <div class="tariff-name"><?= htmlspecialchars($tariff['title']) ?></div>
                            <?php if (!$complete): ?>
                                <span class="status-badge status-incomplete">Incompleta</span>
                            <?php endif; ?>
                        </div>
                        
                        <div><?= htmlspecialchars($tariff['description'] ?? '') ?></div>
                        
                        <div>
                            <?php if ($complete): ?>
                                <select class="status-select" data-tariff-id="<?= $tariff['id'] ?>">
                                    <option value="active">Activa</option>
                                    <option value="inactive">Inactiva</option>
                                </select>
                            <?php else: ?>
                                <span class="status-badge status-incomplete">Incompleta</span>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <select class="access-select" data-tariff-id="<?= $tariff['id'] ?>">
                                <option value="private" <?= $tariff['access'] === 'private' ? 'selected' : '' ?>>Privado</option>
                                <option value="public" <?= $tariff['access'] === 'public' ? 'selected' : '' ?>>Público</option>
                            </select>
                        </div>
                        
                        <div><?= date('d/m/Y', strtotime($tariff['created_at'])) ?></div>
                        
                        <div><?= htmlspecialchars($tariff['author_name'] ?? 'Usuario') ?></div>
                        
                        <div class="budget-actions">
                            <?php if ($complete): ?>
                                <button class="btn-icon green btn-generate-budget" data-tariff-uuid="<?= $tariff['uuid'] ?>" title="Generar presupuesto">
                                    <i data-lucide="panel-top"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($tariff['budget_count'] > 0): ?>
                                <button class="btn-icon black btn-view-budgets" data-tariff-uuid="<?= $tariff['uuid'] ?>" title="Ver <?= $tariff['budget_count'] ?> presupuesto(s)">
                                    <i data-lucide="eye"></i>
                                    <span style="font-size: 10px; margin-left: 2px;">(<?= $tariff['budget_count'] ?>)</span>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-icon black btn-edit" data-type="tariff" data-id="<?= $tariff['id'] ?>" title="Editar">
                                <i data-lucide="pencil"></i>
                            </button>
                            <button class="btn-icon black btn-duplicate" data-type="tariff" data-id="<?= $tariff['id'] ?>" title="Duplicar">
                                <i data-lucide="copy"></i>
                            </button>
                            <button class="btn-icon red btn-delete" data-type="tariff" data-id="<?= $tariff['id'] ?>" title="Borrar">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="<?= asset('js/icon-buttons.js') ?>"></script>
</body>
</html>