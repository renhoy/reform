<?php
// {"_META_file_path_": "public/duplicate-budget.php"}
// Duplicar presupuesto

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$uuid = $_GET['uuid'] ?? null;
if (!$uuid) {
    header('Location: budgets.php');
    exit;
}

$pdo = getConnection();

try {
    $pdo->beginTransaction();
    
    // Obtener presupuesto original
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE uuid = ? AND user_id = ?");
    $stmt->execute([$uuid, $_SESSION['user_id']]);
    $original = $stmt->fetch();
    
    if ($original) {
        // Generar nuevo UUID
        $new_uuid = generateUUID();
        
        // Crear copia (sin PDF)
        $stmt = $pdo->prepare("
            INSERT INTO budgets 
            (uuid, json_observations, json_tariff_data, client_type, client_name, 
             client_nif_nie, client_phone, client_email, client_web, client_address, 
             client_postal_code, client_locality, client_province, client_acceptance,
             json_budget_data, status, total, iva, base, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $new_uuid,
            $original['json_observations'],
            $original['json_tariff_data'],
            $original['client_type'],
            $original['client_name'] . ' (Copia)',
            $original['client_nif_nie'],
            $original['client_phone'],
            $original['client_email'],
            $original['client_web'],
            $original['client_address'],
            $original['client_postal_code'],
            $original['client_locality'],
            $original['client_province'],
            $original['client_acceptance'],
            $original['json_budget_data'],
            $original['total'],
            $original['iva'],
            $original['base'],
            $_SESSION['user_id']
        ]);
    }
    
    $pdo->commit();
    header('Location: budgets.php?duplicated=1');
    exit;
    
} catch (Exception $e) {
    $pdo->rollback();
    header('Location: budgets.php?error=' . urlencode($e->getMessage()));
    exit;
}