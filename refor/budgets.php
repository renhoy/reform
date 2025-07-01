<?php
// {"_META_file_path_": "refor/budgets.php"}
// Página principal de gestión de presupuestos

require_once 'includes/config.php';
require_once 'includes/budgets-helpers.php';
requireAuth();

// Obtener filtros
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Obtener datos
$budgets = getBudgetsWithFilters($search, $status, $dateFrom, $dateTo);
$stats = getBudgetStats();

$title = "Presupuestos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Generador de Presupuestos</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/budgets.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

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
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Borrador</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Enviado</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expirado</option>
                </select>
                
                <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="filter-date">
                <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="filter-date">
                
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="budgets.php" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

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
            
            <?php if (empty($budgets)): ?>
                <div class="empty-state">
                    <h3>No hay presupuestos</h3>
                    <p>Los presupuestos aparecerán aquí cuando se generen</p>
                </div>
            <?php else: ?>
                <?php foreach ($budgets as $budget): ?>
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
                        
                        <div class="total-column" title="Base: <?= formatNumber($budget['base']) ?>€ | IVA: <?= formatNumber($budget['iva']) ?>€ | Total: <?= formatNumber($budget['total']) ?>€">
                            <?= formatNumber($budget['total']) ?>€
                        </div>
                        
                        <div class="tariff-column">
                            <button class="btn btn-outline btn-small view-tariff" data-tariff-data='<?= htmlspecialchars(json_encode(['name' => $budget['tariff_name'] ?? 'Sin datos'])) ?>'>Ver</button>
                            <small><?= htmlspecialchars($budget['tariff_title'] ?? 'Sin título') ?></small>
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
                            <?= formatDate($budget['created_at']) ?>
                            <?php if ($budget['end_date']): ?>
                                <?php 
                                $daysRemaining = getDaysRemaining($budget['end_date']);
                                if ($daysRemaining !== null): ?>
                                    <small class="days-remaining <?= $daysRemaining <= 3 ? 'urgent' : '' ?>">
                                        (<?= $daysRemaining ?> días)
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="author-column">
                            <?= htmlspecialchars($budget['author_name']) ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if ($budget['pdf_url']): ?>
                                <button class="btn-icon black btn-view-pdf" data-pdf-url="<?= htmlspecialchars($budget['pdf_url']) ?>" title="Ver PDF">
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
            <?php endif; ?>
        </div>
    </div>

    <!-- Modales -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Apuntes del Presupuesto</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="notesHistory"></div>
                <form id="addNoteForm">
                    <div class="form-group">
                        <label for="noteCategory">Categoría:</label>
                        <select id="noteCategory" required>
                            <option value="📞">📞 Llamada</option>
                            <option value="📧">📧 Email</option>
                            <option value="🤝">🤝 Reunión</option>
                            <option value="📝">📝 Nota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="noteText">Apunte:</label>
                        <textarea id="noteText" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Añadir Apunte</button>
                </form>
            </div>
        </div>
    </div>

    <div id="tariffModal" class="modal">
        <div class="modal-content modal-fullscreen">
            <div class="modal-header">
                <h3>Visualización de Tarifa</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="tariffContent"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/budgets.js"></script>
</body>
</html>