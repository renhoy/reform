<?php
// {"_META_file_path_": "public/process-budget.php"}
// Procesamiento usando esquema original completo

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$tariff_uuid = $_POST['tariff_uuid'] ?? null;
if (!$tariff_uuid) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getConnection();

try {
    // Obtener tarifa completa
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE uuid = ?");
    $stmt->execute([$tariff_uuid]);
    $tariff = $stmt->fetch();
    
    if (!$tariff) {
        throw new Exception("Tarifa no encontrada");
    }
    
    $quantities = $_POST['quantity'] ?? [];
    
    // Calcular totales
    $tariff_data = json_decode($tariff['json_tariff_data'], true);
    $budget_items = [];
    $total_base = 0;
    $iva_breakdown = [];
    
    foreach ($tariff_data as $item) {
        if ($item['level'] === 'item' && isset($quantities[$item['id']])) {
            $quantity = floatval($quantities[$item['id']]);
            if ($quantity > 0) {
                $pvp = floatval($item['pvp'] ?? 0);
                $iva_rate = floatval($item['iva_percentage'] ?? 0);
                
                $total_item = $quantity * $pvp;
                $base_amount = $total_item / (1 + $iva_rate / 100);
                $iva_amount = $total_item - $base_amount;
                
                $budget_items[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? '',
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
    
    $budget_data = [
        'items' => $budget_items,
        'totals' => [
            'base' => $total_base,
            'iva_breakdown' => $iva_breakdown,
            'total_iva' => $total_iva,
            'final' => $total_final
        ]
    ];
    
    $uuid = generateUUID();
    
    // Guardar usando esquema original completo
    $stmt = $pdo->prepare("
        INSERT INTO budgets 
        (uuid, json_tariff_data, client_type, client_name, client_nif_nie, 
         client_phone, client_email, client_web, client_address, client_postal_code,
         client_locality, client_province, client_acceptance, json_budget_data, 
         status, total, iva, base, start_date, end_date, user_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?)
    ");
    $stmt->execute([
        $uuid,
        json_encode($tariff), // Snapshot completo tarifa
        $_POST['client_type'] ?? '',
        $_POST['client_name'] ?? '',
        $_POST['client_nif_nie'] ?? '',
        $_POST['client_phone'] ?? '',
        $_POST['client_email'] ?? '',
        $_POST['client_web'] ?? '',
        $_POST['client_address'] ?? '',
        $_POST['client_postal_code'] ?? '',
        $_POST['client_locality'] ?? '',
        $_POST['client_province'] ?? '',
        isset($_POST['client_acceptance']) ? 1 : 0,
        json_encode($budget_data),
        $total_final,
        $total_iva,
        $total_base,
        $_SESSION['user_id']
    ]);
    
    header('Location: budget-success.php?uuid=' . $uuid);
    exit;
    
} catch (Exception $e) {
    error_log("Budget processing error: " . $e->getMessage());
    header('Location: dashboard.php?error=processing');
    exit;
}