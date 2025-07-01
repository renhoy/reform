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
 * Verifica si una tarifa está completa
 */
function isTariffComplete($tariff) {
    $requiredFields = [
        'title', 'name', 'nif', 'address', 'contact', 
        'logo_url', 'template', 'primary_color', 'secondary_color',
        'summary_note', 'conditions_note', 'legal_note', 'json_tariff_data'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($tariff[$field])) {
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
        
        // Crear duplicado
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
        
        $newId = $pdo->lastInsertId();
        $pdo->commit();
        
        return $newId;
        
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

/**
 * Elimina una tarifa
 */
function deleteTariff($tariffId) {
    $pdo = getConnection();
    
    try {
        $pdo->beginTransaction();
        
        // Verificar que no tenga presupuestos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE tariff_id = ?");
        $stmt->execute([$tariffId]);
        $budgetCount = $stmt->fetchColumn();
        
        if ($budgetCount > 0) {
            throw new Exception("No se puede eliminar una tarifa con presupuestos asociados");
        }
        
        // Eliminar tarifa
        $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$tariffId, $_SESSION['user_id']]);
        
        $pdo->commit();
        return $result;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}