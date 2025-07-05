<?php
require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';

// Determinar si es edición o creación
$isEdit = isset($_GET['id']);
$tariff_id = $isEdit ? $_GET['id'] : null;
$pageTitle = $isEdit ? 'Editar Tarifa' : 'Nueva Tarifa';

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
    <link href="https://unpkg.com/lucide@latest/dist/umd/lucide.css" rel="stylesheet">
    <style>
        /* Estilos básicos para el layout de dos columnas */
        .two-column-layout {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-column {
            flex: 1;
        }
        
        .preview-column {
            flex: 1;
        }
        
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
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

        <!-- Título de página con botones -->
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
                        <!-- Card 1: Información básica -->
                        <div class="card">
                            <h2>Información Básica</h2>
                            <p>Este es un card de ejemplo en la columna izquierda.</p>
                        </div>
                        
                        <!-- Card 2: Otro ejemplo -->
                        <div class="card">
                            <h2>Otro Card</h2>
                            <p>Este es otro card de ejemplo en la columna izquierda.</p>
                        </div>
                    </form>
                </div>

                <!-- Columna derecha: Carga de datos -->
                <div class="preview-column">
                    <!-- Card 1: Ejemplo derecha -->
                    <div class="card">
                        <h2>Card Derecha</h2>
                        <p>Este es un card de ejemplo en la columna derecha.</p>
                    </div>
                    
                    <!-- Card 2: Otro ejemplo derecha -->
                    <div class="card">
                        <h2>Otro Card Derecha</h2>
                        <p>Este es otro card de ejemplo en la columna derecha.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        // Inicializar los iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
