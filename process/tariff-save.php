<?php
// {"_META_file_path_": "refor/process/tariff-save.php"}
// Procesamiento de guardado de tarifas

require_once '../includes/config.php';
require_once '../includes/tariff-helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../tariffs');
}

$tariff_id = $_GET['id'] ?? null;

try {
    $data = [
        'tariff_name' => trim($_POST['tariff_name'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'company_nif' => trim($_POST['company_nif'] ?? ''),
        'company_address' => trim($_POST['company_address'] ?? ''),
        'company_contact' => trim($_POST['company_contact'] ?? ''),
        'logo_url' => trim($_POST['logo_url'] ?? ''),
        'template' => trim($_POST['template'] ?? '41200-00001'),
        'primary_color' => $_POST['primary_color'] ?? '#e8951c',
        'secondary_color' => $_POST['secondary_color'] ?? '#109c61',
        'summary_note' => trim($_POST['summary_note'] ?? ''),
        'conditions_note' => trim($_POST['conditions_note'] ?? ''),
        'legal_note' => trim($_POST['legal_note'] ?? ''),
        'csv_data' => $_POST['csv_data'] ?? ''
    ];
    
    // Validaciones
    if (empty($data['tariff_name'])) {
        throw new Exception('El nombre de la tarifa es obligatorio');
    }
    
    if (empty($data['company_name'])) {
        throw new Exception('El nombre de la empresa es obligatorio');
    }
    
    $result_id = saveTariff($data, $tariff_id);
    
    if ($tariff_id) {
        redirect('../tariff-form', ['id' => $tariff_id, 'success' => 'Tarifa actualizada correctamente']);
    } else {
        redirect('../tariffs', ['success' => 'Tarifa creada correctamente']);
    }
    
} catch (Exception $e) {
    $errorParam = $tariff_id ? ['id' => $tariff_id, 'error' => $e->getMessage()] : ['error' => $e->getMessage()];
    redirect('../tariff-form', $errorParam);
}