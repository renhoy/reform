<?php
// {"_META_file_path_": "refor/includes/budget-helpers.php"}
// Funciones auxiliares para presupuestos - versión actualizada

function getAllBudgets($user_id = null) {
    $pdo = getConnection();
    
    $sql = "
        SELECT b.*, t.title as tariff_title, t.name as company_name
        FROM budgets b 
        LEFT JOIN tariffs t ON b.tariff_id = t.id 
    ";
    
    if ($user_id) {
        $sql .= " WHERE b.user_id = ? ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $sql .= " ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

function getBudgetById($id, $user_id = null) {
    $pdo = getConnection();
    
    $sql = "
        SELECT b.*, t.title as tariff_title, t.name as company_name,
               t.nif as company_nif, t.address as company_address,
               t.contact as company_contact, t.logo_url as company_logo
        FROM budgets b 
        LEFT JOIN tariffs t ON b.tariff_id = t.id 
        WHERE b.id = ?
    ";
    
    if ($user_id) {
        $sql .= " AND b.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
    }
    
    return $stmt->fetch();
}

function getBudgetByUuid($uuid, $user_id = null) {
    $pdo = getConnection();
    
    $sql = "
        SELECT b.*, t.title as tariff_title, t.name as company_name,
               t.nif as company_nif, t.address as company_address,
               t.contact as company_contact, t.logo_url as company_logo
        FROM budgets b 
        LEFT JOIN tariffs t ON b.tariff_id = t.id 
        WHERE b.uuid = ?
    ";
    
    if ($user_id) {
        $sql .= " AND b.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uuid, $user_id]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uuid]);
    }
    
    return $stmt->fetch();
}

function updateBudgetStatus($id, $status, $user_id = null) {
    $validStatuses = ['draft', 'pending', 'sent', 'approved', 'rejected', 'expired'];
    
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Estado no válido");
    }
    
    $pdo = getConnection();
    
    if ($user_id) {
        $stmt = $pdo->prepare("UPDATE budgets SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $id, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE budgets SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
    
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
                client_address = ?, client_postal_code = ?, client_locality = ?,
                client_province = ?, client_acceptance = ?, json_budget_data = ?,
                total = ?, iva = ?, base = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $data['client_type'],
                $data['client_name'],
                $data['client_nif_nie'],
                $data['client_phone'],
                $data['client_email'],
                $data['client_web'] ?? null,
                $data['client_address'],
                $data['client_postal_code'],
                $data['client_locality'],
                $data['client_province'],
                $data['client_acceptance'] ? 1 : 0,
                json_encode($totals['budget_data']),
                $totals['total_final'],
                $totals['total_iva'],
                $totals['total_base'],
                $budget_id,
                $_SESSION['user_id']
            ]);
        } else {
            // Crear nuevo presupuesto
            $stmt = $pdo->prepare("
                INSERT INTO budgets 
                (uuid, tariff_id, json_tariff_data, client_type, client_name, client_nif_nie, 
                 client_phone, client_email, client_web, client_address, client_postal_code,
                 client_locality, client_province, client_acceptance, json_budget_data, 
                 status, total, iva, base, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uuid,
                $data['tariff_id'],
                json_encode($data['tariff_company_data']),
                $data['client_type'],
                $data['client_name'],
                $data['client_nif_nie'],
                $data['client_phone'],
                $data['client_email'],
                $data['client_web'] ?? null,
                $data['client_address'],
                $data['client_postal_code'],
                $data['client_locality'],
                $data['client_province'],
                $data['client_acceptance'] ? 1 : 0,
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
    if (!$tariff) {
        throw new Exception("Tarifa no encontrada");
    }
    
    $tariff_data = json_decode($tariff['json_tariff_data'], true);
    if (!$tariff_data) {
        throw new Exception("Error al procesar datos de la tarifa");
    }
    
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
                    'total' => $total_item,
                    'base_amount' => $base_amount,
                    'iva_amount' => $iva_amount
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
        $original = getBudgetById($id, $_SESSION['user_id']);
        if (!$original) {
            throw new Exception("Presupuesto no encontrado");
        }
        
        $uuid = generateUUID();
        
        // Crear presupuesto duplicado
        $stmt = $pdo->prepare("
            INSERT INTO budgets 
            (uuid, tariff_id, json_tariff_data, client_type, client_name, client_nif_nie, 
             client_phone, client_email, client_web, client_address, client_postal_code,
             client_locality, client_province, client_acceptance, json_budget_data, 
             status, total, iva, base, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $uuid,
            $original['tariff_id'],
            $original['json_tariff_data'],
            $original['client_type'],
            $original['client_name'] . ' (Copia)',
            $original['client_nif_nie'],
            $original['client_phone'],
            $original['client_email'],
            $original['client_web'],
            $original['client_address'],
            $original['client_postal_code'],
            $original['client_locality'],
            $original['client_province'],
            $original['client_acceptance'],
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
        $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function getBudgetsByStatus($status, $user_id = null) {
    $pdo = getConnection();
    
    $sql = "
        SELECT b.*, t.title as tariff_title, t.name as company_name
        FROM budgets b 
        LEFT JOIN tariffs t ON b.tariff_id = t.id 
        WHERE b.status = ?
    ";
    
    if ($user_id) {
        $sql .= " AND b.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $user_id]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
    }
    
    return $stmt->fetchAll();
}

function getBudgetStatistics($user_id = null) {
    $pdo = getConnection();
    
    $baseQuery = "
        SELECT 
            COUNT(*) as total_budgets,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count,
            SUM(CASE WHEN status = 'approved' THEN total ELSE 0 END) as total_approved_amount,
            AVG(CASE WHEN status = 'approved' THEN total ELSE NULL END) as avg_approved_amount
        FROM budgets
    ";
    
    if ($user_id) {
        $baseQuery .= " WHERE user_id = ?";
        $stmt = $pdo->prepare($baseQuery);
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare($baseQuery);
        $stmt->execute();
    }
    
    return $stmt->fetch();
}

function exportBudgetToPDF($budget_id) {
    // Esta función se implementará más adelante cuando se integre la generación de PDF
    // Por ahora retorna false para indicar que no está implementada
    return false;
}

function sendBudgetByEmail($budget_id, $email_data) {
    // Esta función se implementará más adelante cuando se integre el envío de emails
    // Por ahora retorna false para indicar que no está implementada
    return false;
}

function validateBudgetData($data) {
    $errors = [];
    
    // Validar datos obligatorios del cliente
    if (empty($data['client_name'])) {
        $errors[] = 'El nombre del cliente es obligatorio';
    }
    
    if (empty($data['client_nif_nie'])) {
        $errors[] = 'El NIF/NIE del cliente es obligatorio';
    }
    
    if (empty($data['client_phone'])) {
        $errors[] = 'El teléfono del cliente es obligatorio';
    }
    
    if (empty($data['client_email'])) {
        $errors[] = 'El email del cliente es obligatorio';
    } elseif (!filter_var($data['client_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email del cliente no es válido';
    }
    
    if (empty($data['client_address'])) {
        $errors[] = 'La dirección del cliente es obligatoria';
    }
    
    if (empty($data['client_postal_code'])) {
        $errors[] = 'El código postal es obligatorio';
    }
    
    if (empty($data['client_locality'])) {
        $errors[] = 'La localidad es obligatoria';
    }
    
    if (empty($data['client_province'])) {
        $errors[] = 'La provincia es obligatoria';
    }
    
    if (empty($data['client_acceptance'])) {
        $errors[] = 'Debe aceptar los términos y condiciones';
    }
    
    // Validar que exista al menos una partida con cantidad > 0
    $hasItems = false;
    if (isset($data['quantities']) && is_array($data['quantities'])) {
        foreach ($data['quantities'] as $quantity) {
            if (floatval($quantity) > 0) {
                $hasItems = true;
                break;
            }
        }
    }
    
    if (!$hasItems) {
        $errors[] = 'Debe incluir al menos una partida con cantidad mayor a cero';
    }
    
    return $errors;
}

function getBudgetStatusLabel($status) {
    $labels = [
        'draft' => 'Borrador',
        'pending' => 'Pendiente',
        'sent' => 'Enviado',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'expired' => 'Expirado'
    ];
    
    return $labels[$status] ?? $status;
}

function getBudgetStatusClass($status) {
    $classes = [
        'draft' => 'badge--secondary',
        'pending' => 'badge--warning',
        'sent' => 'badge--info',
        'approved' => 'badge--success',
        'rejected' => 'badge--danger',
        'expired' => 'badge--black'
    ];
    
    return $classes[$status] ?? 'badge--secondary';
}