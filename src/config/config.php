<?php
// {"_META_file_path_": "src/config/config.php"}
// Configuración principal de la aplicación

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', 8889); // Puerto estándar MAMP

// Rutas de la aplicación
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('UPLOAD_DIR', PUBLIC_PATH . '/assets/uploads/');
define('ASSETS_URL', BASE_URL . '/assets');

// Configuración de servicios externos
define('PDF_SERVICE_URL', 'https://your-pdf-service.com/generate');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
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
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Error de conexión a la base de datos. Verifica que MAMP esté ejecutándose y la base de datos exista.");
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
        return BASE_URL . '/dashboard.php';
    }
    
    // Convertir rutas del router a archivos directos
    $routes = [
        'dashboard' => 'dashboard.php',
        'tariffs' => 'tariffs.php',
        'budgets' => 'budgets.php',
        'login' => 'login.php',
        'logout' => 'logout.php'
    ];
    
    if (isset($routes[$path])) {
        return BASE_URL . '/' . $routes[$path];
    }
    
    return BASE_URL . '/' . ltrim($path, '/');
}

// Verificar conexión y base de datos
try {
    $testConnection = getConnection();
    $testConnection->query("SELECT 1");
} catch (Exception $e) {
    die("Error: Base de datos no disponible. Asegúrate de:\n1. MAMP está ejecutándose\n2. Base de datos 'budget_form_service' existe\n3. Configuración de conexión es correcta");
}