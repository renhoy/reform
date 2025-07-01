<?php
// {"_META_file_path_": "public/upload-logo.php"}
// Subida de archivos de logo

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No se recibió archivo válido']);
    exit;
}

$file = $_FILES['logo'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validar tipo
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
    exit;
}

// Validar tamaño
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Archivo demasiado grande']);
    exit;
}

// Crear directorio si no existe
$uploadDir = __DIR__ . '/assets/uploads/logos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = time() . '_' . uniqid() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// Mover archivo
if (move_uploaded_file($file['tmp_name'], $filePath)) {
    $url = '/assets/uploads/logos/' . $fileName;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar archivo']);
}