<?php
// {"_META_file_path_": "refor/tariffs.php"}
// Página de gestión de tarifas - mantiene estructura EXACTA del original

require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';

requireAuth();

// Obtener filtros
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$access = $_GET['access'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Obtener todas las tarifas del usuario
$tariffs = getAllTariffs($_SESSION['user_id']);

// Aplicar filtros
if ($search) {
    $tariffs = array_filter($tariffs, function($tariff) use ($search) {
        return stripos($tariff['title'], $search) !== false;
    });
}

if ($status === 'complete') {
    $tariffs = array_filter($tariffs, function($tariff) {
        return !empty($tariff['name']) && !empty($tariff['nif']) && !empty($tariff['address']) && !empty($tariff['contact']);
    });
} elseif ($status === 'incomplete') {
    $tariffs = array_filter($tariffs, function($tariff) {
        return empty($tariff['name']) || empty($tariff['nif']) || empty($tariff['address']) || empty($tariff['contact']);
    });
}

if ($access) {
    $tariffs = array_filter($tariffs, function($tariff) use ($access) {
        return $tariff['access'] === $access;
    });
}

// Obtener autor de cada tarifa
$pdo = getConnection();
foreach ($tariffs as &$tariff) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$tariff['user_id']]);
    $user = $stmt->fetch();
    $tariff['author_name'] = $user['name'] ?? 'Desconocido';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tariffs.css') ?>">
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
                <a href="tariff-form.php" class="btn btn-secondary">Crear Tarifas</a>
                <a href="templates.php" class="btn btn-secondary">Plantillas</a>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($_GET['duplicated'])): ?>
            <div class="alert alert-success">Tarifa duplicada correctamente</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Tarifa eliminada correctamente</div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <!-- Línea 3: Filtros -->
        <div class="filters-row">
            <form method="GET" class="filters-form">
                <input type="text" name="search" placeholder="Buscar por nombre de tarifa..." value="<?= htmlspecialchars($search) ?>" class="filter-input">
                
                <select name="status" class="filter-select">
                    <option value="">Todos los estados</option>
                    <option value="complete" <?= $status === 'complete' ? 'selected' : '' ?>>Completas</option>
                    <option value="incomplete" <?= $status === 'incomplete' ? 'selected' : '' ?>>Incompletas</option>
                </select>
                
                <select name="access" class="filter-select">
                    <option value="">Todos los accesos</option>
                    <option value="private" <?= $access === 'private' ? 'selected' : '' ?>>Privado</option>
                    <option value="public" <?= $access === 'public' ? 'selected' : '' ?>>Público</option>
                </select>
                
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="filter-date">
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="filter-date">
                
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="tariffs.php" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <h2>No hay tarifas disponibles</h2>
                <p>Crea tu primera tarifa para comenzar</p>
                <a href="tariff-form.php" class="btn btn-primary">Crear Primera Tarifa</a>
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
                    <div class="table-row" style="grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1.5fr 1fr;">
                        <div class="tariff-info">
                            <div class="tariff-name"><?= htmlspecialchars($tariff['title']) ?></div>
                        </div>
                        
                        <div><?= htmlspecialchars($tariff['description'] ?? '') ?></div>
                        
                        <div>
                            <select class="status-select" data-tariff-id="<?= $tariff['id'] ?>">
                                <option value="active" <?= $tariff['status'] === 'active' ? 'selected' : '' ?>>Activa</option>
                                <option value="inactive" <?= $tariff['status'] === 'inactive' ? 'selected' : '' ?>>Inactiva</option>
                            </select>
                        </div>
                        
                        <div>
                            <select class="access-select" data-tariff-id="<?= $tariff['id'] ?>">
                                <option value="private" <?= $tariff['access'] === 'private' ? 'selected' : '' ?>>Privado</option>
                                <option value="public" <?= $tariff['access'] === 'public' ? 'selected' : '' ?>>Público</option>
                            </select>
                        </div>
                        
                        <div><?= formatDate($tariff['created_at'], 'd/m/Y') ?></div>
                        
                        <div><?= htmlspecialchars($tariff['author_name']) ?></div>
                        
                        <div class="budget-actions">
                            <?php if ($tariff['status'] === 'active'): ?>
                                <button class="btn-icon green btn-generate-budget" data-tariff-uuid="<?= $tariff['uuid'] ?>" title="Generar presupuesto">
                                    <i data-lucide="panel-top"></i>
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

    <script src="<?= asset('js/tariffs.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>