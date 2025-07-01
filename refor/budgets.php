<?php
// {"_META_file_path_": "refor/budgets.php"}
// Página de gestión de presupuestos - mantiene estructura definida

require_once 'includes/config.php';
require_once 'includes/budget-helpers.php';

requireAuth();

// Verificar presupuestos expirados
checkExpiredBudgets();

// Obtener filtros
$filters = [
    'status' => $_GET['status'] ?? '',
    'client_search' => $_GET['client_search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Obtener presupuestos y estadísticas
$budgets = getAllBudgets($_SESSION['user_id'], $filters);
$stats = getBudgetStats($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/budgets.css') ?>">
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
        <div class="page-header">
            <h1>Presupuestos</h1>
        </div>

        <!-- Línea 2: Resumen Estadísticas -->
        <div class="stats-summary">
            <span class="stats-text">
                Presupuestos Realizados (<?= $stats['total'] ?>): 
                <span class="stat-item draft">Borrador (<?= $stats['draft'] ?>)</span>
                <span class="stat-item pending">Pendientes (<?= $stats['pending'] ?>)</span>
                <span class="stat-item sent">Enviados (<?= $stats['sent'] ?>)</span>
                <span class="stat-item approved">Aprobados (<?= $stats['approved'] ?>)</span>
                <span class="stat-item rejected">Rechazados (<?= $stats['rejected'] ?>)</span>
                <span class="stat-item expired">Expirados (<?= $stats['expired'] ?>)</span>
            </span>
        </div>

        <!-- Línea 3: Buscador y Filtros -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="search-input">
                    <input type="text" name="client_search" placeholder="Buscar por cliente..." 
                           value="<?= htmlspecialchars($filters['client_search']) ?>">
                </div>
                
                <select name="status" class="filter-select">
                    <option value="">Todos los estados</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="sent" <?= $filters['status'] === 'sent' ? 'selected' : '' ?>>Enviado</option>
                    <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                    <option value="expired" <?= $filters['status'] === 'expired' ? 'selected' : '' ?>>Expirado</option>
                </select>
                
                <input type="date" name="date_from" placeholder="Desde" 
                       value="<?= htmlspecialchars($filters['date_from']) ?>">
                
                <input type="date" name="date_to" placeholder="Hasta" 
                       value="<?= htmlspecialchars($filters['date_to']) ?>">
                
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="budgets.php" class="btn btn-secondary">Limpiar</a>
            </form>
        </div>


        <!-- Línea 4: Listado Presupuestos -->
        <?php if (empty($budgets)): ?>
            <div class="empty-state">
                <h2>No hay presupuestos</h2>
                <p>Crea tu primera tarifa para generar presupuestos</p>
                <a href="tariffs.php" class="btn btn-primary">Ver Tarifas</a>
            </div>
        <?php else: ?>
            <div class="budgets-table">
                <div class="table-header">
                    <div>Cliente (NIF/NIE)</div>
                    <div class="notes-column">
                        <i data-lucide="edit-3" class="notes-icon"></i>
                    </div>
                    <div>Total</div>
                    <div>Tarifa</div>
                    <div>Estado</div>
                    <div>Fecha</div>
                    <div>Autor</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($budgets as $budget): ?>
                    <?php include 'templates/budget-table-row.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="<?= asset('js/budgets.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>