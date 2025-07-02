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
                    <a href="create-tariff.php" class="btn btn--tariffs">Nueva Tarifa</a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="spacing">
            <div class="filters-bar tariffs">
                <input type="text" class="search-input" placeholder="Buscar por nombre...">
                <select class="filter-select">
                    <option>Acciones</option>
                    <option>Público</option>
                    <option>Privado</option>
                </select>
                <select class="filter-select">
                    <option>Estados</option>
                    <option>Borrador</option>
                    <option>Pendiente</option>
                    <option>Enviado</option>
                    <option>Aprobado</option>
                    <option>Rechazado</option>
                    <option>Expirado</option>
                </select>
                <input type="text" class="search-input" placeholder="Buscar por autores...">
                <input type="date" class="date-input">
                <input type="date" class="date-input">
                <div class="filter-buttons">
                    <button class="btn--filter">Filtrar</button>
                    <button class="btn--clear">Limpiar</button>
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
                                    title="Generar presupuesto a partir de esta tarifa">
                                    <i data-lucide="file-input"></i>
                                </button>
                                <?php if ($tariff['budgets_count'] > 0): ?>
                                <div class="budget-count">
                                    <button class="btn-icon btn-icon--black"
                                        title="Ver presupuestos generados con esta tarifa">
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
                                        onclick="window.location.href='edit-tariff.php?id=<?= $tariff['id'] ?>'">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <button class="btn-icon btn-icon--black" 
                                        title="Duplicar Tarifa"
                                        data-action="duplicate-tariff"
                                        data-tariff-id="<?= $tariff['id'] ?>">
                                    <i data-lucide="copy"></i>
                                </button>
                                <?php if ($tariff['budgets_count'] == 0): ?>
                                <button class="btn-icon btn-icon--red" 
                                        title="Borrar Tarifa"
                                        data-action="delete-tariff"
                                        data-tariff-id="<?= $tariff['id'] ?>"
                                        data-tariff-name="<?= htmlspecialchars($tariff['title']) ?>">
                                    <i data-lucide="trash-2"></i>
                                </button>
                                <?php else: ?>
                                    <button class="btn-icon disabled btn-icon--red" 
                                            title="Borrar Tarifa"
                                            data-action="delete-tariff"
                                            data-tariff-id="<?= $tariff['id'] ?>"
                                            data-tariff-name="<?= htmlspecialchars($tariff['title']) ?>">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                <?php endif; ?>
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
                                            title="Generar presupuesto a partir de esta tarifa">
                                            <i data-lucide="file-input"></i>
                                        </button>
                                        <?php if ($tariff['budgets_count'] > 0): ?>
                                        <div class="budget-count">
                                            <button class="btn-icon btn-icon--black"
                                                title="Ver presupuestos generados con esta tarifa">
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
                                                onclick="window.location.href='edit-tariff.php?id=<?= $tariff['id'] ?>'">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button class="btn-icon btn-icon--black" 
                                                title="Duplicar tarifa"
                                                data-action="duplicate-tariff"
                                                data-tariff-id="<?= $tariff['id'] ?>">
                                            <i data-lucide="copy"></i>
                                        </button>
                                        <?php if ($tariff['budgets_count'] == 0): ?>
                                        <button class="btn-icon btn-icon--red" 
                                                title="Borrar tarifa"
                                                data-action="delete-tariff"
                                                data-tariff-id="<?= $tariff['id'] ?>"
                                                data-tariff-name="<?= htmlspecialchars($tariff['title']) ?>">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modales -->
    <div id="tariffModal" class="modal">
        <div class="modal__content">
            <div class="modal__header">
                <h3>Detalles de la Tarifa</h3>
                <button class="btn-icon btn-icon--black" data-action="close-modal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal__body" id="tariffContent"></div>
        </div>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal__content modal__content--small">
            <div class="modal__header">
                <h3 id="confirmTitle">Confirmar acción</h3>
            </div>
            <div class="modal__body">
                <p id="confirmMessage">¿Estás seguro de realizar esta acción?</p>
            </div>
            <div class="modal__footer">
                <button class="btn btn--secondary" data-action="close-modal">Cancelar</button>
                <button class="btn btn--danger" id="confirmButton">Confirmar</button>
            </div>
        </div>
    </div>

    <script src="assets/js/tariffs.js"></script>
    <script src="design/script.js"></script>
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