<?php
// {"_META_file_path_": "refor/includes/config.php"}
// Configuración base del sistema refactorizado

session_start();

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_form_service');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', '8889');

// Configuración de rutas
define('BASE_URL', '/');
define('ASSETS_URL', BASE_URL . 'assets/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function getConnection() {
    global $pdo;
    return $pdo;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function asset($path) {
    return ASSETS_URL . $path;
}

function url($path = '') {
    return BASE_URL . $path;
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function flash($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatCurrency($amount, $decimals = 2) {
    return number_format($amount, $decimals, ',', '.') . ' €';
}

function formatPercentage($percentage, $decimals = 2) {
    return number_format($percentage, $decimals, ',', '.') . '%';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
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

function logError($message, $context = []) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $log .= " - Context: " . json_encode($context);
    }
    
    // Crear directorio logs si no existe
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($log . PHP_EOL, 3, $logDir . '/app.log');
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function updateUserLastAccess($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE users SET last_access = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
}

// Función para manejar errores de forma consistente
function handleError($message, $code = 500) {
    http_response_code($code);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        isset($_SERVER['HTTP_CONTENT_TYPE']) && 
        strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        // Mostrar página de error
        include __DIR__ . '/../pages/error.php';
    }
    exit;
}

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    logError("PHP Error: $message in $file:$line", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    
    if ($severity === E_ERROR || $severity === E_USER_ERROR) {
        handleError('Ha ocurrido un error interno del servidor');
    }
    
    return true;
});

set_exception_handler(function($exception) {
    logError("Uncaught Exception: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    handleError('Ha ocurrido un error inesperado');
});

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de sesión más segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Regenerar ID de sesión periódicamente para mayor seguridad
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}