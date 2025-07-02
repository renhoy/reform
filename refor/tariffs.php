<?php
// {"_META_file_path_": "refor/tariffs.php"}
// Página de gestión de tarifas

require_once 'config.php';
require_once 'auth.php';
requireAuth();

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT * FROM tariffs
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tariffs = $stmt->fetchAll();

function isComplete($tariff) {
    return !empty($tariff['title']) && 
           !empty($tariff['name']) && 
           !empty($tariff['nif']) && 
           !empty($tariff['address']) && 
           !empty($tariff['contact']);
}

$pageTitle = "Tarifas";
$activeNav = "tariffs";
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
                <p class="page-header__subtitle">Gestiona tus tarifas de precios</p>
            </div>
            <div class="page-header__actions">
                <a href="tariff-form.php" class="btn btn--primary">
                    <span class="btn__icon">+</span>
                    Nueva Tarifa
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert--success">
                <div class="alert__icon">✓</div>
                <div class="alert__content">
                    <div class="alert__title">¡Éxito!</div>
                    <div class="alert__message">Operación completada correctamente</div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert--error">
                <div class="alert__icon">!</div>
                <div class="alert__content">
                    <div class="alert__title">Error</div>
                    <div class="alert__message"><?= htmlspecialchars($_GET['error']) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="content-section">
            <?php if (empty($tariffs)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">📊</div>
                    <h2 class="empty-state__title">No hay tarifas</h2>
                    <p class="empty-state__description">
                        Crea tu primera tarifa para comenzar a generar presupuestos
                    </p>
                    <a href="tariff-form.php" class="btn btn--primary">
                        Crear Primera Tarifa
                    </a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table__head">
                                <tr>
                                    <th>Tarifa</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="table__actions">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($tariffs as $tariff): ?>
                                    <?php $complete = isComplete($tariff); ?>
                                    <tr class="table__row">
                                        <td class="table__cell">
                                            <div class="table__cell-content">
                                                <div class="table__cell-title"><?= htmlspecialchars($tariff['title']) ?></div>
                                                <?php if ($tariff['name']): ?>
                                                    <div class="table__cell-subtitle"><?= htmlspecialchars($tariff['name']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="table__cell">
                                            <?php if ($complete): ?>
                                                <span class="badge badge--success">Completa</span>
                                            <?php else: ?>
                                                <span class="badge badge--warning">Incompleta</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table__cell">
                                            <time class="table__date">
                                                <?= date('d/m/Y H:i', strtotime($tariff['created_at'])) ?>
                                            </time>
                                        </td>
                                        <td class="table__cell table__actions">
                                            <div class="btn-group">
                                                <?php if ($complete): ?>
                                                    <a href="budget-form.php?tariff_id=<?= $tariff['id'] ?>" 
                                                       class="btn btn--sm btn--primary" 
                                                       title="Crear Presupuesto">
                                                        Presupuesto
                                                    </a>
                                                <?php endif; ?>
                                                <a href="tariff-form.php?id=<?= $tariff['id'] ?>" 
                                                   class="btn btn--sm btn--secondary" 
                                                   title="Editar">
                                                    Editar
                                                </a>
                                                <button type="button" 
                                                        class="btn btn--sm btn--outline" 
                                                        onclick="duplicateTariff(<?= $tariff['id'] ?>)"
                                                        title="Duplicar">
                                                    Duplicar
                                                </button>
                                                <button type="button" 
                                                        class="btn btn--sm btn--danger" 
                                                        onclick="deleteTariff(<?= $tariff['id'] ?>)"
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
            <?php endif; ?>
        </div>
    </main>

    <script>
        function duplicateTariff(id) {
            if (confirm('¿Duplicar esta tarifa?')) {
                window.location.href = `duplicate-tariff.php?id=${id}`;
            }
        }

        function deleteTariff(id) {
            if (confirm('¿Eliminar esta tarifa? Esta acción no se puede deshacer.')) {
                window.location.href = `delete-tariff.php?id=${id}`;
            }
        }
    </script>
</body>
</html>