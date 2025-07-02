<?php
// {"_META_file_path_": "refor/budgets.php"}
// Página de gestión de presupuestos

require_once 'config.php';
require_once 'auth.php';
requireAuth();

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT b.*, t.title as tariff_name 
    FROM budgets b 
    LEFT JOIN tariffs t ON b.tariff_id = t.id 
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC 
    LIMIT 50
");
$stmt->execute([$_SESSION['user_id']]);
$budgets = $stmt->fetchAll();

function getStatusInfo($status) {
    $statuses = [
        'draft' => ['text' => 'Borrador', 'class' => 'neutral'],
        'sent' => ['text' => 'Enviado', 'class' => 'info'],
        'pending' => ['text' => 'Pendiente', 'class' => 'warning'],
        'approved' => ['text' => 'Aprobado', 'class' => 'success'],
        'rejected' => ['text' => 'Rechazado', 'class' => 'error'],
        'expired' => ['text' => 'Expirado', 'class' => 'error']
    ];
    
    return $statuses[$status] ?? ['text' => ucfirst($status), 'class' => 'neutral'];
}

$pageTitle = "Presupuestos";
$activeNav = "budgets";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Budget Generator</title>
    <link rel="stylesheet" href="assets/css/design-system.css">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header__content">
                <h1 class="page-header__title"><?= $pageTitle ?></h1>
                <p class="page-header__subtitle">Consulta y gestiona todos tus presupuestos</p>
            </div>
            <div class="page-header__actions">
                <a href="tariffs.php" class="btn btn--outline">
                    <span class="btn__icon">📊</span>
                    Ver Tarifas
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert--success">
                <div class="alert__icon">✓</div>
                <div class="alert__content">
                    <div class="alert__title">¡Presupuesto generado!</div>
                    <div class="alert__message">El presupuesto se ha creado correctamente</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="content-section">
            <?php if (empty($budgets)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">📋</div>
                    <h2 class="empty-state__title">No hay presupuestos</h2>
                    <p class="empty-state__description">
                        Crea tu primera tarifa para poder generar presupuestos
                    </p>
                    <a href="tariffs.php" class="btn btn--primary">
                        Ver Tarifas
                    </a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card__header">
                        <h2 class="card__title">Últimos Presupuestos</h2>
                        <div class="card__actions">
                            <select class="form-select form-select--sm" onchange="filterBudgets(this.value)">
                                <option value="">Todos los estados</option>
                                <option value="draft">Borradores</option>
                                <option value="sent">Enviados</option>
                                <option value="pending">Pendientes</option>
                                <option value="approved">Aprobados</option>
                                <option value="rejected">Rechazados</option>
                                <option value="expired">Expirados</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table__head">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Tarifa</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="table__actions">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($budgets as $budget): ?>
                                    <?php 
                                        $statusInfo = getStatusInfo($budget['status']);
                                    ?>
                                    <tr class="table__row" data-status="<?= $budget['status'] ?>">
                                        <td class="table__cell">
                                            <div class="table__cell-content">
                                                <div class="table__cell-title"><?= htmlspecialchars($budget['client_name']) ?></div>
                                                <div class="table__cell-subtitle"><?= htmlspecialchars($budget['client_email'] ?? '') ?></div>
                                            </div>
                                        </td>
                                        <td class="table__cell">
                                            <div class="table__cell-content">
                                                <div class="table__cell-title"><?= htmlspecialchars($budget['tariff_name'] ?? 'Sin tarifa') ?></div>
                                                <div class="table__cell-subtitle">UUID: <?= substr($budget['uuid'], 0, 8) ?>...</div>
                                            </div>
                                        </td>
                                        <td class="table__cell">
                                            <div class="table__amount">
                                                <?= number_format($budget['total'], 2, ',', '.') ?> €
                                            </div>
                                        </td>
                                        <td class="table__cell">
                                            <span class="badge badge--<?= $statusInfo['class'] ?>">
                                                <?= $statusInfo['text'] ?>
                                            </span>
                                        </td>
                                        <td class="table__cell">
                                            <time class="table__date">
                                                <?= date('d/m/Y H:i', strtotime($budget['created_at'])) ?>
                                            </time>
                                        </td>
                                        <td class="table__cell table__actions">
                                            <div class="btn-group">
                                                <a href="budget-view.php?uuid=<?= $budget['uuid'] ?>" 
                                                   class="btn btn--sm btn--primary" 
                                                   title="Ver Presupuesto">
                                                    Ver
                                                </a>
                                                <?php if ($budget['status'] === 'draft'): ?>
                                                    <a href="budget-form.php?edit=<?= $budget['uuid'] ?>" 
                                                       class="btn btn--sm btn--secondary" 
                                                       title="Editar">
                                                        Editar
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($budget['pdf_url']): ?>
                                                    <a href="<?= $budget['pdf_url'] ?>" 
                                                       class="btn btn--sm btn--outline" 
                                                       title="Descargar PDF" 
                                                       target="_blank">
                                                        PDF
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn--sm btn--danger" 
                                                        onclick="deleteBudget('<?= $budget['uuid'] ?>')"
                                                        title="Eliminar">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="stats-grid">
                    <?php
                    $stats = [
                        'total' => count($budgets),
                        'pending' => count(array_filter($budgets, fn($b) => $b['status'] === 'pending')),
                        'approved' => count(array_filter($budgets, fn($b) => $b['status'] === 'approved')),
                        'total_amount' => array_sum(array_column($budgets, 'total'))
                    ];
                    ?>
                    <div class="stat-card">
                        <div class="stat-card__number"><?= $stats['total'] ?></div>
                        <div class="stat-card__label">Total Presupuestos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__number"><?= $stats['pending'] ?></div>
                        <div class="stat-card__label">Pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__number"><?= $stats['approved'] ?></div>
                        <div class="stat-card__label">Aprobados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__number"><?= number_format($stats['total_amount'], 0, ',', '.') ?> €</div>
                        <div class="stat-card__label">Valor Total</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function filterBudgets(status) {
            const rows = document.querySelectorAll('.table__row[data-status]');
            
            rows.forEach(row => {
                if (status === '' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function deleteBudget(uuid) {
            if (confirm('¿Eliminar este presupuesto? Esta acción no se puede deshacer.')) {
                window.location.href = `delete-budget.php?uuid=${uuid}`;
            }
        }
    </script>
</body>
</html>