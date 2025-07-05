<?php
require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Tarifa - Versión Simple</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header.css') ?>">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .two-column-layout {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-column {
            flex: 1;
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        
        .preview-column {
            flex: 1;
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .header-title__buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn--tariffs {
            background-color: #e8951c;
            color: white;
        }
        
        .btn--templates {
            background-color: #17a2b8;
            color: white;
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
        <div class="page-header">
            <h1>Nueva Tarifa</h1>
            <div class="header-title__buttons">
                <a href="tariffs.php" class="btn btn--tariffs">Tarifas</a>
                <button type="submit" form="tariffForm" class="btn btn--tariffs">Guardar Tarifa</button>
                <button type="button" class="btn btn--templates">Plantillas</button>
                <button type="button" class="btn btn--templates">Guardar como Plantilla</button>
            </div>
        </div>

        <!-- Contenido en dos columnas -->
        <div class="two-column-layout">
            <!-- Columna izquierda -->
            <div class="form-column">
                <h2>Formulario de Tarifa</h2>
                <p>Esta es la columna izquierda con el formulario.</p>
                <form id="tariffForm" method="POST">
                    <div>
                        <label>Nombre de la Tarifa</label>
                        <input type="text" name="tariff_name" placeholder="Nombre de la tarifa">
                    </div>
                    <div style="margin-top: 10px;">
                        <label>Descripción</label>
                        <textarea name="description" rows="3" placeholder="Descripción de la tarifa"></textarea>
                    </div>
                </form>
            </div>
            
            <!-- Columna derecha -->
            <div class="preview-column">
                <h2>Carga de Datos</h2>
                <p>Esta es la columna derecha para cargar datos CSV.</p>
                <div style="margin-top: 20px; padding: 15px; background: #e0e0e0; border-radius: 4px;">
                    <p>Área para cargar archivos CSV</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
