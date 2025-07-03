<?php
// {"_META_file_path_": "refor/process/get-budget-stats.php"}
// Obtiene estadísticas actualizadas de presupuestos

require_once '../includes/config.php';
require_once '../includes/budgets-helpers.php';

header('Content-Type: application/json');
requireAuth();

try {
    $stats = getBudgetStats();
    echo json_encode($stats);
} catch (Exception $e) {
    error_log("Error getting budget stats: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}