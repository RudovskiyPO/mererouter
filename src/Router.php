<?php

namespace RudovskiyPO;

class Router
{
    private $routes;
    private $configs;
    private $request;
    private $response;

    public function __construct($routesTree, array $configs = [])
    {
        $this->configs = $configs;
//        $this->request = new Request();
//        $this->response = new Response();

        if (is_string($routesTree) && file_exists($routesTree)) {
            $routesTree = require_once($routesTree);
        }

        if (!is_array($routesTree)) {
            throw new \Exception(
                'Wrong routes type: ' . gettype($routesTree) . '. Allowed only array or path to file with array.'
            );
        }

        error_log('Router construct');

//        $this->buildRoutes($routesTree);
//        $this->parseRoutes();
    }

//    private function buildRoutes($routesTree)
//    {
//        $br = function ($parentRegex, $branches) use (&$br) {
//            foreach ($branches as $regex => $leaf) {
//                $concatenatedRegex = empty($parentRegex) ? $regex : $parentRegex . $regex;
//
//                if (substr($concatenatedRegex, -1) == '$') {
//                    $this->routes[$concatenatedRegex] = $leaf;
//                } else {
//                    $br($concatenatedRegex, $leaf);
//                }
//            }
//        };
//
//        $br(null, $routesTree);
//    }
//
//    private function parseRoutes()
//    {
//        $attrNamePattern = '[A-Za-z0-9_-]';
//
//        foreach ($this->routes as $pathPattern => $route) {
//            $regex = $pathPattern;
//            $attrs = [];
//
//            if (strpos($pathPattern, '{')) {
//                preg_match_all('~{(\w+)}~', $pathPattern, $matchedAttrs, PREG_SET_ORDER);
//
//                foreach ($matchedAttrs as $attr) {
//                    $regex = str_replace($attr[0], "($attrNamePattern+)", $regex);
//                    $attrs[] = $attr[1];
//                }
//            }
//
//            $this->routes[$pathPattern]['regex'] = $regex;
//            $this->routes[$pathPattern]['attrs'] = $attrs;
//        }
//    }
//
//    public function run()
//    {
//        $uri = $this->request->getURI();
//        $notFound = new NotFound($this->request, $this->response, [
//            'Routes' => $this->routes,
//            'Attrs' => $uri->getParamsMap(),
//        ]);
//
//        foreach ($this->routes as $route) {
//            if (preg_match("~{$route['regex']}~", $uri->getPath(), $matches)) {
//                // Get leaf data
//                $requestMethod = $this->request->method();
//                $leaf = $route[$requestMethod] ?? null;
//                if (empty($leaf)) {
//                    continue;
//                }
//
//                // Get params
//                array_shift($matches);
//                $attrs = array_combine($route['attrs'], $matches);
//                $params = $uri->getParamsMap() + $attrs;
//
//                // Init controller
//                $controllerName = $leaf['controller'];
//                if (!class_exists($controllerName)) {
//                    throw new ClassNotFound($controllerName);
//                }
//
//                $controller = new $controllerName($this->request, $this->response, [
//                    'Routes' => $this->routes,
//                    'Attrs' => $params,
//                ]);
//                if (!($controller instanceof Controller)) {
//                    throw new NotInstance($controller, 'Controller');
//                }
//
//                // Run controller
//                $controllerMethodName = $leaf['action'];
//                try {
//                    if (!method_exists($controller, $controllerMethodName)) {
//                        throw new MethodNotDefined($controllerMethodName, $controllerName);
//                    }
//
//                    call_user_func(array($controller, $controllerMethodName), $params);
//                } catch (NotFoundX $e) {
//                    $errors = $e->getX()['Errors'];
//
//                    if (!isset($errors['Path'])) {
//                        $errors['Path'] = $uri->getPath();
//                    }
//
//                    $notFound->index($errors);
//                }
//
//                return;
//            }
//        }
//
//        $notFound->index(['Path' => $uri->getPath()]);
//    }
//
//    public function runMiddlewares(array $middlewares)
//    {
//        foreach ($middlewares as $middlewareName) {
//            if (!class_exists($middlewareName)) {
//                throw new \Exception("Class '$middlewareName' not found. Maybe this class was not included.");
//            }
//
//            $middleware = new $middlewareName($this->configs);
//            if (!($middleware instanceof Middleware)) {
//                throw new \Exception("Class '$middlewareName' not found. Maybe this class was not included.");
//                throw new NotInstance($middleware, 'Middleware');
//            }
//
//            if (!method_exists($middleware, 'run')) {
//                throw new MethodNotDefined('run', $middlewareName);
//            }
//
//            call_user_func(array($middleware, 'run'), $this->request, $this->response);
//        }
//    }
//
//    public static function makeLeaf($controller, $action)
//    {
//        return [
//            'controller' => $controller,
//            'action' => $action
//        ];
//    }
}
