# Mere Router

## Install with Composer
This must be in your composer.json:
```json
"require": {
    "rudovskiypo/mererouter": "dev-master"
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
require $pathToComposerAutoload;
use RudovskiyPO\Router as Router;

// Controller class should inherit \RudovskiyPO\Controller
$router = new Router(
    [
        '^' => [
            '/$' => [
                'GET' => Router::makeLeaf(\Controllers\Home::class, 'index'),
            ],
            '/entity-page' => [
                '/$' => [
                    'GET' => Router::makeLeaf(\Controllers\Entity::class, 'index'),
                ],
                '/{Id}' => [
                    '/$' => [
                        'GET' => Router::makeLeaf(\Controllers\Entity::class, 'show'),
                    ],
                ],
            ],
            '/api' => [
                '/entities' => [
                    '/$' => [
                        'GET' => Router::makeLeaf(\Controllers\Entity::class, 'index'),
                        'POST' => Router::makeLeaf(\Controllers\Entity::class, 'create'),
                    ],
                    '/entity' => [
                        '/{Id}' => [
                            '/$' => [
                                'GET' => Router::makeLeaf(\Controllers\Entity::class, 'show'),
                                'POST' => Router::makeLeaf(\Controllers\Entity::class, 'update'),
                                'DELETE' => Router::makeLeaf(\Controllers\Entity::class, 'delete'),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        // Configs that customer wants to transfer to controller
    ]
);

// Middleware class must inherit \RudovskiyPO\Middleware
$router->runMiddlewares([
    \Middlewares\Middleware1::class,
    \Middlewares\Middleware2::class,
]);

$router->run([
    'NotFound' => function($params) {
        http_response_code(404);
    }
]);
```
