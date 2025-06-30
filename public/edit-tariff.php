<?php
// {"_META_file_path_": "public/edit-tariff.php"}
// Editar tarifa

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: tariffs.php');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT t.*, c.* 
    FROM tariffs t 
    LEFT JOIN company_config c ON t.id = c.tariff_id 
    WHERE t.id = ?
");
$stmt->execute([$id]);
$tariff = $stmt->fetch();

if (!$tariff) {
    header('Location: tariffs.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tariffName = trim($_POST['tariff_name'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    
    if (empty($tariffName)) $errors[] = 'El nombre de la tarifa es obligatorio';
    if (empty($companyName)) $errors[] = 'El nombre de la empresa es obligatorio';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE tariffs SET name = ? WHERE id = ?");
            $stmt->execute([$tariffName, $id]);
            
            $stmt = $pdo->prepare("
                UPDATE company_config SET 
                name = ?, nif = ?, address = ?, contact = ?, logo_url = ?, 
                primary_color = ?, secondary_color = ?
                WHERE tariff_id = ?
            ");
            $stmt->execute([
                $companyName,
                $_POST['company_nif'] ?? '',
                $_POST['company_address'] ?? '',
                $_POST['company_contact'] ?? '',
                $_POST['logo_url'] ?? '',
                $_POST['primary_color'] ?? '#e8951c',
                $_POST['secondary_color'] ?? '#109c61',
                $id
            ]);
            
            $pdo->commit();
            $success = true;
            
            // Recargar datos
            $stmt = $pdo->prepare("
                SELECT t.*, c.* 
                FROM tariffs t 
                LEFT JOIN company_config c ON t.id = c.tariff_id 
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $tariff = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarifa - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tariffs-styles.css') ?>">
    <style>
        .form-container { max-width: 600px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { resize: vertical; height: 60px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn-submit { background: #e8951c; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #d4841a; }
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

    <div class="form-container">
        <h1>Editar Tarifa</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Tarifa actualizada correctamente</div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="tariff_name">Nombre de la Tarifa:</label>
                <input type="text" id="tariff_name" name="tariff_name" required 
                       value="<?= htmlspecialchars($tariff['name']) ?>">
            </div>
            
            <div class="form-group">
                <label for="company_name">Nombre de la Empresa:</label>
                <input type="text" id="company_name" name="company_name" required 
                       value="<?= htmlspecialchars($tariff['name'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="company_nif">NIF/CIF:</label>
                    <input type="text" id="company_nif" name="company_nif" 
                           value="<?= htmlspecialchars($tariff['nif'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="logo_url">URL del Logo:</label>
                    <input type="url" id="logo_url" name="logo_url" 
                           value="<?= htmlspecialchars($tariff['logo_url'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="company_address">Dirección:</label>
                <textarea id="company_address" name="company_address"><?= htmlspecialchars($tariff['address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="company_contact">Contacto:</label>
                <input type="text" id="company_contact" name="company_contact" 
                       value="<?= htmlspecialchars($tariff['contact'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="primary_color">Color Primario:</label>
                    <input type="color" id="primary_color" name="primary_color" 
                           value="<?= $tariff['primary_color'] ?? '#e8951c' ?>">
                </div>
                <div class="form-group">
                    <label for="secondary_color">Color Secundario:</label>
                    <input type="color" id="secondary_color" name="secondary_color" 
                           value="<?= $tariff['secondary_color'] ?? '#109c61' ?>">
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn-submit">Actualizar Tarifa</button>
                <a href="tariffs.php" style="margin-left: 1rem;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>