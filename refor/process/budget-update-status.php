<?php
// {"_META_file_path_": "refor/process/budget-update-status.php"}
// Actualización de estado de presupuestos vía AJAX

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/budget-helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos no válidos');
    }
    
    $budget_id = $input['budget_id'] ?? null;
    $status = $input['status'] ?? null;
    
    if (!$budget_id || !$status) {
        throw new Exception('Faltan parámetros requeridos');
    }
    
    $result = updateBudgetStatus($budget_id, $status);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo actualizar el estado');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}