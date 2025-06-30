<?php
// {"_META_file_path_": "src/config/config.php"}
// Configuración principal de la aplicación

// Configuración de base de datos
define('DB_HOST', 'localhost:8889');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Rutas de la aplicación
define('BASE_URL', '/public');
define('UPLOAD_DIR', PUBLIC_PATH . '/assets/uploads/');
define('ASSETS_URL', BASE_URL . '/assets');

// Configuración de servicios externos
define('PDF_SERVICE_URL', 'https://your-pdf-service.com/generate');

// Configuración de sesión
session_start();

// Funciones auxiliares
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function asset($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}