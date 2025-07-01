<?php
// {"_META_file_path_": "refor/tariffs.php"}
// Página principal de gestión de tarifas

require_once 'includes/config.php';
require_once 'includes/tariffs-helpers.php';
requireAuth();

// Obtener datos
$tariffs = getTariffsWithData();

$title = "Tarifas";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Generador de Presupuestos</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/tariffs.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <!-- Línea 1: Título -->
        <div class="page-header-row">
            <h1 class="page-title">Tarifas</h1>
        </div>

        <!-- Línea 2: Barra de Acciones -->
        <div class="actions-row">
            <a href="create-tariff.php" class="btn btn-primary">Crear Tarifa</a>
        </div>

        <!-- Línea 3: Listado de Tarifas -->
        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <h3>No hay tarifas disponibles</h3>
                <p>Crea tu primera tarifa para comenzar</p>
                <a href="create-tariff.php" class="btn btn-primary">Crear Primera Tarifa</a>
            </div>
        <?php else: ?>
            <div class="data-table">
                <div class="table-header" style="grid-template-columns: 80px 2fr 120px 140px 120px 120px 140px;">
                    <div>Código</div>
                    <div>Nombre</div>
                    <div>Acceso</div>
                    <div>Presupuestos</div>
                    <div>Estado</div>
                    <div>Autor</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($tariffs as $tariff): ?>
                    <?php $isComplete = isTariffComplete($tariff); ?>
                    <div class="table-row" style="grid-template-columns: 80px 2fr 120px 140px 120px 120px 140px;">
                        <!-- Desktop version -->
                        <div class="code-column desktop-only">
                            <?= $tariff['id'] ?>
                        </div>
                        
                        <div class="name-column desktop-only" title="<?= htmlspecialchars($tariff['title'] . ' - ' . formatDate($tariff['created_at']) . ' - ' . ($tariff['description'] ?: 'Sin descripción')) ?>">
                            <?php if (!$isComplete): ?>
                                <span class="incomplete-badge">Incompleta</span>
                            <?php endif; ?>
                            <div class="tariff-title"><?= htmlspecialchars($tariff['title']) ?></div>
                            <small class="tariff-meta">
                                <?= formatDate($tariff['created_at']) ?> - <?= htmlspecialchars(substr($tariff['description'] ?: 'Sin descripción', 0, 50)) ?><?= strlen($tariff['description'] ?: '') > 50 ? '...' : '' ?>
                            </small>
                        </div>
                        
                        <div class="access-column desktop-only">
                            <span class="access-badge <?= $tariff['access'] ?>" data-tariff-id="<?= $tariff['id'] ?>" onclick="toggleAccess(this)">
                                <?= $tariff['access'] === 'private' ? 'Privado' : 'Público' ?>
                            </span>
                        </div>
                        
                        <div class="budgets-column desktop-only">
                            <?php if ($isComplete && $tariff['status'] === 'active'): ?>
                                <button class="btn-icon black btn-generate" data-tariff-id="<?= $tariff['id'] ?>" title="Generar presupuesto">
                                    <i data-lucide="file-input"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($tariff['budgets_count'] > 0): ?>
                                <button class="btn-icon black btn-view-budgets" data-tariff-id="<?= $tariff['id'] ?>" title="Ver presupuestos">
                                    <i data-lucide="list"></i>
                                </button>
                                <span class="budgets-count"><?= $tariff['budgets_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="status-column desktop-only">
                            <?php if ($isComplete): ?>
                                <span class="status-badge <?= $tariff['status'] ?>" data-tariff-id="<?= $tariff['id'] ?>" onclick="toggleStatus(this)">
                                    <?= $tariff['status'] === 'active' ? 'Activa' : 'Inactiva' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="author-column desktop-only">
                            <?= htmlspecialchars($tariff['author_name']) ?>
                        </div>
                        
                        <div class="action-buttons desktop-only">
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

                        <!-- Mobile version -->
                        <div class="mobile-only">
                            <div class="mobile-card-header">Nombre <span style="float: right;">Código</span></div>
                            <div class="mobile-card-section">
                                <div>
                                    <?php if (!$isComplete): ?>
                                        <span class="incomplete-badge">Incompleta</span>
                                    <?php endif; ?>
                                    <div class="tariff-title"><?= htmlspecialchars($tariff['title']) ?></div>
                                    <div class="tariff-meta"><?= formatDate($tariff['created_at']) ?> - <?= htmlspecialchars($tariff['description'] ?: 'Sin descripción') ?></div>
                                </div>
                                <div style="font-weight: 600; font-size: 18px;"><?= $tariff['id'] ?></div>
                            </div>

                            <div class="mobile-card-header">Acceso <span style="float: right;">Presupuestos</span></div>
                            <div class="mobile-card-section">
                                <span class="access-badge <?= $tariff['access'] ?>" data-tariff-id="<?= $tariff['id'] ?>" onclick="toggleAccess(this)">
                                    <?= $tariff['access'] === 'private' ? 'Privado' : 'Público' ?>
                                </span>
                                <div class="budgets-column">
                                    <?php if ($isComplete && $tariff['status'] === 'active'): ?>
                                        <button class="btn-icon black btn-generate" data-tariff-id="<?= $tariff['id'] ?>" title="Generar presupuesto">
                                            <i data-lucide="file-input"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($tariff['budgets_count'] > 0): ?>
                                        <button class="btn-icon black btn-view-budgets" data-tariff-id="<?= $tariff['id'] ?>" title="Ver presupuestos">
                                            <i data-lucide="list"></i>
                                        </button>
                                        <span class="budgets-count"><?= $tariff['budgets_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mobile-card-header">Estado <span style="float: right;">Autor</span></div>
                            <div class="mobile-card-section">
                                <?php if ($isComplete): ?>
                                    <span class="status-badge <?= $tariff['status'] ?>" data-tariff-id="<?= $tariff['id'] ?>" onclick="toggleStatus(this)">
                                        <?= $tariff['status'] === 'active' ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                <?php endif; ?>
                                <div><?= htmlspecialchars($tariff['author_name']) ?></div>
                            </div>

                            <div class="mobile-card-header">Acciones</div>
                            <div class="mobile-card-section">
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
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/tariffs.js"></script>
</body>
</html>