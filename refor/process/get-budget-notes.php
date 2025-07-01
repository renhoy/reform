<?php
// {"_META_file_path_": "refor/process/get-budget-notes.php"}
// Obtiene apuntes de un presupuesto

require_once '../includes/config.php';
require_once '../includes/budgets-helpers.php';

header('Content-Type: application/json');
requireAuth();

$budgetId = $_GET['budget_id'] ?? null;

if (!$budgetId) {
    echo json_encode([]);
    exit;
}

try {
    $notes = getBudgetNotes($budgetId);
    echo json_encode($notes);
} catch (Exception $e) {
    error_log("Error getting budget notes: " . $e->getMessage());
    echo json_encode([]);
}