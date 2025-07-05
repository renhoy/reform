<?php
require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';
require_once 'includes/csv-processor.php';

requireAuth();

// Determinar si es edición o creación
$isEdit = isset($_GET['id']);
$tariff_id = $isEdit ? $_GET['id'] : null;
$pageTitle = $isEdit ? 'Editar Tarifa' : 'Crear Tarifa';

// Verificar si se ha proporcionado un ID de plantilla
$template_id = isset($_GET['template']) ? intval($_GET['template']) : 1;

// Cargar datos existentes o por defecto
if ($isEdit) {
    $tariff = getTariffById($tariff_id);
    if (!$tariff) {
        redirect('tariffs', ['error' => 'Tarifa no encontrada']);
    }
} else {
    $tariff = getDefaultTariffData($template_id);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Código para procesar el formulario
    // ...
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Reform</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/forms.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/nueva-tarifa-layout.css') ?>">
    <link href="https://unpkg.com/lucide@latest/dist/umd/lucide.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header igual que en otras páginas -->
        <div class="header">
            <div class="header-content">
                <div class="logo">Generador de Presupuestos</div>
                <nav class="main-nav">
                    <a href="dashboard.php" class="nav-item">Dashboard</a>
                    <a href="tariffs.php" class="nav-item active">Tarifas</a>
                    <a href="budgets.php" class="nav-item">Presupuestos</a>
                </nav>
                <div class="user-menu">
                    <a href="logout.php" class="btn-icon btn-icon--black" title="Cerrar Sesión">
                        <i data-lucide="log-out"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Título de página con botones a la derecha -->
        <div class="spacing">
            <div class="page-header">
                <h1><?= $pageTitle ?></h1>
                <div class="header-title__buttons">
                    <a href="tariffs.php" class="btn btn--tariffs">Tarifas</a>
                    <button type="submit" form="tariffForm" class="btn btn--tariffs">Guardar Tarifa</button>
                    <button type="button" class="btn btn--templates">Plantillas</button>
                    <button type="button" class="btn btn--templates">Guardar como Plantilla</button>
                </div>
            </div>
        </div>

        <!-- Contenido principal en dos columnas -->
        <div class="spacing">
            <div class="two-column-layout">
                <!-- Columna izquierda: Formulario -->
                <div class="form-column">
                    <form id="tariffForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="csv_data" name="csv_data" value="<?= htmlspecialchars($tariff['json_tariff_data'] ?? '') ?>">
                        
                        <!-- Card 1: Información de la Tarifa -->
                        <div class="form-section">
                            <h2>Información de la Tarifa</h2>
                            
                            <div class="form-group">
                                <label class="form-label">Nombre de la Tarifa</label>
                                <input type="text" class="form-input" name="tariff_name" placeholder="Introduce el nombre" value="<?= htmlspecialchars($tariff['title'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Descripción de la Tarifa</label>
                                <textarea class="form-textarea" name="description" rows="3" placeholder="Descripción de la tarifa"><?= htmlspecialchars($tariff['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Card 2: Datos de la Empresa -->
                        <div class="form-section">
                            <h2>Datos de la Empresa</h2>
                            
                            <div class="form-group">
                                <label class="form-label">Logo</label>
                                <div class="file-upload-area">
                                    <div class="file-upload-text">Arrastra aquí o selecciona tu imagen</div>
                                    <button type="button" class="btn btn--primary">Seleccionar</button>
                                    <input type="hidden" name="logo_url" value="<?= htmlspecialchars($tariff['logo_url'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nombre de la Empresa</label>
                                <input type="text" class="form-input" name="company_name" placeholder="Nombre de la empresa" value="<?= htmlspecialchars($tariff['name'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">NIF/CIF</label>
                                <input type="text" class="form-input" name="company_nif" placeholder="NIF/CIF" value="<?= htmlspecialchars($tariff['nif'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-input" name="company_address" placeholder="Dirección" value="<?= htmlspecialchars($tariff['address'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contacto</label>
                                <input type="text" class="form-input" name="company_contact" placeholder="Contacto" value="<?= htmlspecialchars($tariff['contact'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Card 3: Configuración del PDF -->
                        <div class="form-section">
                            <h2>Configuración del PDF</h2>
                            
                            <div class="form-group">
                                <label class="form-label">Días de validez</label>
                                <input type="number" class="form-input" name="validity" placeholder="30" value="<?= htmlspecialchars($tariff['validity'] ?? '30') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Plantilla PDF</label>
                                <select class="form-select" name="template">
                                    <option value="41200-00001" <?= ($tariff['template'] ?? '') === '41200-00001' ? 'selected' : '' ?>>Estándar</option>
                                    <option value="41200-00002" <?= ($tariff['template'] ?? '') === '41200-00002' ? 'selected' : '' ?>>Moderna</option>
                                    <option value="41200-00003" <?= ($tariff['template'] ?? '') === '41200-00003' ? 'selected' : '' ?>>Minimalista</option>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Color Primario</label>
                                    <input type="color" class="form-input color-input" name="primary_color" value="<?= htmlspecialchars($tariff['primary_color'] ?? '#e8951c') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Color Secundario</label>
                                    <input type="color" class="form-input color-input" name="secondary_color" value="<?= htmlspecialchars($tariff['secondary_color'] ?? '#109c61') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Textos Legales del PDF -->
                        <div class="form-section">
                            <h2>Textos Legales del PDF</h2>
                            
                            <div class="form-group">
                                <label class="form-label">Nota del Resumen</label>
                                <textarea class="form-textarea" name="summary_note" rows="3" placeholder="Nota para el resumen del presupuesto"><?= htmlspecialchars($tariff['summary_note'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nota de Condiciones</label>
                                <textarea class="form-textarea" name="conditions_note" rows="3" placeholder="Nota para las condiciones del presupuesto"><?= htmlspecialchars($tariff['conditions_note'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Card 5: Condiciones Legales del Formulario -->
                        <div class="form-section">
                            <h2>Condiciones Legales del Formulario</h2>
                            
                            <div class="form-group">
                                <label class="form-label">Nota Legal del Formulario</label>
                                <textarea class="form-textarea" name="legal_note" rows="4" placeholder="Nota legal del formulario"><?= htmlspecialchars($tariff['legal_note'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Columna derecha: Carga de datos -->
                <div class="preview-column">
                    <!-- Card 1: Selector de Tarifa -->
                    <div class="preview-section">
                        <h2>Selección de Tarifa</h2>
                        
                        <div class="form-group">
                            <div class="file-upload-area">
                                <div class="file-upload-text">Arrastra aquí o selecciona tu archivo CSV</div>
                                <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display: none;">
                                <button type="button" class="btn btn--primary" onclick="document.getElementById('csv_file').click()">Seleccionar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Formato requerido -->
                    <div class="preview-section">
                        <h2>Formato requerido</h2>
                        
                        <div class="csv-format">
                            <pre class="csv-example">"Nivel","ID","Nombre","Descripción","Ud","%IVA","PVP"
"Capítulo",1,"Nombre del Capítulo 1",,,,
"Subcapítulo","1.1","Nombre del Subcapítulo 1.1",,,,
"Apartado","1.1.1","Nombre del Apartado 1.1.1",,,,
"Partida","1.1.1.1","Nombre del Partida 1.1.1.1","Descripción de la Partida 1.1.1.1","Unidad","5,00","125,00"
"Capítulo",2,"Nombre del Capítulo 2",,,,
"Subcapítulo","2.1","Nombre del Subcapítulo 2.1",,,,
"Partida","2.1.1","Nombre del Partida 2.1.1","Descripción de la Partida 2.1.1","hora","10,00","20,00"
"Capítulo",3,"Nombre del Capítulo 3",,,,
"Partida","3.1","Nombre del Partida 3.1","Descripción de la Partida 3.1","m","21,00","5,00"</pre>
                        </div>
                    </div>
                    
                    <div id="tariffSection" <?= empty($tariff['json_tariff_data']) ? 'style="display: none;"' : '' ?>>
                        <div class="preview-section">
                            <h2>Tarifa Actual</h2>
                            <div class="tariff-actions">
                                <button type="button" id="exportCsv" class="btn btn--secondary">Exportar</button>
                                <button type="button" id="showJson" class="btn btn--info">JSON</button>
                                <button type="button" id="deleteTariff" class="btn btn--danger">Borrar</button>
                            </div>
                            <div id="hierarchyOutput" class="hierarchy-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="<?= asset('js/tariff-form.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar los iconos de Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Manejar la carga de archivos CSV
            const csvFileInput = document.getElementById('csv_file');
            if (csvFileInput) {
                csvFileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const csvContent = e.target.result;
                            document.getElementById('csv_data').value = csvContent;
                            
                            // Mostrar la sección de tarifa actual
                            const tariffSection = document.getElementById('tariffSection');
                            if (tariffSection) {
                                tariffSection.style.display = 'block';
                            }
                        };
                        reader.readAsText(file);
                    }
                });
            }
        });
    </script>
</body>
</html>
