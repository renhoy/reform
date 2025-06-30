<?php
// {"_META_file_path_": "src/controllers/TariffController.php"}
// Controlador de tarifas

class TariffController {
    
    public function duplicate($id) {
        requireAuth();
        
        $pdo = getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Obtener tarifa original
            $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
            $stmt->execute([$id]);
            $original_tariff = $stmt->fetch();
            
            if ($original_tariff) {
                // Crear nueva tarifa
                $new_name = $original_tariff['name'] . ' (Copia)';
                $stmt = $pdo->prepare("INSERT INTO tariffs (name, file_path, json_data) VALUES (?, ?, ?)");
                $stmt->execute([$new_name, $original_tariff['file_path'], $original_tariff['json_data']]);
                $new_tariff_id = $pdo->lastInsertId();
                
                // Duplicar configuración de empresa
                $stmt = $pdo->prepare("SELECT * FROM company_config WHERE tariff_id = ?");
                $stmt->execute([$id]);
                $original_config = $stmt->fetch();
                
                if ($original_config) {
                    $stmt = $pdo->prepare("
                        INSERT INTO company_config 
                        (tariff_id, name, nif, address, contact, logo_url, template, primary_color, secondary_color, summary_note, conditions_note, legal_note) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $new_tariff_id,
                        $original_config['name'],
                        $original_config['nif'],
                        $original_config['address'],
                        $original_config['contact'],
                        $original_config['logo_url'],
                        $original_config['template'],
                        $original_config['primary_color'],
                        $original_config['secondary_color'],
                        $original_config['summary_note'],
                        $original_config['conditions_note'],
                        $original_config['legal_note']
                    ]);
                }
            }
            
            $pdo->commit();
            header('Location: ' . url('tariffs?duplicated=1'));
            exit;
        } catch (Exception $e) {
            $pdo->rollback();
            header('Location: ' . url('tariffs?error=' . urlencode($e->getMessage())));
            exit;
        }
    }
    
    public function delete($id) {
        requireAuth();
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: ' . url('tariffs?deleted=1'));
            exit;
        } catch (Exception $e) {
            header('Location: ' . url('tariffs?error=' . urlencode($e->getMessage())));
            exit;
        }
    }
}