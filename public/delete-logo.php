<?php
// {"_META_file_path_": "public/delete-logo.php"}
// Eliminar archivo de logo

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'URL no proporcionada']);
    exit;
}

// Validar que la URL es del directorio de logos
if (strpos($url, '/assets/uploads/logos/') !== 0) {
    echo json_encode(['success' => false, 'error' => 'URL inválida']);
    exit;
}

$filePath = __DIR__ . $url;

if (file_exists($filePath)) {
    if (unlink($filePath)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el archivo']);
    }
} else {
    echo json_encode(['success' => true]); // Ya no existe
}