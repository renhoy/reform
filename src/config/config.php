<?php
// {"_META_file_path_": "src/config/config.php"}
// Configuración sin .htaccess

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', 8889);

// Detectar URL base automáticamente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$projectPath = dirname($_SERVER['SCRIPT_NAME']);

define('BASE_URL', $protocol . '://' . $host . $projectPath);
define('ASSETS_URL', BASE_URL . '/public/assets');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funciones auxiliares
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . url('login'));
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
    if (empty($path)) {
        return BASE_URL . '/public/dashboard.php';
    }
    
    // Rutas simples a archivos PHP
    $routes = [
        'dashboard' => '/public/dashboard.php',
        'tariffs' => '/public/tariffs.php',
        'budgets' => '/public/budgets.php',
        'login' => '/public/login.php',
        'logout' => '/public/logout.php',
        'upload-tariff' => '/public/upload-tariff.php'
    ];
    
    if (isset($routes[$path])) {
        return BASE_URL . $routes[$path];
    }
    
    return BASE_URL . '/public/' . ltrim($path, '/');
}

// Verificar conexión
try {
    getConnection();
} catch (Exception $e) {
    die("Error: Base de datos no disponible.");
}