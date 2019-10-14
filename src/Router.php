<?php

namespace petrorud;

class Router
{
    private $routes;
    private $routesTree;
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
        $this->routesTree = $routesTree;

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

    public function run($options = [])
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $path = self::getPath();

        foreach ($this->routes as $route) {
            if (preg_match("~{$route['regex']}~", $path, $matches)) {
                $callable = $route[$requestMethod] ?? null;
                if (empty($callable)) {
                    continue;
                }

                array_shift($matches);
                $attrs = array_combine($route['attrs'], $matches);
                $params = $_GET + $attrs;

                call_user_func($callable, [
                    'current_route_regex' => $route['regex'],
                    'attrs' => $params,
                    'configs' => $this->configs,
                ]);

                return;
            }
        }

        if (isset($options['not_found']) && is_callable($options['not_found'])) {
            call_user_func($options['not_found'], [
                'attrs' => $_GET,
                'configs' => $this->configs,
            ]);
        } else {
            http_response_code(404);
            echo "<h1>404. Not Found</h1>";
        }
    }

    public function runMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $callable) {
            call_user_func($callable, [
                'attrs' => $_GET,
                'configs' => $this->configs,
            ]);
        }
    }

    public static function getPath():string
    {
        return parse_url($_SERVER['REQUEST_URI'])['path'] ?? '';
    }
}
