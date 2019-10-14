# Mere Router

## Install with Composer
This must be in your composer.json:
```json
"require": {
    "petrorud/mererouter": "dev-master"
},
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/RudovskiyPO/mererouter"
    }
]
```

## Simple sample
```php
require $pathToComposerAutoload; // require 'vendor/autoload.php';
use petrorud\Router as Router;

$router = new Router(
    [
        '^' => [
            '/$' => [
                'GET' => function($params) {
                    echo "Home Page";
                    $configs = $params['configs']; // This way you can get access to configs you passed as second parameter to Router constructor
                    print_r($configs);
                },
            ],
            '/entity-page' => [
                '/$' => [
                    'GET' => function($params) {
                        echo "Entities index page";
                    },
                ],
                '/{Id}' => [
                    '/$' => [
                        'GET' => function($params) {
                            echo "One entity page";
                            $attrs = $params['attrs']; // This way you can get access to attributes such as GET params and values parsed from URI path
                            print_r($attrs);
                        },
                    ],
                ],
            ],
            '/api' => [
                '/entities' => [
                    '/$' => [
                        'GET' => function($params) {
                            $data = []; // Requested entities data
                            header('Content-Type: application/json');
                            echo json_encode($data);
                        },
                        'POST' => function($params) {
                            $newEntityData = json_decode($_POST['new-entity-data'] ?? [], true);
                            // Create new entity
                        },
                    ],
                    '/entity' => [
                        '/{Id}' => [
                            '/$' => [
                                'GET' => function($params) {
                                    $entityId = $params['attrs']['Id'];
                                    $data = []; // Requested entity[id=$entityId] data
                                    header('Content-Type: application/json');
                                    echo json_encode($data);
                                },
                                'POST' => function($params) {
                                    $entityId = $params['attrs']['Id'];
                                    $entityData = json_decode($_POST['entity-data'] ?? [], true);
                                    // Update existing entity[id=$entityId]
                                },
                                'DELETE' => function($params) {
                                    $entityId = $params['attrs']['Id'];
                                    // Delete entity[id=$entityId]
                                },
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        // Configs that you wants to transfer to controllers (closures)
        'config-key-1' => 'config-value-1',
    ]
);

// You can specify functions you need to call before routing
$router->runMiddlewares([
    function($params) {
        echo "Middleware 1 <br>";
    }
]);

// You can also specify action for "Not Found 404" case
$router->run([
    'not_found' => function($params) {
        http_response_code(404);
    }
]);
```
