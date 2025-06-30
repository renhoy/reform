<?php
// {"_META_file_path_": "public/duplicate-tariff.php"}
// Duplicar tarifa

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

$pdo = getConnection();

try {
    $pdo->beginTransaction();
    
    // Obtener tarifa original
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
    $stmt->execute([$id]);
    $original_tariff = $stmt->fetch();
    
    if ($original_tariff) {
        $new_name = $original_tariff['title'] . ' (Copia)';
        $new_uuid = generateUUID();
        
        $stmt = $pdo->prepare("
            INSERT INTO tariffs 
            (uuid, title, description, name, nif, address, contact, logo_url, 
             template, primary_color, secondary_color, summary_note, conditions_note, 
             access, legal_note, json_tariff_data, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $new_uuid,
            $new_name,
            $original_tariff['description'],
            $original_tariff['name'],
            $original_tariff['nif'],
            $original_tariff['address'],
            $original_tariff['contact'],
            $original_tariff['logo_url'],
            $original_tariff['template'],
            $original_tariff['primary_color'],
            $original_tariff['secondary_color'],
            $original_tariff['summary_note'],
            $original_tariff['conditions_note'],
            $original_tariff['access'],
            $original_tariff['legal_note'],
            $original_tariff['json_tariff_data'],
            $_SESSION['user_id']
        ]);
    }
    
    $pdo->commit();
    header('Location: tariffs.php?duplicated=1');
    exit;
} catch (Exception $e) {
    $pdo->rollback();
    header('Location: tariffs.php?error=' . urlencode($e->getMessage()));
    exit;
}