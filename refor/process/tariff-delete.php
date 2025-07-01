<?php
// {"_META_file_path_": "refor/process/tariff-delete.php"}
// Procesamiento de eliminación de tarifas

require_once '../includes/config.php';
require_once '../includes/tariff-helpers.php';

requireAuth();

$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('../tariffs', ['error' => 'ID de tarifa no válido']);
}

try {
    deleteTariff($id);
    redirect('../tariffs', ['deleted' => '1']);
    
} catch (Exception $e) {
    redirect('../tariffs', ['error' => $e->getMessage()]);
}