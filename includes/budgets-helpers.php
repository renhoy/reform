<?php
// {"_META_file_path_": "refor/includes/budgets-helpers.php"}
// Funciones auxiliares para gestión de presupuestos

/**
 * Obtiene presupuestos con filtros aplicados
 */
function getBudgetsWithFilters($search = '', $status = '', $dateFrom = '', $dateTo = '') {
    $pdo = getConnection();
    
    $query = "
        SELECT b.*, 
               u.name as author_name,
               t.title as tariff_title,
               JSON_UNQUOTE(JSON_EXTRACT(b.json_tariff_data, '$.name')) as tariff_name
        FROM budgets b
        LEFT JOIN users u ON b.user_id = u.id 
        LEFT JOIN tariffs t ON b.tariff_id = t.id
        WHERE b.user_id = :user_id
    ";
    
    $params = ['user_id' => $_SESSION['user_id']];
    
    if ($search) {
        $query .= " AND (b.client_name LIKE :search OR b.client_nif_nie LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($status) {
        $query .= " AND b.status = :status";
        $params['status'] = $status;
    }
    
    if ($dateFrom) {
        $query .= " AND DATE(b.created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $query .= " AND DATE(b.created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    $query .= " ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Obtiene estadísticas de presupuestos
 */
function getBudgetStats() {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
        FROM budgets 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch();
}

/**
 * Calcula días restantes para vencimiento
 */
function getDaysRemaining($endDate) {
    if (!$endDate) return null;
    
    $now = new DateTime();
    $end = new DateTime($endDate);
    $diff = $now->diff($end);
    
    if ($end < $now) return 0;
    
    return $diff->days;
}

/**
 * Obtiene apuntes de un presupuesto
 */
function getBudgetNotes($budgetId) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT json_observations FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$budgetId, $_SESSION['user_id']]);
    $budget = $stmt->fetch();
    
    if (!$budget || !$budget['json_observations']) {
        return [];
    }
    
    return json_decode($budget['json_observations'], true) ?: [];
}

/**
 * Añade un apunte a un presupuesto
 */
function addBudgetNote($budgetId, $category, $note) {
    $pdo = getConnection();
    
    // Obtener apuntes existentes
    $currentNotes = getBudgetNotes($budgetId);
    
    // Añadir nuevo apunte
    $newNote = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['user_name'] ?? 'Usuario',
        'category' => $category,
        'note' => $note
    ];
    
    $currentNotes[] = $newNote;
    
    // Actualizar en BD
    $stmt = $pdo->prepare("UPDATE budgets SET json_observations = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([
        json_encode($currentNotes),
        $budgetId,
        $_SESSION['user_id']
    ]);
}

/**
 * Actualiza el estado de un presupuesto
 */
function updateBudgetStatus($budgetId, $newStatus) {
    $validStatuses = ['draft', 'pending', 'sent', 'approved', 'rejected', 'expired'];
    
    if (!in_array($newStatus, $validStatuses)) {
        return false;
    }
    
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("UPDATE budgets SET status = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([$newStatus, $budgetId, $_SESSION['user_id']]);
}

/**
 * Duplica un presupuesto
 */
function duplicateBudget($budgetId) {
    $pdo = getConnection();
    
    // Obtener presupuesto original
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$budgetId, $_SESSION['user_id']]);
    $original = $stmt->fetch();
    
    if (!$original) return false;
    
    try {
        $pdo->beginTransaction();
        
        // Crear duplicado
        $newUuid = generateUUID();
        $stmt = $pdo->prepare("
            INSERT INTO budgets 
            (uuid, tariff_id, json_observations, json_tariff_data, client_type, client_name, 
             client_nif_nie, client_phone, client_email, client_web, client_address, 
             client_acceptance, json_budget_data, status, total, iva, base, 
             start_date, end_date, validity_days, user_id)
            VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, 
                    NULL, NULL, NULL, ?)
        ");
        
        $stmt->execute([
            $newUuid,
            $original['tariff_id'],
            $original['json_tariff_data'],
            $original['client_type'],
            $original['client_name'] . ' (Copia)',
            $original['client_nif_nie'],
            $original['client_phone'],
            $original['client_email'],
            $original['client_web'],
            $original['client_address'],
            $original['client_acceptance'],
            $original['json_budget_data'],
            $original['total'],
            $original['iva'],
            $original['base'],
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
 * Elimina un presupuesto
 */
function deleteBudget($budgetId) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    return $stmt->execute([$budgetId, $_SESSION['user_id']]);
}

/**
 * Verifica y actualiza presupuestos expirados
 */
function updateExpiredBudgets() {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        UPDATE budgets 
        SET status = 'expired' 
        WHERE end_date < CURDATE() 
        AND status NOT IN ('approved', 'rejected', 'expired')
    ");
    
    return $stmt->execute();
}