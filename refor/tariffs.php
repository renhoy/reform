<?php
// {"_META_file_path_": "refor/tariffs.php"}
// Página de tarifas con diseño exacto del sistema

require_once 'includes/config.php';
require_once 'includes/tariffs-helpers.php';

requireAuth();
$tariffs = getTariffsWithData();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="container">
    <?php include 'includes/header.php'; ?>
    
        <!-- Page Header -->
        <div class="spacing">
            <div class="page-header">
                <h1>Tarifa</h1>
                <div class="header-title__buttons">
                    <a href="tariff-form.php?template_id=1" class="btn btn--tariffs">Nueva Tarifa</a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="spacing">
            <div class="filters-bar tariffs" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Contenedor flexible para inputs -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <input type="text" class="search-input" placeholder="Buscar por nombre..." style="flex: 1 1 200px;">
                    <select class="filter-select" style="flex: 1 1 150px;" onchange="filterByVisibility(this.value)">
                        <option value="">Acceso</option>
                        <option value="public">Público</option>
                        <option value="private">Privado</option>
                    </select>
                    <select class="filter-select" style="flex: 1 1 150px;" onchange="filterByStatus(this.value)">
                        <option value="">Estado</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Buscar por autor..." style="flex: 1 1 200px;">
                    <div style="display: flex; gap: 10px; flex: 2 1 300px;">
                        <input type="date" class="date-input" style="flex: 1;">
                        <input type="date" class="date-input" style="flex: 1;">
                    </div>
                </div>
                
                <!-- Línea de botones -->
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button class="btn--filter" onclick="applyFilters()">Filtrar</button>
                    <button class="btn--clear" onclick="resetFilters()">Limpiar</button>
                </div>
            </div>
        </div>

        <!-- Notificaciones -->
        <?php if (isset($_GET['success'])): ?>
            <div class="notification notification--success">
                <i data-lucide="check-circle"></i>
                <span>
                    <?php
                    switch($_GET['success']) {
                        case 'created': echo 'Tarifa creada correctamente'; break;
                        case 'updated': echo 'Tarifa actualizada correctamente'; break;
                        case 'duplicated': echo 'Tarifa duplicada correctamente'; break;
                        case 'deleted': echo 'Tarifa eliminada correctamente'; break;
                        default: echo 'Operación realizada correctamente';
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="notification notification--error">
                <i data-lucide="alert-circle"></i>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Contenido Principal -->
        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <div class="empty-state__icon">
                    <i data-lucide="file-text"></i>
                </div>
                <h2>No hay tarifas disponibles</h2>
                <p>Crea tu primera tarifa para comenzar a generar presupuestos personalizados</p>
                <a href="create-tariff.php" class="btn btn--primary">
                    <i data-lucide="plus"></i>
                    <span>Crear Primera Tarifa</span>
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <!-- Desktop Table -->
                <div class="table-header--tariffs">
                    <div>Nombre</div>
                    <div></div>
                    <div>Presupuestos</div>
                    <div>Acceso</div>
                    <div>Estado</div>
                    <div>Autor</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($tariffs as $tariff): ?>
                    <?php $isComplete = isTariffComplete($tariff); ?>
                    <div class="table-row--tariffs">
                        <!-- Nombre -->
                        <div class="table-cell">
                            <?php if (!$isComplete): ?>
                                <span class="badge badge--incomplete">Incompleta</span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($tariff['title']) ?></span>
                            <span class="cell-line--secondary"><?= date('d/m/Y', strtotime($tariff['created_at'])) ?> - <?= htmlspecialchars($tariff['description'] ?? 'Sin descripción') ?></span>
                        </div>
                        
                        <!-- Columna vacía -->
                        <div class="table-cell"></div>
                        
                        <!-- Presupuestos -->
                        <div class="table-cell">
                            <div class="budget-actions">
                                <button class="btn-icon btn-icon--black"
                                    title="Generar presupuesto a partir de esta tarifa"
                                    onclick="window.location.href='budget-form.php?tariff_uuid=<?= $tariff['uuid'] ?>'">
                                    <i data-lucide="file-input"></i>
                                </button>
                                <?php if ($tariff['budgets_count'] > 0): ?>
                                <div class="budget-count">
                                    <button class="btn-icon btn-icon--black"
                                        title="Ver presupuestos de esta tarifa"
                                        onclick="window.location.href='budgets.php?filter_tariff_id=<?= $tariff['id'] ?>'">
                                        <i data-lucide="list"></i>
                                    </button>
                                    <span class="count-number"><?= $tariff['budgets_count'] ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Acceso -->
                        <div class="table-cell">
                            <select class="badge-select badge--<?= $tariff['access'] === 'public' ? 'success' : 'danger' ?>"
                                    data-tariff-id="<?= $tariff['id'] ?>" 
                                    data-action="toggle-access">
                                <option value="public" <?= $tariff['access'] === 'public' ? 'selected' : '' ?>>Público</option>
                                <option value="private" <?= $tariff['access'] === 'private' ? 'selected' : '' ?>>Privado</option>
                            </select>
                        </div>
                        
                        <!-- Estado -->
                        <div class="table-cell">
                            <select class="badge-select badge--<?= $tariff['status'] === 'active' ? 'success' : 'danger' ?>"
                                    data-tariff-id="<?= $tariff['id'] ?>" 
                                    data-action="toggle-status">
                                <option value="active" <?= $tariff['status'] === 'active' ? 'selected' : '' ?>>Activa</option>
                                <option value="inactive" <?= $tariff['status'] === 'inactive' ? 'selected' : '' ?>>Inactiva</option>
                            </select>
                        </div>
                        
                        <!-- Autor -->
                        <div class="table-cell">
                            <span class="cell-line cell-line--primary"><?= htmlspecialchars($tariff['author_name'] ?? 'Usuario') ?></span>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="table-cell">
                            <div class="action-buttons">
                                <button class="btn-icon btn-icon--black" 
                                        title="Editar Tarifa"
                                        onclick="window.location.href='tariff-form.php?id=<?= $tariff['id'] ?>'">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <button class="btn-icon btn-icon--black" 
                                        title="Duplicar Tarifa"
                                        onclick="if(confirm('¿Duplicar tarifa?')) window.location.href='process/duplicate-tariff.php?id=<?= $tariff['id'] ?>'">
                                    <i data-lucide="copy"></i>
                                </button>
                                <button class="btn-icon btn-icon--red" 
                                        title="Eliminar Tarifa"
                                        onclick="if(confirm('¿Eliminar tarifa?')) window.location.href='process/delete-tariff.php?id=<?= $tariff['id'] ?>'">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Mobile Cards -->
                <div class="table-mobile">
                    <?php foreach ($tariffs as $tariff): ?>
                        <?php $isComplete = isTariffComplete($tariff); ?>
                        <div class="table-card table-card--tariffs">
                            <!-- Header -->
                            <div class="table-card__header">
                                <span>Nombre</span>
                            </div>
                            <div class="table-card__body">
                                <div class="table-card__content">
                                    <?php if (!$isComplete): ?>
                                        <span class="badge badge--incomplete">Incompleta</span>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($tariff['title']) ?></span>
                                    <span class="cell-line--secondary"><?= date('d/m/Y', strtotime($tariff['created_at'])) ?> - <?= htmlspecialchars($tariff['description'] ?? 'Sin descripción') ?></span>
                                </div>

                                <!-- Presupuestos y Acceso -->
                                <div class="table-card__section">
                                    <span>Presupuestos</span>
                                    <span>Acceso</span>
                                </div>

                                <div class="table-card__content">
                                    <div class="budget-actions">
                                        <button class="btn-icon btn-icon--black"
                                            title="Generar presupuesto a partir de esta tarifa"
                                            onclick="window.location.href='budget-form.php?tariff_uuid=<?= $tariff['uuid'] ?>'">
                                            <i data-lucide="file-input"></i>
                                        </button>
                                        <?php if ($tariff['budgets_count'] > 0): ?>
                                        <div class="budget-count">
                                            <button class="btn-icon btn-icon--black"
                                                title="Ver presupuestos de esta tarifa"
                                                onclick="window.location.href='budgets.php?filter_tariff_id=<?= $tariff['id'] ?>'">
                                                <i data-lucide="list"></i>
                                            </button>
                                            <span class="count-number"><?= $tariff['budgets_count'] ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-cell">
                                        <select class="badge-select badge--<?= $tariff['access'] === 'public' ? 'success' : 'danger' ?>"
                                                data-tariff-id="<?= $tariff['id'] ?>" 
                                                data-action="toggle-access">
                                            <option value="public" <?= $tariff['access'] === 'public' ? 'selected' : '' ?>>Público</option>
                                            <option value="private" <?= $tariff['access'] === 'private' ? 'selected' : '' ?>>Privado</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Estado y Autor -->
                                <div class="table-card__section">
                                    <span>Estado</span>
                                    <span>Autor</span>
                                </div>

                                <div class="table-card__content">
                                    <div class="table-cell">
                                        <select class="badge-select badge--<?= $tariff['status'] === 'active' ? 'success' : 'danger' ?>"
                                                data-tariff-id="<?= $tariff['id'] ?>" 
                                                data-action="toggle-status">
                                            <option value="active" <?= $tariff['status'] === 'active' ? 'selected' : '' ?>>Activa</option>
                                            <option value="inactive" <?= $tariff['status'] === 'inactive' ? 'selected' : '' ?>>Inactiva</option>
                                        </select>
                                    </div>
                                    <span class="cell-line cell-line--primary"><?= htmlspecialchars($tariff['author_name'] ?? 'Usuario') ?></span>
                                </div>

                                <!-- Acciones -->
                                <div class="table-card__section">
                                    <span>Acciones</span>
                                </div>

                                <div class="table-card__content">
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-icon--black" 
                                                title="Editar tarifa"
                                                onclick="window.location.href='tariff-form.php?id=<?= $tariff['id'] ?>'">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--black" 
                                                title="Duplicar tarifa"
                                                onclick="if(confirm('¿Duplicar tarifa?')) window.location.href='process/duplicate-tariff.php?id=<?= $tariff['id'] ?>'">
                                            <i data-lucide="copy"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--red" 
                                                title="Eliminar tarifa"
                                                onclick="if(confirm('¿Eliminar tarifa?')) window.location.href='process/delete-tariff.php?id=<?= $tariff['id'] ?>'">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modales eliminados según solicitud -->

    <script src="assets/js/badge-select.js"></script>
    <script src="assets/js/tariffs.js"></script>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof TariffsManager !== 'undefined') {
                new TariffsManager();
            }
        });
    </script>
</body>
</html>