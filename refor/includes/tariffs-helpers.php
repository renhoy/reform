<?php
// {"_META_file_path_": "refor/includes/tariffs-helpers.php"}
// Alias para compatibilidad con sistema existente

// Incluir el archivo principal de funciones de tarifas
require_once __DIR__ . '/tariff-helpers.php';

// Funciones adicionales que usa tariffs.php
function getTariffsWithData($user_id = null) {
    $pdo = getConnection();
    
    $sql = "
        SELECT t.*, u.name as author_name,
               COUNT(b.id) as budgets_count
        FROM tariffs t 
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN budgets b ON t.id = b.tariff_id
    ";
    
    if ($user_id) {
        $sql .= " WHERE t.user_id = ?";
        $sql .= " GROUP BY t.id ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $sql .= " WHERE t.user_id = ?";
        $sql .= " GROUP BY t.id ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    return $stmt->fetchAll();
}

function isTariffComplete($tariff) {
    // Verificar si la tarifa tiene todos los datos necesarios
    if (empty($tariff['name']) || empty($tariff['json_tariff_data'])) {
        return false;
    }
    
    $tariff_data = json_decode($tariff['json_tariff_data'], true);
    if (!$tariff_data || !is_array($tariff_data)) {
        return false;
    }
    
    // Verificar que tenga al menos una partida (item)
    $hasItems = false;
    foreach ($tariff_data as $item) {
        if ($item['level'] === 'item') {
            $hasItems = true;
            break;
        }
    }
    
    return $hasItems;
}