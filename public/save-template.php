<?php
// {"_META_file_path_": "public/save-template.php"}
// Guardar plantilla

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$pdo = getConnection();

try {
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $templateData = $input['template_data'] ?? [];
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    
    if ($input['id']) {
        // Actualizar
        $stmt = $pdo->prepare("UPDATE templates SET name = ?, description = ?, template_data = ? WHERE id = ? AND created_by = ?");
        $stmt->execute([$name, $description, json_encode($templateData), $input['id'], $_SESSION['user_id']]);
    } else {
        // Crear
        $stmt = $pdo->prepare("INSERT INTO templates (name, description, template_data, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, json_encode($templateData), $_SESSION['user_id']]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}