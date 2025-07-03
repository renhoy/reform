<?php
// {"_META_file_path_": "refor/process/create-budget.php"}
// Procesar creación de presupuesto

require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $tariff_id = $_POST['tariff_id'] ?? null;
    $action = $_POST['action'] ?? 'pending';
    
    if (!$tariff_id) {
        throw new Exception('ID de tarifa requerido');
    }
    
    // Verificar tarifa
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
    $stmt->execute([$tariff_id, $_SESSION['user_id']]);
    $tariff = $stmt->fetch();
    
    if (!$tariff) {
        throw new Exception('Tarifa no encontrada');
    }
    
    // Datos del cliente
    $clientData = [
        'type' => $_POST['client_type'] ?? 'particular',
        'name' => $_POST['client_name'] ?? '',
        'nif_nie' => $_POST['client_nif_nie'] ?? '',
        'phone' => $_POST['client_phone'] ?? '',
        'email' => $_POST['client_email'] ?? '',
        'web' => $_POST['client_web'] ?? '',
        'address' => $_POST['client_address'] ?? '',
        'postal_code' => $_POST['client_postal_code'] ?? '',
        'locality' => $_POST['client_locality'] ?? '',
        'province' => $_POST['client_province'] ?? ''
    ];
    
    $quantities = $_POST['quantity'] ?? [];
    $tariffData = json_decode($tariff['json_tariff_data'], true) ?? [];
    
    // Calcular totales
    $budgetItems = [];
    $baseTotal = 0;
    $ivaTotal = 0;
    
    foreach ($tariffData as $item) {
        if ($item['level'] === 'item' && isset($quantities[$item['id']])) {
            $quantity = floatval($quantities[$item['id']]);
            if ($quantity > 0) {
                $price = floatval($item['pvp'] ?? 0);
                $ivaRate = floatval($item['iva_percentage'] ?? 21);
                
                $itemTotal = $quantity * $price;
                $baseAmount = $itemTotal / (1 + $ivaRate / 100);
                $ivaAmount = $itemTotal - $baseAmount;
                
                $budgetItems[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? 'ud',
                    'price' => $price,
                    'iva_rate' => $ivaRate,
                    'total' => $itemTotal
                ];
                
                $baseTotal += $baseAmount;
                $ivaTotal += $ivaAmount;
            }
        }
    }
    
    if (empty($budgetItems)) {
        throw new Exception('Debe seleccionar al menos una partida');
    }
    
    $finalTotal = $baseTotal + $ivaTotal;
    
    // Fechas
    $startDate = $_POST['start_date'] ?? date('Y-m-d');
    $validityDays = intval($_POST['validity_days'] ?? 30);
    $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $validityDays . ' days'));
    
    // Observaciones
    $observations = $_POST['observations'] ?? '';
    
    // Crear presupuesto
    $uuid = generateUUID();
    $status = in_array($action, ['draft', 'pending']) ? $action : 'pending';
    
    $budgetData = [
        'items' => $budgetItems,
        'totals' => [
            'base' => $baseTotal,
            'iva' => $ivaTotal,
            'final' => $finalTotal
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO budgets (
            uuid, tariff_id, json_observations, json_tariff_data, 
            client_type, client_name, client_nif_nie, client_phone, client_email, 
            client_web, client_address, client_postal_code, client_locality, client_province,
            json_budget_data, status, total, iva, base, start_date, end_date, validity_days, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $uuid,
        $tariff_id,
        $observations ? json_encode(['notes' => $observations]) : null,
        json_encode(['tariff_title' => $tariff['title']]),
        $clientData['type'],
        $clientData['name'],
        $clientData['nif_nie'],
        $clientData['phone'],
        $clientData['email'],
        $clientData['web'],
        $clientData['address'],
        $clientData['postal_code'],
        $clientData['locality'],
        $clientData['province'],
        json_encode($budgetData),
        $status,
        $finalTotal,
        $ivaTotal,
        $baseTotal,
        $startDate,
        $endDate,
        $validityDays,
        $_SESSION['user_id']
    ]);
    
    if (!$result) {
        throw new Exception('Error al crear el presupuesto');
    }
    
    $budgetId = $pdo->lastInsertId();
    
    // Redirigir según acción
    if ($action === 'draft') {
        header('Location: ../budgets.php?success=draft_created');
    } else {
        header('Location: ../budgets.php?success=created');
    }
    exit;
    
} catch (Exception $e) {
    header('Location: ../tariffs.php?error=' . urlencode($e->getMessage()));
    exit;
}