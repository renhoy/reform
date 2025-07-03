<?php
// {"_META_file_path_": "refor/budgets.php"}
// Página de presupuestos con diseño exacto del sistema

require_once 'includes/config.php';
require_once 'includes/budgets-helpers.php';

requireAuth();

// Obtener presupuestos con datos adicionales
$pdo = getConnection();
$budgets = $pdo->prepare("
    SELECT b.*, 
           t.title as tariff_title,
           u.name as author_name
    FROM budgets b
    LEFT JOIN tariffs t ON b.tariff_id = t.id 
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$budgets->execute([$_SESSION['user_id']]);
$budgets = $budgets->fetchAll();

// Obtener estadísticas
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

// Función para formatear precio
function formatPrice($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="container">
    <?php include 'includes/header.php'; ?>
    
        <!-- Page Header -->
        <div class="spacing">
            <div class="page-header">
                <h1>Presupuestos</h1>
                <div class="header-title__buttons">
                    <a href="create-tariff.php" class="btn btn--tariffs">Nueva Tarifa</a>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="spacing">
            <div class="stats-bar">
                <div class="stats-badges">
                    <span class="stats-total"><?= $stats['total'] ?> Presupuestos:</span>
                    
                    <span class="stats-badge stats-badge--success">
                        <span class="stats-number badge--success"><?= $stats['approved'] ?></span>
                        <span>Aprobados </span>
                    </span>
                    <span class="stats-badge stats-badge--danger">
                        <span class="stats-number badge--danger"><?= $stats['rejected'] ?></span>
                        <span>Rechazados </span>
                    </span>
                    <span class="stats-badge stats-badge--info">
                        <span class="stats-number badge--info"><?= $stats['sent'] ?></span>
                        <span>Enviados </span>
                    </span>
                    <span class="stats-badge stats-badge--black">
                        <span class="stats-number badge--black"><?= $stats['expired'] ?></span>
                        <span>Expirados</span>
                    </span>
                    <span class="stats-badge stats-badge--warning">
                        <span class="stats-number badge--warning"><?= $stats['pending'] ?></span>
                        <span>Pendientes </span>
                    </span>
                    <span class="stats-badge stats-badge--secondary">
                        <span class="stats-number badge--secondary"><?= $stats['draft'] ?></span>
                        <span>Borradores </span>
                    </span>
                </div>
            </div>

        <div class="spacing">
            <!-- Filters -->
            <div class="filters-bar budgets" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Contenedor flexible para inputs -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <input type="text" class="search-input" placeholder="Buscar por nombre..." style="flex: 1 1 150px;">
                    <select class="filter-select" style="flex: 0.5 1 100px;" onchange="filterByStatus(this.value)">
                        <option value="">Estado</option>
                        <option value="draft">Borrador</option>
                        <option value="pending">Pendiente</option>
                        <option value="sent">Enviado</option>
                        <option value="approved">Aprobado</option>
                        <option value="rejected">Rechazado</option>
                        <option value="expired">Expirado</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Buscar por cliente..." style="flex: 1 1 150px;">
                    <div style="display: flex; gap: 10px; flex: 1 1 200px;">
                        <input type="date" class="date-input" style="flex: 1;">
                        <input type="date" class="date-input" style="flex: 1;">
                    </div>
                    <div style="display: flex; gap: 10px; flex: 0 0 auto;">
                        <button class="btn--filter" onclick="applyFilters()">Filtrar</button>
                        <button class="btn--clear" onclick="resetFilters()">Limpiar</button>
                    </div>
                </div>
                
                <!-- Línea de botones -->
                
            </div>

            <!-- Tabla Presupuestos -->
            <div class="table-responsive">
                <!-- Desktop Tabla Presupuestos -->
                <div class="table-header--budgets">
                    <div>Cliente (NIF/NIE)</div>
                    <div>Apuntes</div>
                    <div>Total</div>
                    <div>Tarifa</div>
                    <div>Estado</div>
                    <div>Usuario</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($budgets as $budget): ?>
                    <?php $daysRemaining = getDaysRemaining($budget['end_date']); ?>
                    <div class="table-row--budgets">
                        <!-- Cliente -->
                        <div class="table-cell">
                            <span><?= htmlspecialchars($budget['client_name']) ?>
                            <span class="cell-line--secondary">
                                (<?= htmlspecialchars($budget['client_nif_nie'] ?? 'Sin NIF/NIE') ?>)
                            </span></span>
                            <span class="cell-line--secondary">
                                <?= $budget['start_date'] ? date('d/m/Y', strtotime($budget['start_date'])) : 'Sin fecha' ?> - 
                                <?= $budget['end_date'] ? date('d/m/Y', strtotime($budget['end_date'])) : 'Sin fecha' ?> 
                                (<?= $daysRemaining ?> días restantes)
                            </span>
                        </div>
                        
                        <!-- Apuntes -->
                        <div class="table-cell">
                            <button class="btn-icon btn-icon--black" title="Editar apuntes">
                                <i data-lucide="edit-3"></i>
                            </button>
                        </div>
                        
                        <!-- Total -->
                        <div class="table-cell">
                            <span class="total-amount"><?= formatPrice($budget['total']) ?></span>
                        </div>
                        
                        <!-- Tarifa -->
                        <div class="table-cell">
                            <button class="btn-icon btn-icon--black" title="Ver tarifa">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                        
                        <!-- Estado -->
                        <div class="table-cell">
                            <select class="badge-select badge--<?= $budget['status'] === 'approved' ? 'success' : ($budget['status'] === 'rejected' ? 'danger' : ($budget['status'] === 'sent' ? 'info' : 'warning')) ?>"
                                    data-budget-id="<?= $budget['id'] ?>" 
                                    data-action="toggle-status">
                                <option value="draft" <?= $budget['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                                <option value="pending" <?= $budget['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="sent" <?= $budget['status'] === 'sent' ? 'selected' : '' ?>>Enviado</option>
                                <option value="approved" <?= $budget['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                                <option value="rejected" <?= $budget['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                                <option value="expired" <?= $budget['status'] === 'expired' ? 'selected' : '' ?>>Expirado</option>
                            </select>
                        </div>
                        
                        <!-- Usuario -->
                        <div class="table-cell">
                            <span><?= htmlspecialchars($budget['author_name'] ?? 'Usuario') ?></span>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="table-cell">
                            <div class="action-buttons">
                                <button class="btn-icon btn-icon--black" title="Ver presupuesto">
                                    <i data-lucide="file-text"></i>
                                </button>
                                <button class="btn-icon btn-icon--black" title="Enviar por email">
                                    <i data-lucide="mail"></i>
                                </button>
                                <button class="btn-icon btn-icon--black" title="Editar presupuesto">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <button class="btn-icon btn-icon--black" title="Duplicar presupuesto">
                                    <i data-lucide="copy"></i>
                                </button>
                                <button class="btn-icon btn-icon--red" title="Eliminar presupuesto">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Mobile Cards -->
                <div class="table-mobile">
                    <?php foreach ($budgets as $budget): ?>
                        <?php $daysRemaining = getDaysRemaining($budget['end_date']); ?>
                        <div class="table-card table-card--budgets">
                            <!-- Header -->
                            <div class="table-card__header">
                                <span>Cliente (NIF/NIE)</span>
                            </div>
                            <div class="table-card__body">
                                <div class="table-card__content">
                                    <span><?= htmlspecialchars($budget['client_name']) ?></span>
                                    <span class="cell-line--secondary">
                                        (<?= htmlspecialchars($budget['client_nif_nie'] ?? 'Sin NIF/NIE') ?>)
                                    </span>
                                </div>

                                <!-- Apuntes y Total -->
                                <div class="table-card__section apuntes-total">
                                    <span>Apuntes</span>
                                    <span>Total</span>
                                </div>

                                <div class="table-card__content apuntes-total">
                                    <div>
                                        <button class="btn-icon btn-icon--black" title="Editar apuntes">
                                            <i data-lucide="edit-3"></i>
                                        </button>
                                        <span class="cell-line--secondary">
                                            <?= date('d/m/Y', strtotime($budget['start_date'])) ?> - 
                                            <?= date('d/m/Y', strtotime($budget['end_date'])) ?> 
                                            (<?= $daysRemaining ?> días restantes)
                                        </span>
                                    </div>
                                    <span class="total-amount"><?= formatPrice($budget['total']) ?></span>
                                </div>

                                <!-- Tarifa, Estado y Usuario -->
                                <div class="table-card__section">
                                    <span>Tarifa</span>
                                    <span>Estado</span>
                                    <span>Usuario</span>
                                </div>

                                <div class="table-card__content">
                                    <button class="btn-icon btn-icon--black" title="Ver tarifa">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    <select class="badge-select badge--<?= $budget['status'] === 'approved' ? 'success' : ($budget['status'] === 'rejected' ? 'danger' : ($budget['status'] === 'sent' ? 'info' : 'warning')) ?>"
                                            data-budget-id="<?= $budget['id'] ?>" 
                                            data-action="toggle-status">
                                        <option value="draft" <?= $budget['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                                        <option value="pending" <?= $budget['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="sent" <?= $budget['status'] === 'sent' ? 'selected' : '' ?>>Enviado</option>
                                        <option value="approved" <?= $budget['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                                        <option value="rejected" <?= $budget['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                                        <option value="expired" <?= $budget['status'] === 'expired' ? 'selected' : '' ?>>Expirado</option>
                                    </select>
                                    <span><?= htmlspecialchars($budget['author_name'] ?? 'Usuario') ?></span>
                                </div>

                                <!-- Acciones -->
                                <div class="table-card__section">
                                    <span>Acciones</span>
                                </div>

                                <div class="table-card__content">
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-icon--black" title="Ver presupuesto">
                                            <i data-lucide="file-text"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--black" title="Enviar por email">
                                            <i data-lucide="mail"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--black" title="Editar presupuesto">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--black" title="Duplicar presupuesto">
                                            <i data-lucide="copy"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--red" title="Eliminar presupuesto">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/badge-select.js') ?>"></script>
    <script src="<?= asset('js/budgets.js') ?>"></script>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof BudgetsManager !== 'undefined') {
                new BudgetsManager();
            }
        });
    </script>
</body>
</html>