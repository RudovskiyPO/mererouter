<?php

namespace petrorud;

class Router
{
    public static $ATTR_NAME_PATTERN = '[A-Za-z0-9_-]';
    public static $routes = [];
    public static $routesTree = [];
    private static $configs = [];

    public static function setConfigs(array $configs)
    {
        self::$configs = $configs;
    }

    public static function setRoutesTree($tree)
    {
        if (is_string($tree) && file_exists($tree)) {
            $tree = require_once($tree);
        }

        if (!is_array($tree)) {
            throw new \Exception(
                'Wrong routes type: ' . gettype($tree) . '. Allowed only array or path to file with array.'
            );
        }
        self::$routesTree = $tree;

        self::buildRoutes(self::$routesTree);
    }

    private static function buildRoutes($routesTree)
    {
        $br = function ($parentRegex, $branches) use (&$br) {
            foreach ($branches as $regex => $leaf) {
                $concatenatedRegex = empty($parentRegex) ? $regex : $parentRegex . $regex;

                if (count($leaf) === 1 && in_array(':', array_keys($leaf))) {
                    self::$routes[$concatenatedRegex] = [];
                    foreach ($leaf[':'] as $action) {
                        self::addActionToRoutes($concatenatedRegex, $action);
                    }
                } else {
                    $br($concatenatedRegex, $leaf);
                }
            }
        };

        $br(null, $routesTree);
    }

    public static function registerRoute(string $regex, callable $callable, $methods = 'GET')
    {
        self::addActionToRoutes($regex, self::action($callable, $methods));
    }

    private static function parseRoutes()
    {
        $attrNamePattern = self::$ATTR_NAME_PATTERN;

        foreach (self::$routes as $pathPattern => $route) {
            $regex = $pathPattern;

            if (strpos($pathPattern, '{')) {
                preg_match_all('~{(\w+)}~', $pathPattern, $matchedAttrs, PREG_SET_ORDER);
                foreach ($matchedAttrs as $attr) {
                    $regex = str_replace($attr[0], "(?P<{$attr[1]}>{$attrNamePattern}+)", $regex);
                }
            }

            self::$routes[$pathPattern]['regex'] = $regex;
        }

        uasort(self::$routes, function ($a, $b) {
            if (empty($a['attrs']) && !empty($b['attrs'])) {
                return -1;
            } elseif (empty($b['attrs']) && !empty($a['attrs'])) {
                return 1;
            }
            return 0;
        });
    }

    public static function run($options = [])
    {
        self::parseRoutes();

        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $path = self::getPath();

        foreach (self::$routes as $route) {
            if (preg_match("~{$route['regex']}~", $path, $matches)) {
                $callable = $route[$requestMethod] ?? null;
                if (empty($callable)) {
                    continue;
                }

                $attrs = array_filter($matches, "is_string", ARRAY_FILTER_USE_KEY);
                $params = $_GET + $attrs;

                return call_user_func($callable, [
                    'current_route_regex' => $route['regex'],
                    'attrs' => $params,
                    'configs' => self::$configs,
                ]);
            }
        }

        if (isset($options['not_found']) && is_callable($options['not_found'])) {
            return call_user_func($options['not_found'], [
                'attrs' => $_GET,
                'configs' => self::$configs,
            ]);
        } else {
            http_response_code(404);
            echo "<h1>404. Not Found</h1>";
        }

        return false;
    }

    public static function runMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $callable) {
            call_user_func($callable, [
                'attrs' => $_GET,
                'configs' => self::$configs,
            ]);
        }
    }

    public static function getPath() :string
    {
        return parse_url($_SERVER['REQUEST_URI'])['path'] ?? '';
    }

    public static function action(callable $callable, $methods = 'GET'): array
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        return [
            'callable' => $callable,
            'methods' => $methods,
        ];
    }

    private static function addActionToRoutes(string $regex, $action)
    {
        if (self::validateAction($action)) {
            foreach ($action['methods'] as $method) {
                $method = strtoupper($method);
                if (isset(self::$routes[$regex][$method])) {
                    throw new \Exception("Route [$method $regex] already registered");
                }
                self::$routes[$regex][$method] = $action['callable'];
            }
        }
    }

    private static function validateAction($action): bool
    {
        return !empty($action['methods']) && is_array($action['methods'])
            && !empty($action['callable']) && is_callable($action['callable']);
    }
}
