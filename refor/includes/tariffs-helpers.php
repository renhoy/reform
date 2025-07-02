<?php
// {"_META_file_path_": "refor/includes/tariffs-helpers.php"}
// Funciones auxiliares para gestión de tarifas

/**
 * Obtiene tarifas con datos adicionales
 */
function getTariffsWithData() {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT t.*, 
               u.name as author_name,
               COALESCE((SELECT COUNT(*) FROM budgets WHERE tariff_id = t.id), 0) as budgets_count
        FROM tariffs t
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetchAll();
}

/**
 * Obtiene una tarifa por ID
 */
function getTariffById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Verifica si una tarifa está completa
 */
function isTariffComplete($tariff) {
    $requiredFields = [
        'title', 'name', 'nif', 'address', 'contact', 
        'json_tariff_data'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($tariff[$field])) {
            return false;
        }
    }
    
    // Verificar que json_tariff_data sea válido
    if ($tariff['json_tariff_data']) {
        $data = json_decode($tariff['json_tariff_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Actualiza el acceso de una tarifa
 */
function updateTariffAccess($tariffId, $access) {
    $validAccess = ['public', 'private'];
    
    if (!in_array($access, $validAccess)) {
        return false;
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE tariffs SET access = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([$access, $tariffId, $_SESSION['user_id']]);
}

/**
 * Actualiza el estado de una tarifa
 */
function updateTariffStatus($tariffId, $status) {
    $validStatuses = ['active', 'inactive'];
    
    if (!in_array($status, $validStatuses)) {
        return false;
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE tariffs SET status = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([$status, $tariffId, $_SESSION['user_id']]);
}

/**
 * Duplica una tarifa
 */
function duplicateTariff($tariffId) {
    $pdo = getConnection();
    
    // Obtener tarifa original
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
    $stmt->execute([$tariffId, $_SESSION['user_id']]);
    $original = $stmt->fetch();
    
    if (!$original) return false;
    
    try {
        $pdo->beginTransaction();
        
        // Generar nuevo UUID
        $newUuid = generateUUID();
        $newTitle = $original['title'] . ' (Copia)';
        
        $stmt = $pdo->prepare("
            INSERT INTO tariffs 
            (uuid, title, description, name, nif, address, contact, logo_url, template, 
             primary_color, secondary_color, summary_note, conditions_note, access, status,
             legal_note, json_tariff_data, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'private', 'inactive', ?, ?, ?)
        ");
        
        $stmt->execute([
            $newUuid,
            $newTitle,
            $original['description'],
            $original['name'],
            $original['nif'],
            $original['address'],
            $original['contact'],
            $original['logo_url'],
            $original['template'],
            $original['primary_color'],
            $original['secondary_color'],
            $original['summary_note'],
            $original['conditions_note'],
            $original['legal_note'],
            $original['json_tariff_data'],
            $_SESSION['user_id']
        ]);
        
        $newTariffId = $pdo->lastInsertId();
        $pdo->commit();
        return $newTariffId;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

/**
 * Elimina una tarifa
 */
function deleteTariff($id) {
    $pdo = getConnection();
    
    try {
        // Verificar que no tiene presupuestos asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE tariff_id = ?");
        $stmt->execute([$id]);
        $budgetCount = $stmt->fetchColumn();
        
        if ($budgetCount > 0) {
            throw new Exception("No se puede eliminar la tarifa porque tiene presupuestos asociados");
        }
        
        // Eliminar tarifa
        $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtiene el conteo de presupuestos por tarifa
 */
function getBudgetCountByTariff($tariff_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE tariff_id = ?");
    $stmt->execute([$tariff_id]);
    return $stmt->fetchColumn();
}