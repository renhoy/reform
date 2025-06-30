<?php
// {"_META_file_path_": "public/get-template.php"}
// Obtener datos de plantilla

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID requerido']);
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
$stmt->execute([$id]);
$template = $stmt->fetch();

if ($template) {
    echo json_encode(['success' => true, 'template' => $template]);
} else {
    echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
}