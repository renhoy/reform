<?php
// {"_META_file_path_": "public/duplicate-tariff.php"}
// Duplicar tarifa vía API

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$tariffId = $input['tariff_id'] ?? null;

if (!$tariffId) {
    echo json_encode(['success' => false, 'message' => 'ID requerido']);
    exit;
}

$pdo = getConnection();

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
    $stmt->execute([$tariffId, $_SESSION['user_id']]);
    $original = $stmt->fetch();
    
    if ($original) {
        $newTitle = $original['title'] . ' (Copia)';
        $newUuid = generateUUID();
        
        $stmt = $pdo->prepare("
            INSERT INTO tariffs 
            (uuid, title, description, name, nif, address, contact, logo_url, 
             template, primary_color, secondary_color, summary_note, conditions_note, 
             legal_note, json_tariff_data, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $newUuid, $newTitle, $original['description'], $original['name'],
            $original['nif'], $original['address'], $original['contact'],
            $original['logo_url'], $original['template'], $original['primary_color'],
            $original['secondary_color'], $original['summary_note'], 
            $original['conditions_note'], $original['legal_note'],
            $original['json_tariff_data'], $_SESSION['user_id']
        ]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}