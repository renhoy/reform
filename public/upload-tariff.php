<?php
// {"_META_file_path_": "public/upload-tariff.php"}
// Crear tarifa corregida sin default_config

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
require_once SRC_PATH . '/utils/csv-processor.php';
requireAuth();

$pdo = getConnection();
$errors = [];

// Valores por defecto sin tabla
$defaults = [
    'name' => 'Jeyca Tecnología y Medio Ambiente, S.L.',
    'nif' => 'B91707703',
    'address' => 'C/ Pimienta, 6 - 41200, Alcalá del Río (Sevilla)',
    'contact' => '955 650 626 - soporte@jeyca.net',
    'logo_url' => '',
    'template' => '41200-00001',
    'primary_color' => '#e8951c',
    'secondary_color' => '#109c61',
    'summary_note' => 'Una vez recibida la confirmación del presupuesto procederemos a la facturación del 50% como anticipo y el 50% restante a la finalización de los trabajos.',
    'conditions_note' => 'Este presupuesto incluye materiales y mano de obra. No incluye tasas ni licencias municipales. Validez del presupuesto: 30 días.',
    'legal_note' => 'Al marcar esta casilla acepta nuestra política de privacidad y el tratamiento de sus datos personales conforme al RGPD.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tariffName = trim($_POST['tariff_name'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $companyNif = trim($_POST['company_nif'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    $companyContact = trim($_POST['company_contact'] ?? '');
    
    if (empty($tariffName)) $errors[] = 'El nombre de la tarifa es obligatorio';
    if (empty($companyName)) $errors[] = 'El nombre de la empresa es obligatorio';
    if (empty($companyNif)) $errors[] = 'El NIF de la empresa es obligatorio';
    if (empty($companyAddress)) $errors[] = 'La dirección es obligatoria';
    if (empty($companyContact)) $errors[] = 'El contacto es obligatorio';
    
    if (empty($errors)) {
        try {
            $csvData = $_POST['csv_data'] ?? '';
            
            if (empty($csvData)) {
                $errors[] = 'No se encontraron datos del archivo CSV procesado';
            } else {
                $jsonData = json_decode($csvData, true);
                
                if (!$jsonData || !is_array($jsonData)) {
                    $errors[] = 'Error al procesar los datos del CSV';
                } else {
                    $filePath = null;
                    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                        $fileName = time() . '_' . $_FILES['csv_file']['name'];
                        $filePath = UPLOAD_DIR . $fileName;
                        
                        if (!is_dir(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0755, true);
                        }
                        
                        move_uploaded_file($_FILES['csv_file']['tmp_name'], $filePath);
                    }
                    
                    $pdo->beginTransaction();
                    
                    $uuid = generateUUID();
                    $stmt = $pdo->prepare("
                        INSERT INTO tariffs 
                        (uuid, title, description, logo_url, name, nif, address, contact, 
                         template, primary_color, secondary_color, summary_note, conditions_note, 
                         access, legal_note, json_tariff_data, user_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'private', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $uuid,
                        $tariffName,
                        $_POST['description'] ?? '',
                        $_POST['logo_url'] ?? $defaults['logo_url'],
                        $companyName,
                        $companyNif,
                        $companyAddress,
                        $companyContact,
                        $_POST['template'] ?? $defaults['template'],
                        $_POST['primary_color'] ?? $defaults['primary_color'],
                        $_POST['secondary_color'] ?? $defaults['secondary_color'],
                        $_POST['summary_note'] ?? $defaults['summary_note'],
                        $_POST['conditions_note'] ?? $defaults['conditions_note'],
                        $_POST['legal_note'] ?? $defaults['legal_note'],
                        json_encode($jsonData),
                        $_SESSION['user_id']
                    ]);
                    
                    $pdo->commit();
                    header('Location: tariffs.php?success=1');
                    exit;
                }
            }
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Tarifa - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/common-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <style>
        .form-container { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 2rem; 
            margin-bottom: 2rem; 
        }
        .form-section { 
            background: white; 
            padding: 1.5rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .form-section h3 { 
            color: var(--secondary-orange); 
            margin-bottom: 1rem; 
        }
        .form-group { 
            margin-bottom: 1rem; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 500; 
        }
        .form-group input, .form-group textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 1rem; 
        }
        .action-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
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
        <div class="action-bar">
            <a href="tariffs.php" class="btn btn-secondary">← Volver a Tarifas</a>
            <button type="submit" form="tariffForm" class="btn btn-secondary">Guardar Tarifa</button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="tariffForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="csv_data" name="csv_data">
            
            <div class="form-container">
                <div class="form-section">
                    <h3>Información de la Tarifa</h3>
                    <div class="form-group">
                        <label for="tariff_name">Nombre de la Tarifa:</label>
                        <input type="text" id="tariff_name" name="tariff_name" required value="<?= htmlspecialchars($_POST['tariff_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="description">Descripción:</label>
                        <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Datos de la Empresa</h3>
                    <div class="form-group">
                        <label for="company_name">Nombre de la Empresa:</label>
                        <input type="text" id="company_name" name="company_name" required value="<?= htmlspecialchars($_POST['company_name'] ?? $defaults['name']) ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_nif">NIF/CIF:</label>
                            <input type="text" id="company_nif" name="company_nif" required value="<?= htmlspecialchars($_POST['company_nif'] ?? $defaults['nif']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="logo_url">URL del Logo:</label>
                            <input type="url" id="logo_url" name="logo_url" value="<?= htmlspecialchars($_POST['logo_url'] ?? $defaults['logo_url']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="company_address">Dirección:</label>
                        <textarea id="company_address" name="company_address" required><?= htmlspecialchars($_POST['company_address'] ?? $defaults['address']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="company_contact">Contacto:</label>
                        <input type="text" id="company_contact" name="company_contact" required value="<?= htmlspecialchars($_POST['company_contact'] ?? $defaults['contact']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Archivo CSV de Tarifas</h3>
                <div class="form-group">
                    <label for="csv_file">Seleccionar archivo CSV:</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                <p><small>El archivo debe contener las columnas: Nivel, ID, Nombre, Descripción, Ud, %IVA, PVP</small></p>
            </div>
        </form>
    </div>
</body>
</html>