<?php
// {"_META_file_path_": "src/config/routes.php"}
// Definición de rutas de la aplicación

$routes = [
    // Rutas principales
    '/' => 'dashboard',
    '/dashboard' => 'dashboard',
    
    // Autenticación
    '/login' => 'login',
    '/logout' => 'logout',
    
    // Tarifas
    '/tariffs' => 'tariffs',
    '/tariffs/new' => 'upload-tariff',
    '/tariffs/edit/{id}' => 'edit-tariff',
    '/tariffs/duplicate/{id}' => [TariffController::class, 'duplicate'],
    '/tariffs/delete/{id}' => [TariffController::class, 'delete'],
    
    // Presupuestos
    '/budgets' => 'budgets',
    '/budgets/form/{tariff_id}' => 'form',
    '/budgets/process' => [BudgetController::class, 'process'],
    '/budgets/success/{uuid}' => 'budget-success',
    '/budgets/pending/{uuid}' => 'budget-pending',
    
    // API endpoints
    '/api/tariffs' => [ApiController::class, 'tariffs'],
    '/api/upload-csv' => [ApiController::class, 'uploadCsv'],
];