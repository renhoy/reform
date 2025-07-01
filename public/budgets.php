<?php
// {"_META_file_path_": "public/budgets.php"}
// Página presupuestos rediseñada

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();

// Filtros
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$author_filter = $_GET['author'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$tariff_uuid = $_GET['tariff_uuid'] ?? '';

// Construir consulta
$where_conditions = ["b.user_id = " . $_SESSION['user_id']];
$params = [];

if ($search) {
    $where_conditions[] = "(b.client_name LIKE ? OR b.client_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($tariff_uuid) {
    $where_conditions[] = "b.json_tariff_data LIKE ?";
    $params[] = "%\"uuid\":\"$tariff_uuid\"%";
}

if ($date_from) {
    $where_conditions[] = "DATE(b.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(b.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$budgets = $pdo->prepare("
    SELECT b.*, u.name as author_name
    FROM budgets b 
    LEFT JOIN users u ON b.user_id = u.id
    WHERE $where_clause
    ORDER BY b.created_at DESC 
    LIMIT 50
");
$budgets->execute($params);
$budgets = $budgets->fetchAll();

// Estadísticas
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM budgets 
    WHERE user_id = ?
");
$stats->execute([$_SESSION['user_id']]);
$stats = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos - Generador de Presupuestos</title>
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
                <a href="tariffs.php" class="nav-item">Tarifas</a>
                <a href="budgets.php" class="nav-item active">Presupuestos</a>
            </nav>
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Línea 1: Título -->
        <div class="page-header-row">
            <h1 class="page-title">Presupuestos</h1>
        </div>

        <!-- Línea 2: Estadísticas -->
        <div class="stats-summary">
            Presupuestos Realizados (<?= $stats['total'] ?>): 
            👍 Aprobados (<?= $stats['approved'] ?>), 
            ❌ Rechazados (<?= $stats['rejected'] ?>), 
            📤 Enviados (<?= $stats['sent'] ?>), 
            ⏰ Expirados (<?= $stats['expired'] ?>), 
            ⏸️ Pendientes (<?= $stats['pending'] ?>), 
            ✍️ Borrador (<?= $stats['draft'] ?>)
        </div>

        <!-- Línea 3: Filtros -->
        <div class="filters-row">
            <form method="GET" class="filters-form">
                <input type="text" name="search" placeholder="Buscar por cliente..." value="<?= htmlspecialchars($search) ?>" class="filter-input">
                
                <select name="status" class="filter-select">
                    <option value="">Todos los estados</option>
                    <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Borrador</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="sent" <?= $status_filter === 'sent' ? 'selected' : '' ?>>Enviado</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                    <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Expirado</option>
                </select>
                
                <input type="date" name="date_from" value="<?= $date_from ?>" class="filter-date">
                <input type="date" name="date_to" value="<?= $date_to ?>" class="filter-date">
                
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="budgets.php" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

        <?php if (empty($budgets)): ?>
            <div class="empty-state">
                <h2>No hay presupuestos</h2>
                <p>Crea tu primera tarifa para generar presupuestos</p>
                <a href="tariffs.php" class="btn btn-secondary">Ver Tarifas</a>
            </div>
        <?php else: ?>
            <!-- Línea 4: Tabla -->
            <div class="data-table">
                <div class="table-header" style="grid-template-columns: 2fr 60px 1fr 1.5fr 1fr 1fr 1fr 2fr;">
                    <div>Cliente (NIF/NIE)</div>
                    <div>📝</div>
                    <div>Total</div>
                    <div>Tarifa</div>
                    <div>Estado</div>
                    <div>Fecha</div>
                    <div>Autor</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($budgets as $budget): ?>
                    <?php 
                    $tariff_data = json_decode($budget['json_tariff_data'], true);
                    $budget_data = json_decode($budget['json_budget_data'], true);
                    ?>
                    <div class="table-row" style="grid-template-columns: 2fr 60px 1fr 1.5fr 1fr 1fr 1fr 2fr;">
                        <div class="client-info">
                            <div><?= htmlspecialchars($budget['client_name']) ?></div>
                            <small>(<?= htmlspecialchars($budget['client_nif_nie']) ?>)</small>
                        </div>
                        
                        <div class="notes-column">
                            <button class="btn-icon black notes-btn" data-budget-id="<?= $budget['id'] ?>" title="Ver apuntes">
                                <i data-lucide="sticky-note"></i>
                            </button>
                        </div>
                        
                        <div class="total-column" title="Base: <?= number_format($budget['base'], 2, ',', '.') ?>€ | IVA: <?= number_format($budget['iva'], 2, ',', '.') ?>€ | Total: <?= number_format($budget['total'], 2, ',', '.') ?>€">
                            <?= number_format($budget['total'], 2, ',', '.') ?>€
                        </div>
                        
                        <div class="tariff-column">
                            <button class="btn btn-outline btn-small view-tariff" data-tariff-data='<?= htmlspecialchars(json_encode($tariff_data)) ?>'>Ver</button>
                            <small><?= htmlspecialchars($tariff_data['title'] ?? 'Sin título') ?></small>
                        </div>
                        
                        <div class="status-column">
                            <select class="status-select" data-budget-id="<?= $budget['id'] ?>">
                                <option value="draft" <?= $budget['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                                <option value="pending" <?= $budget['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="sent" <?= $budget['status'] === 'sent' ? 'selected' : '' ?>>Enviado</option>
                                <option value="approved" <?= $budget['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                                <option value="rejected" <?= $budget['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                                <option value="expired" <?= $budget['status'] === 'expired' ? 'selected' : '' ?>>Expirado</option>
                            </select>
                        </div>
                        
                        <div class="date-column">
                            <?= date('d/m/Y', strtotime($budget['created_at'])) ?>
                            <?php if ($budget['end_date']): ?>
                                <br><small>hasta <?= date('d/m/Y', strtotime($budget['end_date'])) ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="author-column">
                            <?= htmlspecialchars($budget['author_name'] ?? 'Usuario') ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if ($budget['pdf_url']): ?>
                                <button class="btn-icon black btn-view-pdf" data-pdf-url="<?= $budget['pdf_url'] ?>" title="Ver PDF">
                                    <i data-lucide="file-check"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-icon black" disabled title="PDF no disponible">
                                    <i data-lucide="file-check" style="opacity: 0.3;"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (!$budget['pdf_url']): ?>
                                <button class="btn-icon black btn-create-pdf" data-budget-id="<?= $budget['id'] ?>" title="Crear PDF">
                                    <i data-lucide="file-stack"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-icon black" disabled title="PDF ya creado">
                                    <i data-lucide="file-stack" style="opacity: 0.3;"></i>
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn-icon black btn-edit" data-type="budget" data-id="<?= $budget['id'] ?>" title="Editar">
                                <i data-lucide="pencil"></i>
                            </button>
                            <button class="btn-icon black btn-duplicate" data-type="budget" data-id="<?= $budget['id'] ?>" title="Duplicar">
                                <i data-lucide="copy"></i>
                            </button>
                            <button class="btn-icon red btn-delete" data-type="budget" data-id="<?= $budget['id'] ?>" title="Borrar">
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