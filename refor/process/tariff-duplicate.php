<?php
// {"_META_file_path_": "refor/process/tariff-duplicate.php"}
// Procesamiento de duplicación de tarifas

require_once '../includes/config.php';
require_once '../includes/tariff-helpers.php';

requireAuth();

$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('../tariffs', ['error' => 'ID de tarifa no válido']);
}

try {
    $newTariffId = duplicateTariff($id);
    redirect('../tariffs', ['duplicated' => '1']);
    
} catch (Exception $e) {
    redirect('../tariffs', ['error' => $e->getMessage()]);
}