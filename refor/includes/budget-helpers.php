<?php
// {"_META_file_path_": "refor/includes/budget-helpers.php"}
// Funciones auxiliares para gestión de presupuestos

function getAllBudgets($user_id = null, $filters = []) {
    $pdo = getConnection();
    $sql = "SELECT b.*, t.name as tariff_name, u.name as author_name 
            FROM budgets b 
            LEFT JOIN tariffs t ON b.tariff_id = t.id 
            LEFT JOIN users u ON b.user_id = u.id";
    
    $whereConditions = [];
    $params = [];
    
    if ($user_id) {
        $whereConditions[] = "b.user_id = ?";
        $params[] = $user_id;
    }
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "b.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['client_search'])) {
        $whereConditions[] = "(b.client_name LIKE ? OR b.client_nif_nie LIKE ?)";
        $searchTerm = '%' . $filters['client_search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['date_from'])) {
        $whereConditions[] = "DATE(b.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $whereConditions[] = "DATE(b.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getBudgetById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT b.*, t.name as tariff_name, t.json_data as tariff_data
        FROM budgets b 
        LEFT JOIN tariffs t ON b.tariff_id = t.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getBudgetByUuid($uuid) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT b.*, t.name as tariff_name, t.json_data as tariff_data
        FROM budgets b 
        LEFT JOIN tariffs t ON b.tariff_id = t.id 
        WHERE b.uuid = ?
    ");
    $stmt->execute([$uuid]);
    return $stmt->fetch();
}

function getBudgetStats($user_id = null) {
    $pdo = getConnection();
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
            FROM budgets";
    
    if ($user_id) {
        $sql .= " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    return $stmt->fetch();
}

function updateBudgetStatus($id, $status) {
    $validStatuses = ['draft', 'pending', 'sent', 'approved', 'rejected', 'expired'];
    
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Estado no válido");
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE budgets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    return $stmt->rowCount() > 0;
}

function saveBudget($data, $budget_id = null) {
    $pdo = getConnection();
    
    try {
        // Calcular totales
        $totals = calculateBudgetTotals($data);
        $uuid = $budget_id ? null : generateUUID();
        
        if ($budget_id) {
            // Actualizar presupuesto existente
            $stmt = $pdo->prepare("
                UPDATE budgets SET 
                client_type = ?, client_name = ?, client_nif_nie = ?, 
                client_phone = ?, client_email = ?, client_web = ?, 
                client_address = ?, json_budget_data = ?,
                total = ?, iva = ?, base = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['client_type'],
                $data['client_name'],
                $data['client_nif_nie'],
                $data['client_phone'],
                $data['client_email'],
                $data['client_web'],
                $data['client_address'],
                json_encode($totals['budget_data']),
                $totals['total_final'],
                $totals['total_iva'],
                $totals['total_base'],
                $budget_id
            ]);
        } else {
            // Crear nuevo presupuesto
            $stmt = $pdo->prepare("
                INSERT INTO budgets 
                (uuid, tariff_id, client_type, client_name, client_nif_nie, 
                 client_phone, client_email, client_web, client_address, 
                 json_budget_data, status, total, iva, base, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uuid,
                $data['tariff_id'],
                $data['client_type'],
                $data['client_name'],
                $data['client_nif_nie'],
                $data['client_phone'],
                $data['client_email'],
                $data['client_web'],
                $data['client_address'],
                json_encode($totals['budget_data']),
                $totals['total_final'],
                $totals['total_iva'],
                $totals['total_base'],
                $_SESSION['user_id']
            ]);
            $budget_id = $pdo->lastInsertId();
        }
        
        return $budget_id;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function calculateBudgetTotals($data) {
    // Obtener datos de la tarifa
    $tariff = getTariffById($data['tariff_id']);
    $tariff_data = json_decode($tariff['json_data'], true);
    
    $budget_items = [];
    $total_base = 0;
    $iva_breakdown = [];
    
    foreach ($tariff_data as $item) {
        if ($item['level'] === 'item' && isset($data['quantities'][$item['id']])) {
            $quantity = floatval($data['quantities'][$item['id']]);
            if ($quantity > 0) {
                $pvp = floatval($item['pvp']);
                $iva_rate = floatval($item['iva_percentage']);
                
                $total_item = $quantity * $pvp;
                $base_amount = $total_item / (1 + $iva_rate / 100);
                $iva_amount = $total_item - $base_amount;
                
                $budget_items[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit' => $item['unit'],
                    'pvp' => $pvp,
                    'iva_rate' => $iva_rate,
                    'total' => $total_item
                ];
                
                $total_base += $base_amount;
                
                if (!isset($iva_breakdown[$iva_rate])) {
                    $iva_breakdown[$iva_rate] = 0;
                }
                $iva_breakdown[$iva_rate] += $iva_amount;
            }
        }
    }
    
    $total_iva = array_sum($iva_breakdown);
    $total_final = $total_base + $total_iva;
    
    return [
        'budget_data' => [
            'items' => $budget_items,
            'totals' => [
                'base' => $total_base,
                'iva_breakdown' => $iva_breakdown,
                'total_iva' => $total_iva,
                'final' => $total_final
            ]
        ],
        'total_base' => $total_base,
        'total_iva' => $total_iva,
        'total_final' => $total_final
    ];
}

function duplicateBudget($id) {
    $pdo = getConnection();
    
    try {
        // Obtener presupuesto original
        $original = getBudgetById($id);
        if (!$original) {
            throw new Exception("Presupuesto no encontrado");
        }
        
        $uuid = generateUUID();
        
        // Crear presupuesto duplicado
        $stmt = $pdo->prepare("
            INSERT INTO budgets 
            (uuid, tariff_id, client_type, client_name, client_nif_nie, 
             client_phone, client_email, client_web, client_address, 
             json_budget_data, status, total, iva, base, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $uuid,
            $original['tariff_id'],
            $original['client_type'],
            $original['client_name'] . ' (Copia)',
            $original['client_nif_nie'],
            $original['client_phone'],
            $original['client_email'],
            $original['client_web'],
            $original['client_address'],
            $original['json_budget_data'],
            $original['total'],
            $original['iva'],
            $original['base'],
            $_SESSION['user_id']
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        throw $e;
    }
}

function deleteBudget($id) {
    $pdo = getConnection();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function checkExpiredBudgets() {
    $pdo = getConnection();
    
    // Actualizar presupuestos expirados
    $stmt = $pdo->prepare("
        UPDATE budgets 
        SET status = 'expired' 
        WHERE end_date < CURDATE() 
        AND status NOT IN ('approved', 'rejected', 'expired')
    ");
    $stmt->execute();
    
    return $stmt->rowCount();
}

function getStatusBadgeClass($status) {
    $statusClasses = [
        'draft' => 'badge-yellow',
        'pending' => 'badge-orange',
        'sent' => 'badge-blue',
        'approved' => 'badge-green',
        'rejected' => 'badge-red',
        'expired' => 'badge-gray'
    ];
    
    return $statusClasses[$status] ?? 'badge-gray';
}

function getStatusLabel($status) {
    $statusLabels = [
        'draft' => 'Borrador',
        'pending' => 'Pendiente',
        'sent' => 'Enviado',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'expired' => 'Expirado'
    ];
    
    return $statusLabels[$status] ?? 'Desconocido';
}