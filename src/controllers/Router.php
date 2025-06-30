<?php
// {"_META_file_path_": "src/controllers/Router.php"}
// Sistema de rutas mejorado

class Router {
    private $routes = [];

    public function __construct() {
        $this->loadRoutes();
    }

    private function loadRoutes() {
        global $routes;
        if (isset($routes) && is_array($routes)) {
            $this->routes = $routes;
        } else {
            throw new Exception("Routes configuration not found");
        }
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
                    throw new Exception("Method {$method} not found in {$controller}");
                }
            } else {
                throw new Exception("Controller {$controller} not found");
            }
        } elseif (is_callable($handler)) {
            // Es una función
            call_user_func_array($handler, $params);
        } else {
            throw new Exception("Invalid route handler");
        }
    }

    private function renderView($view, $params = []) {
        $viewFile = SRC_PATH . '/views/pages/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            // Extraer parámetros como variables
            extract($params);
            
            // Buffer de salida para plantilla base si existe
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            
            // Verificar si hay plantilla base
            $baseTemplate = SRC_PATH . '/views/templates/base.php';
            if (file_exists($baseTemplate)) {
                include $baseTemplate;
            } else {
                echo $content;
            }
        } else {
            throw new Exception("View file not found: {$viewFile}");
        }
    }

    private function show404() {
        http_response_code(404);
        $notFoundFile = SRC_PATH . '/views/templates/404.php';
        
        if (file_exists($notFoundFile)) {
            include $notFoundFile;
        } else {
            echo '<h1>404 - Página no encontrada</h1>';
        }
    }

    public function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}