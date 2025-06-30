<?php
// {"_META_file_path_": "public/index.php"}
// Punto de entrada único de la aplicación

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir rutas base
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

// Autoload básico
spl_autoload_register(function ($className) {
    $file = SRC_PATH . '/controllers/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Incluir configuración
require_once SRC_PATH . '/config/config.php';
require_once SRC_PATH . '/config/routes.php';

// Obtener la ruta solicitada
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = str_replace('/public', '', $request);
$request = rtrim($request, '/') ?: '/';

// Ejecutar router
$router = new Router();
$router->handle($request);