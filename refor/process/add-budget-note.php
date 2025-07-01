<?php
// {"_META_file_path_": "refor/process/add-budget-note.php"}
// Añade un apunte a un presupuesto

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
$category = $input['category'] ?? null;
$note = $input['note'] ?? null;

if (!$budgetId || !$category || !$note) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $result = addBudgetNote($budgetId, $category, trim($note));
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Apunte añadido correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al añadir apunte']);
    }
} catch (Exception $e) {
    error_log("Error adding budget note: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}