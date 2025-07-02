<?php
// {"_META_file_path_": "refor/process/duplicate-tariff.php"}
// Procesar duplicación de tarifa

require_once '../includes/config.php';
require_once '../includes/tariffs-helpers.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['tariff_id']) || !is_numeric($input['tariff_id'])) {
        throw new Exception('ID de tarifa inválido');
    }
    
    $tariffId = (int)$input['tariff_id'];
    
    // Verificar que la tarifa existe y pertenece al usuario
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
    $stmt->execute([$tariffId, $_SESSION['user_id']]);
    $originalTariff = $stmt->fetch();
    
    if (!$originalTariff) {
        throw new Exception('Tarifa no encontrada');
    }
    
    // Duplicar tarifa usando la función helper
    $newTariffId = duplicateTariff($tariffId);
    
    if (!$newTariffId) {
        throw new Exception('Error al duplicar la tarifa');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tarifa duplicada correctamente',
        'new_tariff_id' => $newTariffId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}