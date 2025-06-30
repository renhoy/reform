<?php
// {"_META_file_path_": "public/budgets.php"}
// Listado completo de presupuestos

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();

// Obtener presupuestos con datos de tarifa
$budgets = $pdo->query("
    SELECT b.*, t.title as tariff_title, t.json_tariff_data, u.name as author_name
    FROM budgets b 
    LEFT JOIN tariffs t ON JSON_EXTRACT(b.json_tariff_data, '$.id') = t.id
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC 
    LIMIT 100
")->fetchAll();

// Calcular estadísticas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM budgets WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

function formatCurrency($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

function calculateDaysRemaining($endDate) {
    if (!$endDate) return null;
    $today = new DateTime();
    $end = new DateTime($endDate);
    $diff = $today->diff($end);
    return $diff->invert ? -$diff->days : $diff->days;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos - Generador de Presupuestos</title>
    <link rel="stylesheet" href="assets/css/header-styles.css">
    <link rel="stylesheet" href="assets/css/budgets-styles.css">
</head>
<body>
    <?php include SRC_PATH . '/views/templates/header.php'; ?>

    <div class="container">
        <!-- Título -->
        <div class="page-header">
            <h1>Presupuestos</h1>
        </div>

        <!-- Buscador y filtros -->
        <div class="filters-bar">
            <input type="text" id="searchClient" placeholder="Buscar por cliente..." class="search-input">
            <select id="filterStatus" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="draft">Borrador</option>
                <option value="sent">Enviado</option>
                <option value="approved">Aprobado</option>
                <option value="rejected">Rechazado</option>
                <option value="expired">Expirado</option>
            </select>
            <input type="date" id="dateFrom" class="date-input">
            <input type="date" id="dateTo" class="date-input">
        </div>

        <!-- Estadísticas -->
        <div class="stats-summary">
            Presupuestos Realizados (<?= $stats['total'] ?>): 
            👍 Aprobados (<?= $stats['approved'] ?>), 
            ❌ Rechazados (<?= $stats['rejected'] ?>), 
            📤 Enviados (<?= $stats['sent'] ?>), 
            ⏰ Expirados (<?= $stats['expired'] ?>), 
            ✍️ Borrador (<?= $stats['draft'] ?>)
        </div>

        <!-- Tabla -->
        <div class="budgets-table">
            <div class="table-header">
                <div>Cliente (NIF/NIE)</div>
                <div class="notes-col">📝</div>
                <div>Total</div>
                <div>Tarifa</div>
                <div>Estado</div>
                <div>Fecha</div>
                <div>Autor</div>
                <div>Acciones</div>
            </div>

            <?php foreach ($budgets as $budget): ?>
                <?php 
                $budgetData = json_decode($budget['json_budget_data'], true);
                $tariffData = json_decode($budget['json_tariff_data'], true);
                $observations = json_decode($budget['json_observations'], true) ?: [];
                $daysRemaining = calculateDaysRemaining($budget['end_date']);
                ?>
                <div class="table-row" data-budget-id="<?= $budget['id'] ?>">
                    <!-- Cliente -->
                    <div class="client-info">
                        <?= htmlspecialchars($budget['client_name']) ?> 
                        (<?= htmlspecialchars($budget['client_nif_nie']) ?>)
                    </div>

                    <!-- Apuntes -->
                    <div class="notes-cell">
                        <span class="notes-icon <?= !empty($observations) ? 'has-notes' : '' ?>" 
                              data-budget-id="<?= $budget['id'] ?>"
                              title="<?= !empty($observations) ? htmlspecialchars(end($observations)['note']) : 'Sin apuntes' ?>">
                            📝
                        </span>
                    </div>

                    <!-- Total con tooltip -->
                    <div class="total-amount" 
                         title="Base: <?= formatCurrency($budget['base']) ?> | IVA: <?= formatCurrency($budget['iva']) ?> | Total: <?= formatCurrency($budget['total']) ?>">
                        <?= formatCurrency($budget['total']) ?>
                    </div>

                    <!-- Tarifa -->
                    <div class="tariff-info">
                        <button class="btn-view-tariff" data-tariff-data="<?= htmlspecialchars(json_encode($tariffData)) ?>">Ver</button>
                        <span class="tariff-name"><?= htmlspecialchars($budget['tariff_title']) ?></span>
                    </div>

                    <!-- Estado -->
                    <div class="status-cell">
                        <select class="status-select" data-budget-id="<?= $budget['id'] ?>">
                            <option value="draft" <?= $budget['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                            <option value="sent" <?= $budget['status'] === 'sent' ? 'selected' : '' ?>>Enviado</option>
                            <option value="approved" <?= $budget['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="rejected" <?= $budget['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                            <option value="expired" <?= $budget['status'] === 'expired' ? 'selected' : '' ?>>Expirado</option>
                        </select>
                    </div>

                    <!-- Fecha -->
                    <div class="date-info">
                        <?= date('d/m/Y', strtotime($budget['created_at'])) ?> - 
                        <?= $budget['end_date'] ? date('d/m/Y', strtotime($budget['end_date'])) : 'Sin límite' ?>
                        <?php if ($daysRemaining !== null): ?>
                            <span class="days-remaining">(<?= $daysRemaining ?> días)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Autor -->
                    <div class="author-name">
                        <?= htmlspecialchars($budget['author_name']) ?>
                    </div>

                    <!-- Acciones -->
                    <div class="actions-cell">
                        <button class="btn-action <?= $budget['pdf_url'] ? '' : 'disabled' ?>" 
                                onclick="<?= $budget['pdf_url'] ? "window.open('{$budget['pdf_url']}', '_blank')" : '' ?>">Ver</button>
                        <button class="btn-action <?= !$budget['pdf_url'] ? '' : 'disabled' ?>" 
                                onclick="createPDF(<?= $budget['id'] ?>)">Crear PDF</button>
                        <button class="btn-action" onclick="editBudget('<?= $budget['uuid'] ?>')">Editar</button>
                        <button class="btn-action" onclick="duplicateBudget(<?= $budget['id'] ?>)">Duplicar</button>
                        <button class="btn-action btn-danger" onclick="deleteBudget(<?= $budget['id'] ?>)">Borrar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Apuntes -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Apuntes del Presupuesto</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div id="notesHistory"></div>
                <div class="add-note">
                    <select id="noteCategory">
                        <option value="llamada">📞 Llamada</option>
                        <option value="email">📧 Email</option>
                        <option value="reunion">🤝 Reunión</option>
                        <option value="nota">📝 Nota</option>
                    </select>
                    <textarea id="noteText" placeholder="Añadir apunte..."></textarea>
                    <button onclick="addNote()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tarifa -->
    <div id="tariffModal" class="modal fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Estructura de la Tarifa</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div id="tariffHierarchy"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/budgets-handler.js"></script>
</body>
</html>