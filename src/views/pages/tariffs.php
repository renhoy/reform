<?php
// {"_META_file_path_": "src/views/pages/tariffs.php"}
// Página principal de gestión de tarifas

requireAuth();

$pdo = getConnection();

// Obtener todas las tarifas
$tariffs = $pdo->query("
    SELECT t.*, c.name as company_name, c.nif, c.address, c.contact 
    FROM tariffs t 
    LEFT JOIN company_config c ON t.id = c.tariff_id 
    ORDER BY t.created_at DESC
")->fetchAll();

// Función para verificar si una tarifa está completa
function isComplete($tariff) {
    return !empty($tariff['company_name']) && 
           !empty($tariff['nif']) && 
           !empty($tariff['address']) && 
           !empty($tariff['contact']);
}

$title = "Tarifas";
$styles = ['tariffs-styles'];
?>

<?php ob_start(); ?>
<div class="container">
    <div class="page-header">
        <h1>Gestión de Tarifas</h1>
    </div>

    <?php if (isset($_GET['duplicated'])): ?>
        <div class="alert alert-success">Tarifa duplicada correctamente</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Tarifa eliminada correctamente</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="actions-bar">
        <a href="<?= url('tariffs/new') ?>" class="btn btn-primary">Crear Tarifa</a>
    </div>

    <?php if (empty($tariffs)): ?>
        <div class="empty-state">
            <h2>No hay tarifas disponibles</h2>
            <p>Crea tu primera tarifa para comenzar a generar presupuestos</p>
            <a href="<?= url('tariffs/new') ?>" class="btn btn-primary">Crear Primera Tarifa</a>
        </div>
    <?php else: ?>
        <div class="tariffs-table">
            <div class="table-header">
                <div>Nombre de Tarifa</div>
                <div>Fecha</div>
                <div>Acciones</div>
            </div>
            
            <?php foreach ($tariffs as $tariff): ?>
                <?php $complete = isComplete($tariff); ?>
                <div class="table-row">
                    <div class="tariff-info">
                        <div class="tariff-name"><?= htmlspecialchars($tariff['name']) ?></div>
                        <?php if (!$complete): ?>
                            <span class="incomplete-badge">Incompleta</span>
                        <?php endif; ?>
                    </div>
                    <div class="tariff-date">
                        <?= date('d/m/Y H:i', strtotime($tariff['created_at'])) ?>
                    </div>
                    <div class="tariff-actions">
                        <?php if ($complete): ?>
                            <a href="<?= url('budgets/form/' . $tariff['id']) ?>" class="btn btn-primary btn-small">Crear Presupuesto</a>
                        <?php endif; ?>
                        <a href="<?= url('tariffs/edit/' . $tariff['id']) ?>" class="btn btn-secondary btn-small">Editar</a>
                        <a href="<?= url('tariffs/duplicate/' . $tariff['id']) ?>" class="btn btn-info btn-small" onclick="return confirm('¿Duplicar esta tarifa?')">Duplicar</a>
                        <a href="<?= url('tariffs/delete/' . $tariff['id']) ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Eliminar esta tarifa?')">Borrar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); ?>