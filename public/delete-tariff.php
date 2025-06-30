<?php
// {"_META_file_path_": "public/delete-tariff.php"}
// Eliminar tarifa

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: tariffs.php');
    exit;
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: tariffs.php?deleted=1');
    exit;
} catch (Exception $e) {
    header('Location: tariffs.php?error=' . urlencode($e->getMessage()));
    exit;
}