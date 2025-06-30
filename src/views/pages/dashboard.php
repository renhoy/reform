<?php
// {"_META_file_path_": "src/views/pages/dashboard.php"}
// Panel principal simplificado

requireAuth();

$pdo = getConnection();
$tariffsCount = $pdo->query("SELECT COUNT(*) FROM tariffs")->fetchColumn();
$budgetsCount = $pdo->query("SELECT COUNT(*) FROM budgets")->fetchColumn();
$recentBudgets = $pdo->query("SELECT COUNT(*) FROM budgets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

$title = "Dashboard";
$styles = ['dashboard-styles'];
?>

<?php ob_start(); ?>
<div class="container">
    <div class="welcome-section">
        <h1>Bienvenido al Generador de Presupuestos</h1>
        <p>Gestiona tus tarifas y genera presupuestos profesionales de forma rápida y sencilla.</p>
    </div>

    <div class="quick-actions">
        <div class="action-card">
            <div class="card-icon">📊</div>
            <h3>Gestionar Tarifas</h3>
            <p>Crea, edita y organiza tus tarifas de precios</p>
            <a href="<?= url('tariffs') ?>" class="btn btn-primary">Ir a Tarifas</a>
        </div>

        <div class="action-card">
            <div class="card-icon">📋</div>
            <h3>Ver Presupuestos</h3>
            <p>Consulta todos los presupuestos generados</p>
            <a href="<?= url('budgets') ?>" class="btn btn-secondary">Ver Presupuestos</a>
        </div>

        <div class="action-card">
            <div class="card-icon">✨</div>
            <h3>Crear Nueva Tarifa</h3>
            <p>Comienza creando una nueva tarifa de precios</p>
            <a href="<?= url('tariffs/new') ?>" class="btn btn-info">Nueva Tarifa</a>
        </div>
    </div>

    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-number"><?= $tariffsCount ?></div>
            <div class="stat-label">Tarifas Creadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $budgetsCount ?></div>
            <div class="stat-label">Presupuestos Generados</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $recentBudgets ?></div>
            <div class="stat-label">Esta Semana</div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>