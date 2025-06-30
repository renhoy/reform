<?php
// {"_META_file_path_": "public/update-budget.php"}
// Actualizar presupuesto existente

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: budgets.php');
    exit;
}

$budget_uuid = $_POST['budget_uuid'] ?? null;
if (!$budget_uuid) {
    header('Location: budgets.php');
    exit;
}

$pdo = getConnection();

try {
    // Obtener presupuesto existente
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE uuid = ? AND user_id = ?");
    $stmt->execute([$budget_uuid, $_SESSION['user_id']]);
    $budget = $stmt->fetch();
    
    if (!$budget) {
        throw new Exception("Presupuesto no encontrado");
    }
    
    // Datos del cliente actualizados
    $client_type = $_POST['client_type'] ?? '';
    $client_name = $_POST['name'] ?? '';
    $client_nif_nie = $_POST['nif_nie'] ?? '';
    $client_phone = $_POST['phone'] ?? '';
    $client_email = $_POST['email'] ?? '';
    $client_web = $_POST['web'] ?? '';
    $client_address = $_POST['address'] ?? '';
    $client_acceptance = isset($_POST['acceptance']);
    
    $quantities = $_POST['quantity'] ?? [];
    
    // Recalcular totales
    $tariff_data = json_decode($budget['json_tariff_data'], true);
    $tariff_items = json_decode($tariff_data['json_tariff_data'], true);
    $budget_items = [];
    $total_base = 0;
    $iva_breakdown = [];
    
    foreach ($tariff_items as $item) {
        if ($item['level'] === 'item' && isset($quantities[$item['id']])) {
            $quantity = floatval($quantities[$item['id']]);
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
    
    $budget_data = [
        'items' => $budget_items,
        'totals' => [
            'base' => $total_base,
            'iva_breakdown' => $iva_breakdown,
            'total_iva' => $total_iva,
            'final' => $total_final
        ]
    ];
    
    // Actualizar presupuesto (limpiar PDF si existía)
    $stmt = $pdo->prepare("
        UPDATE budgets SET 
        client_type = ?, client_name = ?, client_nif_nie = ?, 
        client_phone = ?, client_email = ?, client_web = ?, client_address = ?, 
        client_acceptance = ?, json_budget_data = ?, total = ?, iva = ?, base = ?,
        pdf_url = NULL, status = 'draft'
        WHERE uuid = ? AND user_id = ?
    ");
    $stmt->execute([
        $client_type, $client_name, $client_nif_nie,
        $client_phone, $client_email, $client_web, $client_address,
        $client_acceptance, json_encode($budget_data), 
        $total_final, $total_iva, $total_base,
        $budget_uuid, $_SESSION['user_id']
    ]);
    
    // Redirigir a lista de presupuestos
    header('Location: budgets.php?updated=1');
    exit;
    
} catch (Exception $e) {
    error_log("Budget update error: " . $e->getMessage());
    header('Location: budgets.php?error=update');
    exit;
}