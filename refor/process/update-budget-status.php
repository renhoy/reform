<?php
// {"_META_file_path_": "refor/process/update-budget-status.php"}
// Actualiza el estado de un presupuesto

require_once '../includes/config.php';
require_once '../includes/budgets-helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$budgetId = $input['budget_id'] ?? null;
$newStatus = $input['status'] ?? null;

if (!$budgetId || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $result = updateBudgetStatus($budgetId, $newStatus);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
} catch (Exception $e) {
    error_log("Error updating budget status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}