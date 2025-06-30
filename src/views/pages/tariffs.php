<?php
// {"_META_file_path_": "tariffs.php"}
// Página principal de gestión de tarifas

require_once 'config.php';
requireAuth();

$pdo = getConnection();

// Manejar duplicación de tarifa
if (isset($_GET['duplicate']) && $_GET['duplicate']) {
    $original_id = $_GET['duplicate'];
    
    try {
        $pdo->beginTransaction();
        
        // Obtener tarifa original
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
        $stmt->execute([$original_id]);
        $original_tariff = $stmt->fetch();
        
        if ($original_tariff) {
            // Crear nueva tarifa
            $new_name = $original_tariff['name'] . ' (Copia)';
            $stmt = $pdo->prepare("INSERT INTO tariffs (name, file_path, json_data) VALUES (?, ?, ?)");
            $stmt->execute([$new_name, $original_tariff['file_path'], $original_tariff['json_data']]);
            $new_tariff_id = $pdo->lastInsertId();
            
            // Duplicar configuración de empresa
            $stmt = $pdo->prepare("SELECT * FROM company_config WHERE tariff_id = ?");
            $stmt->execute([$original_id]);
            $original_config = $stmt->fetch();
            
            if ($original_config) {
                $stmt = $pdo->prepare("
                    INSERT INTO company_config 
                    (tariff_id, name, nif, address, contact, logo_url, template, primary_color, secondary_color, summary_note, conditions_note, legal_note) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $new_tariff_id,
                    $original_config['name'],
                    $original_config['nif'],
                    $original_config['address'],
                    $original_config['contact'],
                    $original_config['logo_url'],
                    $original_config['template'],
                    $original_config['primary_color'],
                    $original_config['secondary_color'],
                    $original_config['summary_note'],
                    $original_config['conditions_note'],
                    $original_config['legal_note']
                ]);
            }
        }
        
        $pdo->commit();
        header('Location: tariffs.php?duplicated=1');
        exit;
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Error al duplicar: ' . $e->getMessage();
    }
}

// Manejar borrado de tarifa
if (isset($_GET['delete']) && $_GET['delete']) {
    $tariff_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ?");
        $stmt->execute([$tariff_id]);
        header('Location: tariffs.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $error = 'Error al borrar: ' . $e->getMessage();
    }
}

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
?>
<!DOCTYPE html>
<!-- {"_META_file_path_": "tariffs.php"} -->
<!-- Página principal de gestión de tarifas -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="css/header-styles.css">
    <link rel="stylesheet" href="css/tariffs-styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

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

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="actions-bar">
            <a href="upload-tariff.php" class="btn btn-primary">Crear Tarifa</a>
            <a href="budgets.php" class="btn btn-secondary">Ver Presupuestos</a>
        </div>

        <?php if (empty($tariffs)): ?>
            <div class="empty-state">
                <h2>No hay tarifas disponibles</h2>
                <p>Crea tu primera tarifa para comenzar a generar presupuestos</p>
                <a href="upload-tariff.php" class="btn btn-primary">Crear Primera Tarifa</a>
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
                                <a href="form.php?tariff_id=<?= $tariff['id'] ?>" class="btn btn-primary btn-small">Crear Presupuesto</a>
                            <?php endif; ?>
                            <a href="edit-tariff.php?id=<?= $tariff['id'] ?>" class="btn btn-secondary btn-small">Editar</a>
                            <a href="tariffs.php?duplicate=<?= $tariff['id'] ?>" class="btn btn-info btn-small" onclick="return confirm('¿Duplicar esta tarifa?')">Duplicar</a>
                            <a href="tariffs.php?delete=<?= $tariff['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Eliminar esta tarifa? Esta acción no se puede deshacer.')">Borrar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>