<?php
// {"_META_file_path_": "refor/includes/tariff-helpers.php"}
// Funciones auxiliares para tarifas

function getAllTariffs($user_id = null) {
    $pdo = getConnection();
    
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tariffs ORDER BY created_at DESC");
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

function getTariffById($id, $user_id = null) {
    $pdo = getConnection();
    
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    return $stmt->fetch();
}

function getTariffByUuid($uuid, $user_id = null) {
    $pdo = getConnection();
    
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE uuid = ? AND user_id = ?");
        $stmt->execute([$uuid, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE uuid = ?");
        $stmt->execute([$uuid]);
    }
    
    return $stmt->fetch();
}

function createTariff($data) {
    $pdo = getConnection();
    
    try {
        $uuid = generateUUID();
        
        $stmt = $pdo->prepare("
            INSERT INTO tariffs 
            (uuid, title, description, logo_url, name, nif, address, contact, 
             template, primary_color, secondary_color, summary_note, conditions_note, 
             access, status, legal_note, json_tariff_data, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $uuid,
            $data['title'],
            $data['description'] ?? null,
            $data['logo_url'] ?? null,
            $data['name'],
            $data['nif'] ?? null,
            $data['address'] ?? null,
            $data['contact'] ?? null,
            $data['template'] ?? '41200-00001',
            $data['primary_color'] ?? '#e8951c',
            $data['secondary_color'] ?? '#109c61',
            $data['summary_note'] ?? null,
            $data['conditions_note'] ?? null,
            $data['access'] ?? 'private',
            $data['status'] ?? 'active',
            $data['legal_note'] ?? null,
            json_encode($data['json_tariff_data']),
            $_SESSION['user_id']
        ]);
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        
        return false;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function updateTariff($id, $data) {
    $pdo = getConnection();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE tariffs SET 
            title = ?, description = ?, logo_url = ?, name = ?, nif = ?, 
            address = ?, contact = ?, template = ?, primary_color = ?, 
            secondary_color = ?, summary_note = ?, conditions_note = ?, 
            access = ?, status = ?, legal_note = ?, json_tariff_data = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $result = $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['logo_url'] ?? null,
            $data['name'],
            $data['nif'] ?? null,
            $data['address'] ?? null,
            $data['contact'] ?? null,
            $data['template'] ?? '41200-00001',
            $data['primary_color'] ?? '#e8951c',
            $data['secondary_color'] ?? '#109c61',
            $data['summary_note'] ?? null,
            $data['conditions_note'] ?? null,
            $data['access'] ?? 'private',
            $data['status'] ?? 'active',
            $data['legal_note'] ?? null,
            json_encode($data['json_tariff_data']),
            $id,
            $_SESSION['user_id']
        ]);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function deleteTariff($id) {
    $pdo = getConnection();
    
    try {
        // Verificar si hay presupuestos asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE tariff_id = ?");
        $stmt->execute([$id]);
        $budgetCount = $stmt->fetchColumn();
        
        if ($budgetCount > 0) {
            throw new Exception("No se puede eliminar la tarifa porque tiene presupuestos asociados");
        }
        
        $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function duplicateTariff($id) {
    $pdo = getConnection();
    
    try {
        // Obtener tarifa original
        $original = getTariffById($id, $_SESSION['user_id']);
        if (!$original) {
            throw new Exception("Tarifa no encontrada");
        }
        
        $uuid = generateUUID();
        
        // Crear tarifa duplicada
        $stmt = $pdo->prepare("
            INSERT INTO tariffs 
            (uuid, title, description, logo_url, name, nif, address, contact, 
             template, primary_color, secondary_color, summary_note, conditions_note, 
             access, status, legal_note, json_tariff_data, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $uuid,
            $original['title'] . ' (Copia)',
            $original['description'],
            $original['logo_url'],
            $original['name'],
            $original['nif'],
            $original['address'],
            $original['contact'],
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
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        
        return false;
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtiene los datos por defecto para una nueva tarifa basada en una plantilla
 * 
 * @param int $template_id ID de la plantilla a utilizar (por defecto 1)
 * @return array Datos de la tarifa por defecto
 */
function getDefaultTariffData($template_id = 1) {
    $pdo = getConnection();
    
    try {
        // Obtener datos de la plantilla
        $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();
        
        if (!$template) {
            // Si no se encuentra la plantilla, usar valores por defecto
            return [
                'title' => 'Nueva Tarifa',
                'description' => '',
                'logo_url' => '',
                'name' => '',
                'nif' => '',
                'address' => '',
                'contact' => '',
                'template' => '41200-00001',
                'primary_color' => '#e8951c',
                'secondary_color' => '#109c61',
                'summary_note' => '',
                'conditions_note' => '',
                'access' => 'private',
                'status' => 'active',
                'legal_note' => '',
                'json_tariff_data' => '[]'
            ];
        }
        
        // Convertir los datos de la plantilla a un array asociativo
        $template_data = json_decode($template['template_data'], true);
        
        // Crear estructura de tarifa basada en la plantilla
        return [
            'title' => $template_data['title'] ?? 'Nueva Tarifa',
            'description' => $template_data['description'] ?? '',
            'logo_url' => $template_data['logo_url'] ?? '',
            'name' => $template_data['name'] ?? '',
            'nif' => $template_data['nif'] ?? '',
            'address' => $template_data['address'] ?? '',
            'contact' => $template_data['contact'] ?? '',
            'template' => $template_data['template'] ?? '41200-00001',
            'primary_color' => $template_data['primary_color'] ?? '#e8951c',
            'secondary_color' => $template_data['secondary_color'] ?? '#109c61',
            'summary_note' => $template_data['summary_note'] ?? '',
            'conditions_note' => $template_data['conditions_note'] ?? '',
            'access' => 'private',
            'status' => 'active',
            'legal_note' => $template_data['legal_note'] ?? '',
            'json_tariff_data' => $template_data['json_tariff_data'] ?? '[]'
        ];
        
    } catch (Exception $e) {
        // En caso de error, devolver valores por defecto
        return [
            'title' => 'Nueva Tarifa',
            'description' => '',
            'logo_url' => '',
            'name' => '',
            'nif' => '',
            'address' => '',
            'contact' => '',
            'template' => '41200-00001',
            'primary_color' => '#e8951c',
            'secondary_color' => '#109c61',
            'summary_note' => '',
            'conditions_note' => '',
            'access' => 'private',
            'status' => 'active',
            'legal_note' => '',
            'json_tariff_data' => '[]'
        ];
    }
}

function validateTariffData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'El título es obligatorio';
    }
    
    if (empty($data['name'])) {
        $errors[] = 'El nombre de la empresa es obligatorio';
    }
    
    if (!empty($data['logo_url']) && !filter_var($data['logo_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL del logo no es válida';
    }
    
    if (!empty($data['primary_color']) && !preg_match('/^#[a-f0-9]{6}$/i', $data['primary_color'])) {
        $errors[] = 'El color primario debe ser un código hexadecimal válido';
    }
    
    if (!empty($data['secondary_color']) && !preg_match('/^#[a-f0-9]{6}$/i', $data['secondary_color'])) {
        $errors[] = 'El color secundario debe ser un código hexadecimal válido';
    }
    
    if (empty($data['json_tariff_data']) || !is_array($data['json_tariff_data'])) {
        $errors[] = 'Los datos de la tarifa son obligatorios';
    }
    
    return $errors;
}

function getTariffStatistics($user_id = null) {
    $pdo = getConnection();
    
    $baseQuery = "
        SELECT 
            COUNT(*) as total_tariffs,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
            SUM(CASE WHEN access = 'public' THEN 1 ELSE 0 END) as public_count,
            SUM(CASE WHEN access = 'private' THEN 1 ELSE 0 END) as private_count
        FROM tariffs
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

function getPublicTariffs() {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT * FROM tariffs 
        WHERE access = 'public' AND status = 'active' 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function updateTariffStatus($id, $status) {
    $validStatuses = ['active', 'inactive'];
    
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Estado no válido");
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE tariffs SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$status, $id, $_SESSION['user_id']]);
    
    return $stmt->rowCount() > 0;
}

function updateTariffAccess($id, $access) {
    $validAccess = ['public', 'private'];
    
    if (!in_array($access, $validAccess)) {
        throw new Exception("Acceso no válido");
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE tariffs SET access = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$access, $id, $_SESSION['user_id']]);
    
    return $stmt->rowCount() > 0;
}

function getTariffItemsCount($tariff_data) {
    if (!is_array($tariff_data)) {
        $tariff_data = json_decode($tariff_data, true);
    }
    
    $count = 0;
    foreach ($tariff_data as $item) {
        if ($item['level'] === 'item') {
            $count++;
        }
    }
    
    return $count;
}

function validateTariffStructure($tariff_data) {
    if (!is_array($tariff_data)) {
        return false;
    }
    
    foreach ($tariff_data as $item) {
        // Campos obligatorios para todos los elementos
        if (!isset($item['id']) || !isset($item['name']) || !isset($item['level'])) {
            return false;
        }
        
        // Validar niveles válidos
        if (!in_array($item['level'], ['chapter', 'subchapter', 'section', 'item'])) {
            return false;
        }
        
        // Campos adicionales para partidas (items)
        if ($item['level'] === 'item') {
            if (!isset($item['pvp']) || !isset($item['unit']) || !isset($item['iva_percentage'])) {
                return false;
            }
        }
    }
    
    return true;
}