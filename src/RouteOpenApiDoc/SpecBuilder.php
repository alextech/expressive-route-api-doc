<?php
namespace RouteOpenApiDoc;


use RouteOpenApiDoc\RouterStrategy\RouterStrategyInterface;

class SpecBuilder
{
    /**
     * @var RouterStrategyInterface
     */
    private $routerStrategy;

    private $resources = [];

    private $newResources = [];

    /**
     * OpenApiWriter constructor.
     * @param RouterStrategyInterface $param
     */
    public function __construct(RouterStrategyInterface $param)
    {
        $this->routerStrategy = $param;
    }

    public function generateSpec(\Zend\Expressive\Application $app) : array
    {
        return [
            'openapi' => '3.0.2',
            'info'=> [
                'version' => '1.0.0',
                'title' => 'Swagger OpenApi Skeleton',
                'license' => [
                    'name' => '',
                ]
            ],
            'servers' => [
                [
                    'url' => ''
                ]
            ],
            'paths' => $this->getApiPaths($app),
            'components' => [
                'schemas' => $this->getSchemas(),
            ]
        ];
    }

    /**
     * @param \Zend\Expressive\Application $app
     *
     * @return array
     */
    private function getApiPaths(\Zend\Expressive\Application $app): array
    {
        $routes = $app->getRoutes();

        $paths = [];
        foreach ($routes as $route) {

            $openApiPath = new OpenApiPath(
                $this->routerStrategy->applyOpenApiPlaceholders($route)
            );

            foreach ($route->getAllowedMethods() as $method) {
                $method = strtolower($method);


                $methodApi = [
                    'summary' => $this->getSummary($openApiPath, $method),
                    'operationId' => $this->generateOperationId($openApiPath, $method),
                    'tags' => [
                        strtolower($openApiPath->getRelatedCollection()),
                    ],
                ];

                $parameters = $this->getParametersForPath($openApiPath, $method);
                if (count($parameters) > 0) {
                    $methodApi['parameters'] = $parameters;
                }

                $requestBody = $this->suggestRequestBody($openApiPath, $method);
                if (count($requestBody) > 0) {
                    $methodApi['requestBody'] = $requestBody;
                }

                if (! $openApiPath->isCollection()) {
                    $this->resources[] = $openApiPath->getSchemaName();
                }

                $methodApi['responses'] = $this->suggestResponses($openApiPath, $method);

                $paths[(string)$openApiPath][$method] = $methodApi;
            }

        }
        return $paths;
    }

    private function getSummary(OpenApiPath $apiPath, string $method) : string
    {
        switch ($method) {
            case 'get' :

                return ($apiPath->isCollection() ? 'List all ' : 'Info for a specific ')
                    . strtolower($apiPath->getSchemaName());

            case 'post' :

                return 'Add a new ' . strtolower($apiPath->getRelatedResource()) . ' to the collection';
            default:

            return '';
        }
    }

    private function generateOperationId(OpenApiPath $path, string $method) : string
    {
        switch ($method) {
            case 'get':
                if ($path->isCollection()) {
                    return 'list' . $path->getSchemaName();
                } else {
                    $params = $path->getParameters();
                    return 'show' . $path->getSchemaName() . 'By'.ucfirst(end($params));
                }

            case 'post':

                return 'add' . $path->getRelatedResource();
            default:

                return '';
        }
    }

    public function getParametersForPath(OpenApiPath $path, string $method = 'get') : array
    {
        $routeParameters = $path->getParameters();

        $parameters = [];

        foreach ($routeParameters as $parameter) {
            $parameters[] = [
                'name' => $parameter,
                'in'=> 'path',
                'required' => true,
                'description' => 'The '.$parameter.' of the '.strtolower($path->getSchemaName()).' to retrieve',
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        if ($method === 'get' && $path->isCollection()) {
            $parameters[] = [
                'name'=> 'limit',
                'in'=> 'query',
                'description'=> 'How many items to return at one time (max 100)',
                'required'=> false,
                'schema'=> [
                    'type'=> 'integer',
                    'format'=> 'int32'
                ]
            ];
        }

        return $parameters;
    }

    private function suggestRequestBody(OpenApiPath $path, string $method) : array
    {

        switch ($method) {
            case 'get' :

                return [];
            case 'post':

                $this->newResources[] = 'New'.$path->getRelatedResource();
                return [
                    'description' => $path->getRelatedResource() . ' to add to the collection',
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/New'.$path->getRelatedResource(),
                            ],
                        ],
                    ],
                ];
            case 'put':

                return [
                    'description' => $path->getRelatedResource() . ' to update',
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$path->getRelatedResource(),
                            ],
                        ],
                    ],
                ];
            default:

                return [];
        }
    }

    public function suggestResponses(OpenApiPath $path, string $method) : array
    {
        switch ($method) {
            case 'get':
                if ($path->isCollection()) {
                    return [
                        200 =>
                            [
                                'description' => 'Array of ' . strtolower($path->getSchemaName()),
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => [
                                                '$ref' => '#/components/schemas/' . $path->getRelatedResource(),
                                            ],

                                        ],
                                    ],
                                ],
                            ],
                    ];
                } else {
                    return [
                        200 =>
                            [
                                'description' => 'Info for a specific ' . strtolower($path->getSchemaName()),
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/' . $path->getSchemaName(),
                                        ],
                                    ],
                                ],
                            ],

                        404 =>
                            [
                                'description' => 'Not found',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/Error'
                                        ],
                                    ],
                                ],
                            ],
                    ];
                }
            case 'post':

                return [
                    201 =>
                        [
                            'description' => 'Null response',
                        ]
                ];

            case 'put':

                return [
                    201 =>
                        [
                            'description' => $path->getRelatedResource().' replacement update accepted',
                        ],
                    400 =>
                        [
                            'description' => 'Invalid ID supplied',
                        ],
                    404 =>
                        [
                            'description' => $path->getRelatedResource().' not found',
                        ],
                    405 =>
                        [
                            'description' => 'Validation exception',
                        ],
                ];
            default:

                return [];
        }
    }

    private function getSchemas() : array
    {
        $schemas = [];

        foreach ($this->resources as $resource) {
            $schemas[$resource] = [
                'required' => [
                    'id',
                    'name'
                ],
                'properties' => [
                    'id' => [
                        'type' => 'string',
                        'format' => 'uuid',
                    ],
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ];
        }

        foreach ($this->newResources as $resource) {
            $schemas[$resource] = [
                'required' => [
                    'name'
                ],
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ];
        }

        $schemas['Error'] = [
            'required'=> [
                'code',
                'message'
            ],
            'properties'=> [
                'code'=> [
                'type'=> 'integer',
                    'format'=> 'int32'
                ],
                'message'=> [
                    'type'=> 'string'
                ],
            ],
        ];

        return $schemas;
    }
}