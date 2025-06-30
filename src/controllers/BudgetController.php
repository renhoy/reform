<?php
// {"_META_file_path_": "src/controllers/BudgetController.php"}
// Controlador de presupuestos

class BudgetController {
    
    public function process() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('dashboard'));
            exit;
        }
        
        // Procesar datos del formulario
        $tariff_id = $_POST['tariff_id'] ?? null;
        $client_data = [
            'type' => $_POST['client_type'] ?? '',
            'name' => $_POST['name'] ?? '',
            'nif_nie' => $_POST['nif_nie'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'web' => $_POST['web'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];
        
        $quantities = $_POST['quantity'] ?? [];
        
        // Generar UUID para el presupuesto
        $uuid = generateUUID();
        
        try {
            $pdo = getConnection();
            
            // Obtener datos de la tarifa
            $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
            $stmt->execute([$tariff_id]);
            $tariff = $stmt->fetch();
            
            if (!$tariff) {
                throw new Exception("Tarifa no encontrada");
            }
            
            // Calcular totales
            $tariff_data = json_decode($tariff['json_data'], true);
            $budget_items = [];
            $total_base = 0;
            $iva_breakdown = [];
            
            foreach ($tariff_data as $item) {
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
            
            // Guardar presupuesto
            $stmt = $pdo->prepare("
                INSERT INTO budgets (uuid, tariff_id, client_data, budget_data, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $uuid,
                $tariff_id,
                json_encode($client_data),
                json_encode($budget_data)
            ]);
            
            header('Location: ' . url('budgets/pending/' . $uuid));
            exit;
            
        } catch (Exception $e) {
            error_log("Budget processing error: " . $e->getMessage());
            header('Location: ' . url('dashboard?error=processing'));
            exit;
        }
    }
}