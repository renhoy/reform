<?php
// {"_META_file_path_": "public/upload-tariff.php"}
// Nueva tarifa con funcionalidad completa

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();
$errors = [];

// Obtener plantilla estándar del sistema
$stmt = $pdo->prepare("SELECT * FROM templates WHERE is_system = 1 LIMIT 1");
$stmt->execute();
$systemTemplate = $stmt->fetch();

// Usar plantilla estándar o cargar plantilla específica
$templateData = null;
if (isset($_GET['template']) && $_GET['template']) {
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$_GET['template']]);
    $template = $stmt->fetch();
    if ($template) {
        $templateData = json_decode($template['template_data'], true);
    }
} elseif ($systemTemplate) {
    $templateData = json_decode($systemTemplate['template_data'], true);
}

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
                    $uuid = generateUUID();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO tariffs 
                        (uuid, title, description, name, nif, address, contact, logo_url, 
                         template, primary_color, secondary_color, summary_note, conditions_note, 
                         legal_note, json_tariff_data, user_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $uuid,
                        $tariffName,
                        $_POST['description'] ?? '',
                        $companyName,
                        $companyNif,
                        $companyAddress,
                        $companyContact,
                        $_POST['logo_url'] ?? '',
                        $_POST['template'] ?? '41200-00001',
                        $_POST['primary_color'] ?? '#e8951c',
                        $_POST['secondary_color'] ?? '#109c61',
                        $_POST['summary_note'] ?? '',
                        $_POST['conditions_note'] ?? '',
                        $_POST['legal_note'] ?? '',
                        json_encode($jsonData),
                        $_SESSION['user_id']
                    ]);
                    
                    header('Location: tariffs.php?success=1');
                    exit;
                }
            }
        } catch (Exception $e) {
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
    <link rel="stylesheet" href="assets/css/header-styles.css">
    <link rel="stylesheet" href="assets/css/new-tariff-styles.css">
</head>
<body>
    <?php include SRC_PATH . '/views/templates/header.php'; ?>

    <div class="container">
        <!-- Línea 1: Botones de acción -->
        <div class="action-bar">
            <a href="tariffs.php" class="btn btn-secondary">Tarifas</a>
            <button type="submit" form="tariffForm" class="btn btn-primary">Guardar Tarifa</button>
            <a href="templates.php" class="btn btn-info">Plantillas</a>
            <button type="button" id="saveAsTemplate" class="btn btn-warning">Guardar como Plantilla</button>
            <button type="button" id="clearAll" class="btn btn-danger">Limpiar</button>
        </div>

        <!-- Selector de Plantilla -->
        <div class="template-selector">
            <label for="template_select">Usar Plantilla:</label>
            <select id="template_select">
                <option value="">-- Seleccionar plantilla --</option>
                <?php
                $templates = $pdo->query("SELECT id, name FROM templates ORDER BY name")->fetchAll();
                foreach ($templates as $template): ?>
                    <option value="<?= $template['id'] ?>" <?= isset($_GET['template']) && $_GET['template'] == $template['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($template['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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

        <!-- Línea 2: Dos columnas -->
        <div class="main-content">
            <!-- Columna 1: Formulario -->
            <div class="left-column">
                <form id="tariffForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="csv_data" name="csv_data">
                    
                    <div class="form-section">
                        <h3>Información de la Tarifa</h3>
                        <div class="form-group">
                            <label for="tariff_name">Nombre de la Tarifa:</label>
                            <input type="text" id="tariff_name" name="tariff_name" required value="<?= htmlspecialchars($_POST['tariff_name'] ?? $templateData['title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Descripción:</label>
                            <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? $templateData['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Datos de la Empresa (Encabezado de Formulario y del PDF)</h3>
                        <div class="form-group">
                            <label for="company_name">Nombre de la Empresa:</label>
                            <input type="text" id="company_name" name="company_name" required value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_nif">NIF/CIF:</label>
                                <input type="text" id="company_nif" name="company_nif" required value="<?= htmlspecialchars($_POST['company_nif'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="logo_url">URL del Logo:</label>
                                <input type="url" id="logo_url" name="logo_url" value="<?= htmlspecialchars($_POST['logo_url'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="company_address">Dirección (Calle, Número - CP, Localidad, (Provincia)):</label>
                            <textarea id="company_address" name="company_address" required><?= htmlspecialchars($_POST['company_address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="company_contact">Contacto (Teléfono - Email - Web):</label>
                            <input type="text" id="company_contact" name="company_contact" required value="<?= htmlspecialchars($_POST['company_contact'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Configuración del PDF</h3>
                        <div class="form-group">
                            <label for="template">Plantilla PDF:</label>
                            <input type="text" id="template" name="template" value="<?= htmlspecialchars($_POST['template'] ?? '41200-00001') ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="primary_color">Color Primario:</label>
                                <div class="color-picker">
                                    <div class="color-preview" id="primaryColorPreview" style="background: <?= $_POST['primary_color'] ?? '#e8951c' ?>"></div>
                                    <input type="color" id="primary_color" name="primary_color" value="<?= $_POST['primary_color'] ?? '#e8951c' ?>" style="display: none;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="secondary_color">Color Secundario:</label>
                                <div class="color-picker">
                                    <div class="color-preview" id="secondaryColorPreview" style="background: <?= $_POST['secondary_color'] ?? '#109c61' ?>"></div>
                                    <input type="color" id="secondary_color" name="secondary_color" value="<?= $_POST['secondary_color'] ?? '#109c61' ?>" style="display: none;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Textos Legales del PDF</h3>
                        <div class="form-group">
                            <label for="summary_note">Nota del Resumen (Aceptación y Métodos de Pago):</label>
                            <textarea id="summary_note" name="summary_note"><?= htmlspecialchars($_POST['summary_note'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="conditions_note">Condiciones del Presupuesto (Cláusulas, garantías, incluido o no, etc):</label>
                            <textarea id="conditions_note" name="conditions_note"><?= htmlspecialchars($_POST['conditions_note'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Condiciones Legales del Formulario</h3>
                        <div class="form-group">
                            <label for="legal_note">Información legal del Formulario:</label>
                            <textarea id="legal_note" name="legal_note"><?= htmlspecialchars($_POST['legal_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Columna 2: Selector de archivo CSV -->
            <div class="right-column">
                <div id="csvUploadSection">
                    <h3>Selector de Archivo CSV</h3>
                    <div class="upload-area">
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display: none;">
                        <p class="upload-text">Arrastra aquí o selecciona tu archivo CSV</p>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('csv_file').click()">Importar CSV</button>
                        
                        <div class="format-info">
                            <h4>Formato requerido:</h4>
                            <div class="csv-example">
"Nivel","ID","Nombre","Descripción","Ud","%IVA","PVP"
"Capítulo",1,"Nombre del Capítulo 1",,,,
"Subcapítulo","1.1","Nombre del Subcapítulo 1.1",,,,
"Apartado","1.1.1","Nombre del Apartado 1.1.1",,,,
"Partida","1.1.1.1","Nombre del Partida 1.1.1.1","Descripción de la Partida 1.1.1.1","Unidad","5,00","125,00"
"Capítulo",2,"Nombre del Capítulo 2",,,,
"Subcapítulo","2.1","Nombre del Subcapítulo 2.1",,,,
"Partida","2.1.1","Nombre del Partida 2.1.1","Descripción de la Partida 2.1.1","hora","10,00","20,00"
"Capítulo",3,"Nombre del Capítulo 3",,,,
"Partida","3.1","Nombre del Partida 3.1","Descripción de la Partida 3.1","m","21,00","5,00"
                            </div>
                            <button type="button" id="downloadTemplate" class="btn btn-secondary">Descargar Plantilla</button>
                        </div>
                    </div>
                </div>

                <div id="tariffSection" style="display: none;">
                    <h3>Tarifa</h3>
                    <p class="tariff-status">Tarifa Actual</p>
                    <div class="tariff-actions">
                        <button type="button" id="exportCsv" class="btn btn-secondary">Exportar</button>
                        <button type="button" id="showJson" class="btn btn-info">JSON</button>
                        <button type="button" id="deleteTariff" class="btn btn-danger">Borrar</button>
                    </div>
                    <div id="hierarchyOutput" class="hierarchy-container"></div>
                </div>
            </div>
            <!-- Aplicar valores de plantilla a todos los campos -->
            <?php if ($templateData): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        loadTemplateData(<?= json_encode($templateData) ?>);
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Guardar como Plantilla -->
    <div id="saveTemplateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Guardar como Plantilla</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="saveTemplateForm">
                    <div class="form-group">
                        <label for="template_name">Nombre de la Plantilla:</label>
                        <input type="text" id="template_name" name="template_name" required>
                    </div>
                    <div class="form-group">
                        <label for="template_description">Descripción:</label>
                        <textarea id="template_description" name="template_description"></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeSaveTemplateModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/new-tariff-handler.js"></script>
</body>
</html>