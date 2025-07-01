<?php
// {"_META_file_path_": "refor/includes/tariff-helpers.php"}
// Funciones auxiliares para gestión de tarifas - solo ajuste de BD

function getAllTariffs($user_id = null) {
    $pdo = getConnection();
    $sql = "SELECT * FROM tariffs";
    
    if ($user_id) {
        $sql .= " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY created_at DESC");
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

function getTariffById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getDefaultTariffData() {
    // Usar plantilla del sistema si existe
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT template_data FROM templates WHERE is_system = 1 LIMIT 1");
    $template = $stmt->fetch();
    
    if ($template && $template['template_data']) {
        $templateData = json_decode($template['template_data'], true);
        return [
            'name' => $templateData['title'] ?? '',
            'company_name' => $templateData['name'] ?? '',
            'nif' => $templateData['nif'] ?? '',
            'address' => $templateData['address'] ?? '',
            'contact' => $templateData['contact'] ?? '',
            'logo_url' => $templateData['logo_url'] ?? '',
            'template' => $templateData['template'] ?? '41200-00001',
            'primary_color' => $templateData['primary_color'] ?? '#e8951c',
            'secondary_color' => $templateData['secondary_color'] ?? '#109c61',
            'summary_note' => $templateData['summary_note'] ?? '',
            'conditions_note' => $templateData['conditions_note'] ?? '',
            'legal_note' => $templateData['legal_note'] ?? '',
            'json_data' => json_encode($templateData['json_tariff_data'] ?? [])
        ];
    }
    
    return [
        'name' => '',
        'company_name' => '',
        'nif' => '',
        'address' => '',
        'contact' => '',
        'logo_url' => '',
        'template' => '41200-00001',
        'primary_color' => '#e8951c',
        'secondary_color' => '#109c61',
        'summary_note' => '',
        'conditions_note' => '',
        'legal_note' => '',
        'json_data' => '[]'
    ];
}

function isTariffComplete($tariff) {
    $requiredFields = ['name', 'nif', 'address', 'contact'];
    
    foreach ($requiredFields as $field) {
        if (empty($tariff[$field])) {
            return false;
        }
    }
    
    return !empty($tariff['json_tariff_data']);
}

function saveTariff($data, $tariff_id = null) {
    $pdo = getConnection();
    
    try {
        $pdo->beginTransaction();
        
        if ($tariff_id) {
            // Actualizar tarifa existente
            $stmt = $pdo->prepare("
                UPDATE tariffs SET 
                title = ?, description = ?, name = ?, nif = ?, address = ?, contact = ?, 
                logo_url = ?, template = ?, primary_color = ?, secondary_color = ?,
                summary_note = ?, conditions_note = ?, legal_note = ?, json_tariff_data = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['tariff_name'],
                $data['description'] ?? '',
                $data['company_name'],
                $data['company_nif'],
                $data['company_address'],
                $data['company_contact'],
                $data['logo_url'],
                $data['template'],
                $data['primary_color'],
                $data['secondary_color'],
                $data['summary_note'],
                $data['conditions_note'],
                $data['legal_note'],
                $data['csv_data'],
                $tariff_id
            ]);
        } else {
            // Crear nueva tarifa
            $uuid = generateUUID();
            $jsonData = isset($data['csv_data']) && !empty($data['csv_data']) ? $data['csv_data'] : '[]';
            
            $stmt = $pdo->prepare("
                INSERT INTO tariffs 
                (uuid, title, description, name, nif, address, contact, logo_url, 
                 template, primary_color, secondary_color, summary_note, conditions_note, 
                 legal_note, json_tariff_data, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uuid,
                $data['tariff_name'],
                $data['description'] ?? '',
                $data['company_name'],
                $data['company_nif'],
                $data['company_address'],
                $data['company_contact'],
                $data['logo_url'],
                $data['template'],
                $data['primary_color'],
                $data['secondary_color'],
                $data['summary_note'],
                $data['conditions_note'],
                $data['legal_note'],
                $jsonData,
                $_SESSION['user_id']
            ]);
            $tariff_id = $pdo->lastInsertId();
        }
        
        $pdo->commit();
        return $tariff_id;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

function duplicateTariff($id) {
    $pdo = getConnection();
    
    try {
        $pdo->beginTransaction();
        
        $original = getTariffById($id);
        if (!$original) {
            throw new Exception("Tarifa no encontrada");
        }
        
        $newUuid = generateUUID();
        $newTitle = $original['title'] . ' (Copia)';
        
        $stmt = $pdo->prepare("
            INSERT INTO tariffs 
            (uuid, title, description, name, nif, address, contact, logo_url, 
             template, primary_color, secondary_color, summary_note, conditions_note, 
             access, status, legal_note, json_tariff_data, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $original['access'],
            $original['status'],
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

function deleteTariff($id) {
    $pdo = getConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE tariff_id = ?");
        $stmt->execute([$id]);
        $budgetCount = $stmt->fetchColumn();
        
        if ($budgetCount > 0) {
            throw new Exception("No se puede eliminar la tarifa porque tiene presupuestos asociados");
        }
        
        $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ?");
        $stmt->execute([$id]);
        
        return true;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function getBudgetCountByTariff($tariff_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE tariff_id = ?");
    $stmt->execute([$tariff_id]);
    return $stmt->fetchColumn();
}