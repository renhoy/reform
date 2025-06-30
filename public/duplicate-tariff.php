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
        // Crear nueva tarifa
        $new_name = $original_tariff['name'] . ' (Copia)';
        $stmt = $pdo->prepare("INSERT INTO tariffs (name, file_path, json_data) VALUES (?, ?, ?)");
        $stmt->execute([$new_name, $original_tariff['file_path'], $original_tariff['json_data']]);
        $new_tariff_id = $pdo->lastInsertId();
        
        // Duplicar configuración de empresa
        $stmt = $pdo->prepare("SELECT * FROM company_config WHERE tariff_id = ?");
        $stmt->execute([$id]);
        $original_config = $stmt->fetch();
        
        if ($original_config) {
            $stmt = $pdo->prepare("
                INSERT INTO company_config 
                (tariff_id, name, nif, address, contact, logo_url, template, primary_color, secondary_color, summary_note, conditions_note, legal_note) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $new_tariff_id,
                $original_config['name'],
                $original_config['nif'],
                $original_config['address'],
                $original_config['contact'],
                $original_config['logo_url'],
                $original_config['template'],
                $original_config['primary_color'],
                $original_config['secondary_color'],
                $original_config['summary_note'],
                $original_config['conditions_note'],
                $original_config['legal_note']
            ]);
        }
    }
    
    $pdo->commit();
    header('Location: tariffs.php?duplicated=1');
    exit;
} catch (Exception $e) {
    $pdo->rollback();
    header('Location: tariffs.php?error=' . urlencode($e->getMessage()));
    exit;
}