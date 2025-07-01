<?php
// {"_META_file_path_": "refor/tariff-form.php"}
// Formulario unificado para crear/editar tarifas

require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';
require_once 'includes/csv-processor.php';

requireAuth();

// Determinar si es edición o creación
$isEdit = isset($_GET['id']);
$tariff_id = $isEdit ? $_GET['id'] : null;
$pageTitle = $isEdit ? 'Editar Tarifa' : 'Nueva Tarifa';

// Cargar datos existentes o por defecto
if ($isEdit) {
    $tariff = getTariffById($tariff_id);
    if (!$tariff) {
        redirect('tariffs', ['error' => 'Tarifa no encontrada']);
    }
} else {
    $tariff = getDefaultTariffData();
}

$errors = [];
$success = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'tariff_name' => trim($_POST['tariff_name'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? ''),
            'company_nif' => trim($_POST['company_nif'] ?? ''),
            'company_address' => trim($_POST['company_address'] ?? ''),
            'company_contact' => trim($_POST['company_contact'] ?? ''),
            'logo_url' => trim($_POST['logo_url'] ?? ''),
            'template' => trim($_POST['template'] ?? '41200-00001'),
            'primary_color' => $_POST['primary_color'] ?? '#e8951c',
            'secondary_color' => $_POST['secondary_color'] ?? '#109c61',
            'summary_note' => trim($_POST['summary_note'] ?? ''),
            'conditions_note' => trim($_POST['conditions_note'] ?? ''),
            'legal_note' => trim($_POST['legal_note'] ?? ''),
            'csv_data' => $_POST['csv_data'] ?? ''
        ];
        
        // Validaciones básicas
        if (empty($data['tariff_name'])) $errors[] = 'El nombre de la tarifa es obligatorio';
        if (empty($data['company_name'])) $errors[] = 'El nombre de la empresa es obligatorio';
        
        // Procesar archivo CSV si se subió
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $csvContent = file_get_contents($_FILES['csv_file']['tmp_name']);
            $jsonData = processCSVToJSON($csvContent);
            
            if ($jsonData === false) {
                $errors[] = 'Error al procesar el archivo CSV';
            } else {
                $data['csv_data'] = json_encode($jsonData);
                // Guardar archivo CSV
                $filePath = uploadCSVFile($_FILES['csv_file']);
            }
        }
        
        if (empty($errors)) {
            $result_id = saveTariff($data, $tariff_id);
            $success = true;
            
            if (!$isEdit) {
                redirect('tariffs', ['success' => 'Tarifa creada correctamente']);
            }
        }
        
    } catch (Exception $e) {
        $errors[] = 'Error al guardar: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/forms.css') ?>">
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
        <!-- Línea 1: Botones de acción -->
        <div class="action-bar">
            <a href="tariffs.php" class="btn btn-secondary">Tarifas</a>
            <button type="button" id="clearAll" class="btn btn-danger">Limpiar</button>
            <button type="submit" form="tariffForm" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?> Tarifa</button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Tarifa <?= $isEdit ? 'actualizada' : 'creada' ?> correctamente
            </div>
        <?php endif; ?>

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
                    <input type="hidden" id="csv_data" name="csv_data" value="<?= htmlspecialchars($tariff['json_tariff_data'] ?? '') ?>">
                    
                    <?php include 'templates/tariff-form-fields.php'; ?>
                </form>
            </div>

            <!-- Columna 2: Selector de archivo CSV -->
            <div class="right-column">
                <div id="csvUploadSection" <?= !empty($tariff['json_tariff_data']) ? 'style="display: none;"' : '' ?>>
                    <h3>Selector de Archivo CSV</h3>
                    <div class="upload-area">
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display: none;">
                        <p class="upload-text">Arrastra aquí o selecciona tu archivo CSV</p>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('csv_file').click()">Importar CSV</button>
                        
                        <div class="format-info">
                            <h4>Formato requerido:</h4>
                            <div class="csv-example">
                                <?= htmlspecialchars(generateCSVTemplate()) ?>
                            </div>
                            <button type="button" id="downloadTemplate" class="btn btn-secondary">Descargar Plantilla</button>
                        </div>
                    </div>
                </div>

                <div id="tariffSection" <?= empty($tariff['json_tariff_data']) ? 'style="display: none;"' : '' ?>>
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
        </div>
    </div>

    <script src="<?= asset('js/tariff-form.js') ?>"></script>
</body>
</html>