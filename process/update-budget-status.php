<?php
// {"_META_file_path_": "refor/process/update-budget-status.php"}
// Actualizar estado de presupuesto

require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['budget_id']) || !is_numeric($input['budget_id'])) {
        throw new Exception('ID de presupuesto inválido');
    }
    
    if (!isset($input['status']) || !in_array($input['status'], ['draft', 'pending', 'sent', 'approved', 'rejected', 'expired'])) {
        throw new Exception('Estado inválido');
    }
    
    $budgetId = (int)$input['budget_id'];
    $status = $input['status'];
    
    $pdo = getConnection();
    
    // Verificar que el presupuesto existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$budgetId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Presupuesto no encontrado');
    }
    
    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE budgets SET status = ? WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$status, $budgetId, $_SESSION['user_id']]);
    
    if (!$result) {
        throw new Exception('Error al actualizar el estado');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}