<?php
// {"_META_file_path_": "refor/includes/config.php"}
// Configuración principal de la aplicación

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', 8889); // Puerto MAMP

// Rutas de la aplicación
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('ASSETS_URL', BASE_URL . '/assets');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_start();
}

/**
 * Obtiene la conexión a la base de datos
 */
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

/**
 * Requiere autenticación de usuario
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Genera un UUID único
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Genera URL para assets
 */
function asset($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Formatea un número para mostrar
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Formatea una fecha para mostrar
 */
function formatDate($date) {
    if (!$date) return '';
    
    $dateObj = is_string($date) ? new DateTime($date) : $date;
    return $dateObj->format('d/m/Y');
}

/**
 * Redirige a una página con parámetros opcionales
 */
function redirect($page, $params = []) {
    $url = $page;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Sanitiza una cadena para HTML
 */
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida un email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida un NIF español
 */
function isValidNIF($nif) {
    $nif = strtoupper(trim($nif));
    
    if (!preg_match('/^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/', $nif)) {
        return false;
    }
    
    $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $number = intval(substr($nif, 0, 8));
    $letter = substr($nif, 8, 1);
    
    return $letters[$number % 23] === $letter;
}

/**
 * Obtiene el usuario actual
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    static $user = null;
    
    if ($user === null) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    
    return $user;
}

/**
 * Registra una acción en el log
 */
function logAction($action, $details = '') {
    $user = getCurrentUser();
    $userId = $user ? $user['id'] : null;
    $userName = $user ? $user['name'] : 'Sistema';
    
    $logEntry = date('Y-m-d H:i:s') . " - Usuario: {$userName} ({$userId}) - Acción: {$action}";
    if ($details) {
        $logEntry .= " - Detalles: {$details}";
    }
    
    error_log($logEntry);
}

/**
 * Envía respuesta JSON y termina ejecución
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Obtiene valor de configuración del sistema
 */
function getConfigValue($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['config_key']] = $row['config_value'];
        }
    }
    
    return $config[$key] ?? $default;
}

/**
 * Calcula días hábiles entre dos fechas
 */
function getBusinessDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $days = 0;
    
    while ($start <= $end) {
        $dayOfWeek = $start->format('N');
        if ($dayOfWeek < 6) { // Lunes a Viernes
            $days++;
        }
        $start->add(new DateInterval('P1D'));
    }
    
    return $days;
}

// Verificar conexión al inicializar
try {
    $testConnection = getConnection();
    $testConnection->query("SELECT 1");
} catch (Exception $e) {
    die("Error: Base de datos no disponible. Asegúrate de:\n1. MAMP está ejecutándose\n2. Base de datos 'budget_form_service' existe\n3. Configuración de conexión es correcta");
}