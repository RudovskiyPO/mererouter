<?php

namespace RudovskiyPO;

class Router
{
    private $routes;
    private $configs;

    public function __construct($routesTree, array $configs = [])
    {
        $this->configs = $configs;

        if (is_string($routesTree) && file_exists($routesTree)) {
            $routesTree = require_once($routesTree);
        }

        if (!is_array($routesTree)) {
            throw new \Exception(
                'Wrong routes type: ' . gettype($routesTree) . '. Allowed only array or path to file with array.'
            );
        }

        $this->buildRoutes($routesTree);
        $this->parseRoutes();
    }

    private function buildRoutes($routesTree)
    {
        $br = function ($parentRegex, $branches) use (&$br) {
            foreach ($branches as $regex => $leaf) {
                $concatenatedRegex = empty($parentRegex) ? $regex : $parentRegex . $regex;

                if (substr($concatenatedRegex, -1) == '$') {
                    $this->routes[$concatenatedRegex] = $leaf;
                } else {
                    $br($concatenatedRegex, $leaf);
                }
            }
        };

        $br(null, $routesTree);
    }

    private function parseRoutes()
    {
        $attrNamePattern = '[A-Za-z0-9_-]';

        foreach ($this->routes as $pathPattern => $route) {
            $regex = $pathPattern;
            $attrs = [];

            if (strpos($pathPattern, '{')) {
                preg_match_all('~{(\w+)}~', $pathPattern, $matchedAttrs, PREG_SET_ORDER);

                foreach ($matchedAttrs as $attr) {
                    $regex = str_replace($attr[0], "($attrNamePattern+)", $regex);
                    $attrs[] = $attr[1];
                }
            }

            $this->routes[$pathPattern]['regex'] = $regex;
            $this->routes[$pathPattern]['attrs'] = $attrs;
        }
    }

    public function run($params = [])
    {
        $uri = new URI();
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if (preg_match("~{$route['regex']}~", $uri->getPath(), $matches)) {
                // Get leaf data
                $leaf = $route[$requestMethod] ?? null;
                if (empty($leaf)) {
                    continue;
                }

                // Get params
                array_shift($matches);
                $attrs = array_combine($route['attrs'], $matches);
                $params = $uri->getParamsMap() + $attrs;

                // Init controller
                $controllerName = $leaf['controller'];
                if (!class_exists($controllerName)) {
                    throw new \Exception("Class '$controllerName' not found. Maybe this class was not included.");
                }

                $controller = new $controllerName([
                    'Routes' => $this->routes,
                    'Attrs' => $params,
                    'Configs' => $this->configs,
                ]);
                if (!$controller instanceof Controller) {
                    throw new \Exception("Class $controllerName is not instance of Controller.");
                }

                // Run controller
                $controllerMethodName = $leaf['action'];
                if (!method_exists($controller, $controllerMethodName)) {
                    throw new \Exception("Method '$controllerMethodName' does not defined in class '$controllerName'.");
                }

                call_user_func(array($controller, $controllerMethodName), $params);
                return;
            }
        }

        if (isset($params['NotFound']) && is_callable($params['NotFound'])) {
            call_user_func($params['NotFound'], [
                'Routes' => $this->routes,
                'Attrs' => $uri->getParamsMap(),
                'Configs' => $this->configs,
            ]);
        } else {
            http_response_code(404);
            echo "<h1>404. Not Found</h1>";
        }
    }

    public function runMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middlewareName) {
            if (!class_exists($middlewareName)) {
                throw new \Exception("Class '$middlewareName' not found. Maybe this class was not included.");
            }

            $middleware = new $middlewareName($this->configs);
            if (!($middleware instanceof Middleware)) {
                throw new \Exception("Class $middlewareName is not instance of Middleware.");
            }

            if (!method_exists($middleware, 'run')) {
                throw new \Exception("Method 'run' does not defined in class '$middlewareName'.");
            }

            call_user_func(array($middleware, 'run'));
        }
    }

    public static function makeLeaf($controller, $action)
    {
        return [
            'controller' => $controller,
            'action' => $action
        ];
    }
}
