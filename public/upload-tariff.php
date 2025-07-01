<?php
// {"_META_file_path_": "public/upload-tariff.php"}
// Crear tarifa completa

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();
$errors = [];

// Obtener plantilla por defecto del sistema
$defaultTemplate = [
    'name' => 'Empresa Ejemplo',
    'nif' => 'B12345678',
    'address' => 'Calle Ejemplo, 123 - 12345 Ciudad (Provincia)',
    'contact' => '900 123 456 - info@empresa.com - www.empresa.com',
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
    $description = trim($_POST['description'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $companyNif = trim($_POST['company_nif'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    $companyContact = trim($_POST['company_contact'] ?? '');
    
    if (empty($tariffName)) $errors[] = 'El nombre de la tarifa es obligatorio';
    if (empty($companyName)) $errors[] = 'El nombre de la empresa es obligatorio';
    if (empty($companyNif)) $errors[] = 'El NIF de la empresa es obligatorio';
    if (empty($companyAddress)) $errors[] = 'La dirección es obligatoria';
    if (empty($companyContact)) $errors[] = 'El contacto es obligatorio';
    
    $csvData = $_POST['csv_data'] ?? '';
    if (empty($csvData)) {
        $errors[] = 'Debe importar un archivo CSV válido';
    }
    
    if (empty($errors)) {
        try {
            $jsonData = json_decode($csvData, true);
            
            if (!$jsonData || !is_array($jsonData)) {
                $errors[] = 'Error al procesar los datos del CSV';
            } else {
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
                    $description,
                    $_POST['logo_url'] ?? '',
                    $companyName,
                    $companyNif,
                    $companyAddress,
                    $companyContact,
                    $_POST['template'] ?? $defaultTemplate['template'],
                    $_POST['primary_color'] ?? $defaultTemplate['primary_color'],
                    $_POST['secondary_color'] ?? $defaultTemplate['secondary_color'],
                    $_POST['summary_note'] ?? $defaultTemplate['summary_note'],
                    $_POST['conditions_note'] ?? $defaultTemplate['conditions_note'],
                    $_POST['legal_note'] ?? $defaultTemplate['legal_note'],
                    $csvData,
                    $_SESSION['user_id']
                ]);
                
                $pdo->commit();
                header('Location: tariffs.php?success=1');
                exit;
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
    <title>Crear Tarifa - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/common-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/new-tariff-styles.css') ?>">
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
            <h1 class="page-title">Crear Tarifa</h1>
            <div class="header-buttons">
                <a href="tariffs.php" class="btn btn-secondary">Tarifas</a>
                <button type="submit" form="tariffForm" class="btn btn-secondary">Guardar Tarifa</button>
                <a href="templates.php" class="btn btn-secondary">Plantillas</a>
                <button type="button" id="saveAsTemplate" class="btn btn-secondary">Guardar Plantilla</button>
            </div>
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

        <!-- Línea 2: Formulario -->
        <div class="message-container"></div>
        
        <form id="tariffForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="csv_data" name="csv_data">
            <input type="hidden" id="logo_url" name="logo_url">
            
            <div class="form-main-container">
                <!-- Columna 1: Formularios -->
                <div class="form-column">
                    <!-- Card 1: Información de la Tarifa -->
                    <div class="form-section">
                        <div class="section-header">Información de la Tarifa</div>
                        <div class="section-content">
                            <div class="form-group">
                                <label for="tariff_name">Nombre de la Tarifa:</label>
                                <input type="text" id="tariff_name" name="tariff_name" required value="<?= htmlspecialchars($_POST['tariff_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="description">Descripción:</label>
                                <input type="text" id="description" name="description" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Datos de la Empresa -->
                    <div class="form-section">
                        <div class="section-header">Datos de la Empresa (Encabezado de Formulario y del PDF)</div>
                        <div class="section-content">
                            <!-- Selector de imagen -->
                            <div class="form-group">
                                <label>Logo de la Empresa:</label>
                                <div class="image-upload-area" id="imageUploadArea">
                                    <input type="file" id="logo_file" accept="image/jpeg,image/jpg,image/png,image/svg+xml" style="display: none;">
                                    <p class="upload-text">Arrastra aquí o selecciona tu archivo de imagen</p>
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('logo_file').click()">Seleccionar Imagen</button>
                                </div>
                                <div class="image-preview-container" id="imagePreviewContainer" style="display: none;">
                                    <img id="imagePreview" class="image-preview" src="" alt="Vista previa">
                                    <button type="button" class="image-remove-btn" id="removeImageBtn">×</button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="company_name">Nombre de la Empresa:</label>
                                <input type="text" id="company_name" name="company_name" required value="<?= htmlspecialchars($_POST['company_name'] ?? $defaultTemplate['name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="company_nif">NIF/CIF:</label>
                                <input type="text" id="company_nif" name="company_nif" required value="<?= htmlspecialchars($_POST['company_nif'] ?? $defaultTemplate['nif']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="company_address">Dirección (Calle, Número - CP, Localidad, (Provincia)):</label>
                                <input type="text" id="company_address" name="company_address" required value="<?= htmlspecialchars($_POST['company_address'] ?? $defaultTemplate['address']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="company_contact">Contacto (Teléfono - Email - Web):</label>
                                <input type="text" id="company_contact" name="company_contact" required value="<?= htmlspecialchars($_POST['company_contact'] ?? $defaultTemplate['contact']) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Configuración del PDF -->
                    <div class="form-section">
                        <div class="section-header">Configuración del PDF</div>
                        <div class="section-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="validity_days">Días de Validez:</label>
                                    <input type="number" id="validity_days" name="validity_days" min="1" value="<?= htmlspecialchars($_POST['validity_days'] ?? '30') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="template">Plantilla PDF:</label>
                                    <input type="text" id="template" name="template" value="<?= htmlspecialchars($_POST['template'] ?? $defaultTemplate['template']) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="primary_color">Color Primario:</label>
                                    <input type="color" id="primary_color" name="primary_color" value="<?= $_POST['primary_color'] ?? $defaultTemplate['primary_color'] ?>">
                                </div>
                                <div class="form-group">
                                    <label for="secondary_color">Color Secundario:</label>
                                    <input type="color" id="secondary_color" name="secondary_color" value="<?= $_POST['secondary_color'] ?? $defaultTemplate['secondary_color'] ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Textos Legales del PDF -->
                    <div class="form-section">
                        <div class="section-header">Textos Legales del PDF</div>
                        <div class="section-content">
                            <div class="form-group">
                                <label for="summary_note">Nota del Resumen (Aceptación y Métodos de Pago):</label>
                                <textarea id="summary_note" name="summary_note"><?= htmlspecialchars($_POST['summary_note'] ?? $defaultTemplate['summary_note']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="conditions_note">Condiciones del Presupuesto (Cláusulas, garantías, incluido o no, etc):</label>
                                <textarea id="conditions_note" name="conditions_note"><?= htmlspecialchars($_POST['conditions_note'] ?? $defaultTemplate['conditions_note']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: Condiciones Legales del Formulario -->
                    <div class="form-section">
                        <div class="section-header">Condiciones Legales del Formulario</div>
                        <div class="section-content">
                            <div class="form-group">
                                <label for="legal_note">Información legal del Formulario:</label>
                                <textarea id="legal_note" name="legal_note"><?= htmlspecialchars($_POST['legal_note'] ?? $defaultTemplate['legal_note']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: CSV Upload y Preview -->
                <div class="form-column">
                    <!-- Estado inicial: Upload CSV -->
                    <div class="form-section" id="csvUploadSection">
                        <div class="section-header">Archivo CSV de Tarifas</div>
                        <div class="section-content">
                            <div class="upload-area">
                                <input type="file" id="csv_file" accept=".csv" style="display: none;">
                                <p class="upload-text">Arrastra aquí o selecciona tu archivo CSV</p>
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('csv_file').click()">Importar CSV</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="csvFormatSection">
                        <div class="section-header">Formato requerido:</div>
                        <div class="section-content">
                            <div class="csv-example">"Nivel","ID","Nombre","Descripción","Ud","%IVA","PVP"
"Capítulo",1,"Nombre del Capítulo 1",,,,
"Subcapítulo","1.1","Nombre del Subcapítulo 1.1",,,,
"Apartado","1.1.1","Nombre del Apartado 1.1.1",,,,
"Partida","1.1.1.1","Nombre del Partida 1.1.1.1","Descripción de la Partida 1.1.1.1","Unidad","5,00","125,00"</div>
                            <button type="button" id="downloadTemplate" class="btn btn-secondary">Descargar Plantilla</button>
                        </div>
                    </div>

                    <!-- Estado procesado: Preview -->
                    <div class="form-section" id="previewSection" style="display: none;">
                        <div class="section-header">Previsualización de Tarifa</div>
                        <div class="section-content">
                            <div class="preview-actions">
                                <button type="button" id="exportPreview" class="btn btn-secondary">Descargar</button>
                                <button type="button" id="deletePreview" class="btn btn-danger">Borrar</button>
                            </div>
                            <div class="hierarchy-container" id="hierarchyOutput"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="<?= asset('js/new-tariff-handler.js') ?>"></script>
</body>
</html>