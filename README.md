# Mere Router

## Install with Composer
Just run:
```
composer require petrorud/mererouter
```

Or add to your composer.json:
```json
"require": {
    "petrorud/mererouter": "^2.0"
}
```

## Simple sample
```php
require $pathToComposerAutoload; // require 'vendor/autoload.php';
use petrorud\Router as Router;

// Configs that you wants to transfer to controllers (closures)
Router::setConfigs([
    'config-key-1' => 'config-value-1',
]);

// Register routes
Router::registerRoute('^/landing-page-1/$', function () {
    echo "Landing Page №1";

});
Router::registerRoute('^/page-{PageNumber}/$', function ($params) {
    $attrs = $params['attrs']; // This way you can get access to attributes such as GET params and values parsed from URI path
    echo "Page №{$attrs['PageNumber']}";

});

// Also you can register routes as tree if you want to see routes structure more clearly
Router::setRoutesTree([
    '^' => [
        '/$' => [
            ':' => [
                Router::action(function ($params) {
                    echo "Home Page";
                    $configs = $params['configs']; // This way you can get access to configs you passed to Router::setConfigs method
                    print_r($configs);

                }),
            ],
        ],
        '/entity-page' => [
            '/$' => [
                ':' => [
                    Router::action(function ($params) {
                        echo "Entities index page";

                    }),
                ],
            ],
            '/{Id}/$' => [
                ':' => [
                    Router::action(function ($params) {
                        echo "One entity page";
                        $attrs = $params['attrs']; // This way you can get access to attributes such as GET params and values parsed from URI path
                        print_r($attrs);

                    }),
                ],
            ],
        ],
        '/api' => [
            '/entities' => [
                '/$' => [
                    ':' => [
                        Router::action(function ($params) {
                            $data = []; // Requested entities data
                            header('Content-Type: application/json');
                            echo json_encode($data);

                        }),
                        Router::action(function ($params) {
                            $newEntityData = json_decode($_POST['new-entity-data'] ?? [], true);
                            // Create new entity

                        }, 'POST'),
                    ],
                ],
                '/entity' => [
                    '/{Id}' => [
                        '/$' => [
                            ':' => [
                                Router::action(function ($params) {
                                    $entityId = $params['attrs']['Id'];
                                    $data = []; // Requested entity[id=$entityId] data
                                    header('Content-Type: application/json');
                                    echo json_encode($data);

                                }),
                                Router::action(function ($params) {
                                    $entityId = $params['attrs']['Id'];
                                    $entityData = json_decode($_POST['entity-data'] ?? [], true);
                                    // Update existing entity[id=$entityId]

                                }, ['POST', 'PUT']),
                                Router::action(function ($params) {
                                    $entityId = $params['attrs']['Id'];
                                    // Delete entity[id=$entityId]

                                }, 'DELETE'),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);

// You can specify functions you need to call before routing
Router::runMiddlewares([
    function($params) {
        echo "Middleware 1 <br>";
    }
]);

// You can also set some options
Router::run([
    // specify action for "Not Found 404" case ('not_found')
    'not_found' => function($params) {
        http_response_code(404);
    },
    // manage routes sorting by your own ('sort')
    'sort' => function ($a, $b) {
        return strlen($b['regex']) <=> strlen($a['regex']);
    },
]);
```
