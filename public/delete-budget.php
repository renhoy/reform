<?php
// {"_META_file_path_": "public/delete-budget.php"}
// Eliminar presupuesto

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

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("DELETE FROM budgets WHERE uuid = ? AND user_id = ?");
    $stmt->execute([$uuid, $_SESSION['user_id']]);
    
    header('Location: budgets.php?deleted=1');
    exit;
} catch (Exception $e) {
    header('Location: budgets.php?error=' . urlencode($e->getMessage()));
    exit;
}