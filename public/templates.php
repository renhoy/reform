<?php
// {"_META_file_path_": "public/templates.php"}
// Gestión de plantillas

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();

// Obtener todas las plantillas
$templates = $pdo->query("
    SELECT t.*, u.name as creator_name 
    FROM templates t 
    LEFT JOIN users u ON t.created_by = u.id 
    ORDER BY t.created_at DESC
")->fetchAll();

// Procesar eliminación
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ? AND created_by = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header('Location: templates.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantillas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="assets/css/header-styles.css">
    <link rel="stylesheet" href="assets/css/tariffs-styles.css">
    <link rel="stylesheet" href="assets/css/templates-styles.css">
</head>
<body>
    <?php include SRC_PATH . '/views/templates/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Plantillas de Tarifas</h1>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Plantilla guardada correctamente</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Plantilla eliminada correctamente</div>
        <?php endif; ?>

        <div class="actions-bar">
            <a href="upload-tariff.php" class="btn btn-primary">Crear Tarifa</a>
            <button class="btn btn-info" onclick="openTemplateModal()">Crear Plantilla</button>
            <a href="tariffs.php" class="btn btn-secondary">Volver a Tarifas</a>
        </div>

        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <h2>No hay plantillas disponibles</h2>
                <p>Crea tu primera plantilla para reutilizar configuraciones</p>
                <button class="btn btn-primary" onclick="openTemplateModal()">Crear Primera Plantilla</button>
            </div>
        <?php else: ?>
            <div class="tariffs-table">
                <div class="table-header">
                    <div>Nombre</div>
                    <div>Descripción</div>
                    <div>Creador</div>
                    <div>Fecha</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($templates as $template): ?>
                    <div class="table-row">
                        <div class="template-name"><?= htmlspecialchars($template['name']) ?></div>
                        <div class="template-description"><?= htmlspecialchars($template['description']) ?></div>
                        <div class="creator-name"><?= htmlspecialchars($template['creator_name']) ?></div>
                        <div class="template-date"><?= date('d/m/Y', strtotime($template['created_at'])) ?></div>
                        <div class="template-actions">
                            <button class="btn btn-primary btn-small" onclick="useTemplate(<?= $template['id'] ?>)">Usar</button>
                            <?php if ($template['created_by'] == $_SESSION['user_id']): ?>
                                <button class="btn btn-secondary btn-small" onclick="editTemplate(<?= $template['id'] ?>)">Editar</button>
                                <button class="btn btn-info btn-small" onclick="duplicateTemplate(<?= $template['id'] ?>)">Duplicar</button>
                                <button class="btn btn-danger btn-small" onclick="deleteTemplate(<?= $template['id'] ?>)">Borrar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Plantilla -->
    <div id="templateModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3 id="modalTitle">Crear Plantilla</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" id="template_id" name="template_id">
                    
                    <div class="form-section">
                        <h4>Información de la Plantilla</h4>
                        <div class="form-group">
                            <label for="template_name">Nombre:</label>
                            <input type="text" id="template_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="template_description">Descripción:</label>
                            <textarea id="template_description" name="description"></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Configuración de la Tarifa</h4>
                        <div class="form-group">
                            <label for="template_title">Título Tarifa:</label>
                            <input type="text" id="template_title" name="title">
                        </div>
                        <div class="form-group">
                            <label for="template_company_name">Nombre Empresa:</label>
                            <input type="text" id="template_company_name" name="company_name">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="template_nif">NIF/CIF:</label>
                                <input type="text" id="template_nif" name="nif">
                            </div>
                            <div class="form-group">
                                <label for="template_logo_url">URL Logo:</label>
                                <input type="url" id="template_logo_url" name="logo_url">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="template_address">Dirección:</label>
                            <textarea id="template_address" name="address"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="template_contact">Contacto:</label>
                            <input type="text" id="template_contact" name="contact">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="template_primary_color">Color Primario:</label>
                                <input type="color" id="template_primary_color" name="primary_color" value="#e8951c">
                            </div>
                            <div class="form-group">
                                <label for="template_secondary_color">Color Secundario:</label>
                                <input type="color" id="template_secondary_color" name="secondary_color" value="#109c61">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Notas Legales</h4>
                        <div class="form-group">
                            <label for="template_summary_note">Nota Resumen:</label>
                            <textarea id="template_summary_note" name="summary_note"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="template_conditions_note">Condiciones:</label>
                            <textarea id="template_conditions_note" name="conditions_note"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="template_legal_note">Nota Legal:</label>
                            <textarea id="template_legal_note" name="legal_note"></textarea>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/templates-handler.js"></script>
</body>
</html>