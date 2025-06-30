<?php
// {"_META_file_path_": "src/controllers/Router.php"}
// Sistema de rutas para la aplicación

class Router {
    private $routes = [];

    public function __construct() {
        $this->loadRoutes();
    }

    private function loadRoutes() {
        global $routes;
        $this->routes = $routes;
    }

    public function handle($request) {
        // Normalizar la ruta
        $request = rtrim($request, '/') ?: '/';
        
        // Buscar ruta exacta
        if (isset($this->routes[$request])) {
            $this->executeRoute($this->routes[$request]);
            return;
        }

        // Buscar rutas con parámetros
        foreach ($this->routes as $pattern => $handler) {
            if ($this->matchRoute($pattern, $request, $params)) {
                $this->executeRoute($handler, $params);
                return;
            }
        }

        // Ruta no encontrada
        $this->show404();
    }

    private function matchRoute($pattern, $request, &$params = []) {
        // Convertir patrón a regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $request, $matches)) {
            array_shift($matches); // Remover match completo
            $params = $matches;
            return true;
        }

        return false;
    }

    private function executeRoute($handler, $params = []) {
        if (is_string($handler)) {
            // Es una vista directa
            $this->renderView($handler, $params);
        } elseif (is_array($handler)) {
            // Es controlador y método
            [$controller, $method] = $handler;
            
            if (class_exists($controller)) {
                $instance = new $controller();
                if (method_exists($instance, $method)) {
                    call_user_func_array([$instance, $method], $params);
                } else {
                    $this->show404();
                }
            } else {
                $this->show404();
            }
        } elseif (is_callable($handler)) {
            // Es una función
            call_user_func_array($handler, $params);
        }
    }

    private function renderView($view, $params = []) {
        $viewFile = SRC_PATH . '/views/pages/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            // Extraer parámetros como variables
            extract($params);
            
            // Incluir el archivo de vista
            include $viewFile;
        } else {
            $this->show404();
        }
    }

    private function show404() {
        http_response_code(404);
        include SRC_PATH . '/views/templates/404.php';
    }

    public function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}