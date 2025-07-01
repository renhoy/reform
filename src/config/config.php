<?php
// {"_META_file_path_": "src/config/config.php"}
// Configuración principal corregida

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', 8889);

// Rutas base corregidas
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
if ($baseDir === '/' || $baseDir === '.') $baseDir = '';

define('BASE_URL', $protocol . '://' . $host . $baseDir);
define('UPLOAD_DIR', __DIR__ . '/../../public/assets/uploads/');
define('ASSETS_URL', BASE_URL . '/assets');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// Funciones auxiliares corregidas
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
            die("Error de conexión a la base de datos.");
        }
    }
    
    return $pdo;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
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

// Rutas directas - sin router
function asset($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function url($path = '') {
    // Mapeo directo a archivos
    $routes = [
        '' => 'dashboard.php',
        'dashboard' => 'dashboard.php',
        'tariffs' => 'tariffs.php',
        'tariffs/new' => 'upload-tariff.php',
        'budgets' => 'budgets.php',
        'login' => 'login.php',
        'logout' => 'logout.php'
    ];
    
    if (isset($routes[$path])) {
        return BASE_URL . '/' . $routes[$path];
    }
    
    // Para rutas con parámetros
    if (strpos($path, 'tariffs/edit/') === 0) {
        $id = str_replace('tariffs/edit/', '', $path);
        return BASE_URL . '/edit-tariff.php?id=' . $id;
    }
    
    if (strpos($path, 'tariffs/duplicate/') === 0) {
        $id = str_replace('tariffs/duplicate/', '', $path);
        return BASE_URL . '/duplicate-tariff.php?id=' . $id;
    }
    
    if (strpos($path, 'tariffs/delete/') === 0) {
        $id = str_replace('tariffs/delete/', '', $path);
        return BASE_URL . '/delete-tariff.php?id=' . $id;
    }
    
    if (strpos($path, 'budgets/form/') === 0) {
        $id = str_replace('budgets/form/', '', $path);
        return BASE_URL . '/form.php?tariff_id=' . $id;
    }
    
    return BASE_URL . '/' . ltrim($path, '/');
}

// Verificar conexión
try {
    getConnection()->query("SELECT 1");
} catch (Exception $e) {
    die("Error: Base de datos no disponible.");
}