<?php
// {"_META_file_path_": "public/tariffs.php"}
// Tarifas con iconos y estructura corregida

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();
$tariffs = $pdo->query("
    SELECT t.*, t.name as company_name,
           (SELECT COUNT(*) FROM budgets b WHERE b.tariff_id = t.id) as budget_count
    FROM tariffs t 
    ORDER BY t.created_at DESC
")->fetchAll();

function isComplete($tariff) {
    return !empty($tariff['company_name']) && 
           !empty($tariff['nif']) && 
           !empty($tariff['address']) && 
           !empty($tariff['contact']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tariffs-styles.css') ?>">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin: 0 2px;
        }
        
        .icon-btn:hover {
            transform: translateY(-1px);
        }
        
        .icon-btn.generate { background: #e8951c; color: white; }
        .icon-btn.view { background: #17a2b8; color: white; }
        .icon-btn.edit { background: #109c61; color: white; }
        .icon-btn.duplicate { background: #6c757d; color: white; }
        .icon-btn.delete { background: #dc3545; color: white; }
        
        .icon-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .budget-buttons {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        
        .budget-count {
            background: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 4px;
        }
        
        .table-header-updated {
            background: #e8951c;
            color: white;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1.5fr 2fr;
            padding: 1rem 2rem;
            font-weight: bold;
            gap: 1rem;
        }
        
        .table-row-updated {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1.5fr 2fr;
            padding: 1rem 2rem;
            border-bottom: 1px solid #eee;
            align-items: center;
            gap: 1rem;
            transition: background 0.3s;
        }
        
        .tooltip {
            position: relative;
        }
        
        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
        }
    </style>
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
        <div class="page-header">
            <h1>Tarifas</h1>
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
            <a href="upload-tariff.php" class="btn btn-primary">Crear Tarifa</a>
        </div>

        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <h2>No hay tarifas disponibles</h2>
                <p>Crea tu primera tarifa para comenzar</p>
                <a href="upload-tariff.php" class="btn btn-primary">Crear Primera Tarifa</a>
            </div>
        <?php else: ?>
            <div class="tariffs-table">
                <div class="table-header-updated">
                    <div>Nombre de Tarifa</div>
                    <div>Descripción</div>
                    <div>Estado</div>
                    <div>Fecha</div>
                    <div>Autor</div>
                    <div>Acceso</div>
                    <div>Presupuestos</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($tariffs as $tariff): ?>
                    <?php $complete = isComplete($tariff); ?>
                    <div class="table-row-updated">
                        <div class="tariff-info">
                            <div class="tariff-name"><?= htmlspecialchars($tariff['name']) ?></div>
                            <?php if (!$complete): ?>
                                <span class="incomplete-badge">Incompleta</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tariff-description">
                            <?= htmlspecialchars($tariff['description'] ?? 'Sin descripción') ?>
                        </div>
                        
                        <div class="tariff-status">
                            <?php if ($complete): ?>
                                <select class="status-select" onchange="updateTariffStatus(<?= $tariff['id'] ?>, this.value)">
                                    <option value="active" selected>Activa</option>
                                    <option value="inactive">Inactiva</option>
                                </select>
                            <?php else: ?>
                                <span class="incomplete-badge">Incompleta</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tariff-date">
                            <?= date('d/m/Y', strtotime($tariff['created_at'])) ?>
                        </div>
                        
                        <div class="tariff-author">
                            Admin
                        </div>
                        
                        <div class="tariff-access">
                            <select class="access-select" onchange="updateTariffAccess(<?= $tariff['id'] ?>, this.value)">
                                <option value="private" selected>Privado</option>
                                <option value="public">Público</option>
                            </select>
                        </div>
                        
                        <div class="budget-buttons">
                            <?php if ($complete): ?>
                                <a href="form.php?tariff_id=<?= $tariff['id'] ?>" 
                                   class="icon-btn generate tooltip" 
                                   data-tooltip="Generar Presupuesto">
                                    <i data-lucide="panel-top"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($tariff['budget_count'] > 0): ?>
                                <a href="budgets.php?tariff_id=<?= $tariff['id'] ?>" 
                                   class="icon-btn view tooltip" 
                                   data-tooltip="Ver Presupuestos">
                                    <i data-lucide="eye"></i>
                                    <span class="budget-count"><?= $tariff['budget_count'] ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tariff-actions">
                            <a href="edit-tariff.php?id=<?= $tariff['id'] ?>" 
                               class="icon-btn edit tooltip" 
                               data-tooltip="Editar">
                                <i data-lucide="pencil"></i>
                            </a>
                            
                            <a href="duplicate-tariff.php?id=<?= $tariff['id'] ?>" 
                               class="icon-btn duplicate tooltip" 
                               data-tooltip="Duplicar"
                               onclick="return confirm('¿Duplicar esta tarifa?')">
                                <i data-lucide="copy"></i>
                            </a>
                            
                            <a href="delete-tariff.php?id=<?= $tariff['id'] ?>" 
                               class="icon-btn delete tooltip" 
                               data-tooltip="Borrar"
                               onclick="return confirm('¿Eliminar esta tarifa?')">
                                <i data-lucide="trash-2"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Inicializar Lucide icons
        lucide.createIcons();
        
        function updateTariffStatus(tariffId, status) {
            // Implementar AJAX para actualizar estado
            console.log('Updating tariff', tariffId, 'to status', status);
        }
        
        function updateTariffAccess(tariffId, access) {
            // Implementar AJAX para actualizar acceso
            console.log('Updating tariff', tariffId, 'to access', access);
        }
    </script>
</body>
</html>