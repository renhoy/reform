<?php
// {"_META_file_path_": "public/index.php"}
// Punto de entrada único de la aplicación

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Definir rutas base
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

// Autoload básico
spl_autoload_register(function ($className) {
    $paths = [
        SRC_PATH . '/controllers/' . $className . '.php',
        SRC_PATH . '/models/' . $className . '.php',
        SRC_PATH . '/utils/' . $className . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Incluir configuración
$configFile = SRC_PATH . '/config/config.php';
if (!file_exists($configFile)) {
    die('Error: Archivo de configuración no encontrado.');
}
require_once $configFile;

// Incluir rutas
$routesFile = SRC_PATH . '/config/routes.php';
if (!file_exists($routesFile)) {
    die('Error: Archivo de rutas no encontrado.');
}
require_once $routesFile;

// Obtener la ruta solicitada desde REQUEST_URI
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Extraer la ruta limpia
$base_path = dirname($script_name);
if ($base_path === '.' || $base_path === '/') {
    $base_path = '';
}

$request = str_replace($base_path, '', $request_uri);
$request = parse_url($request, PHP_URL_PATH);
$request = rtrim($request, '/') ?: '/';

// Verificar si Router existe
if (!class_exists('Router')) {
    die('Error: Clase Router no encontrada.');
}

// Ejecutar router
try {
    $router = new Router();
    $router->handle($request);
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    http_response_code(500);
    die('Error interno del servidor.');
}