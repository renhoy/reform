<?php
// {"_META_file_path_": "refor/includes/config.php"}
// Configuración unificada para la aplicación refor

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', 8889);

// Configuración de rutas relativas
define('BASE_DIR', dirname(__DIR__));
define('UPLOAD_DIR', BASE_DIR . '/assets/uploads/');
define('ASSETS_DIR', 'assets/');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// Conexión a base de datos
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

// Autenticación
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Funciones auxiliares
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
    return ASSETS_DIR . ltrim($path, '/');
}

function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function isCurrentPage($page) {
    $currentFile = basename($_SERVER['PHP_SELF'], '.php');
    
    // Mapeo de páginas para activar navegación
    $pageMap = [
        'dashboard' => ['dashboard', 'index'],
        'tariffs' => ['tariffs', 'tariff-form'],
        'budgets' => ['budgets', 'budget-form']
    ];
    
    if (isset($pageMap[$page])) {
        return in_array($currentFile, $pageMap[$page]);
    }
    
    return $currentFile === $page;
}

function showAlert($message, $type = 'info') {
    $alertClass = 'alert-' . $type;
    echo "<div class='alert {$alertClass}'>" . htmlspecialchars($message) . "</div>";
}

function redirect($page, $params = []) {
    $url = $page . '.php';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

// Verificar conexión inicial
try {
    $testConnection = getConnection();
    $testConnection->query("SELECT 1");
} catch (Exception $e) {
    die("Error: Base de datos no disponible. Asegúrate de que MAMP está ejecutándose y la base de datos existe.");
}