<?php
namespace RouteApiDoc;


use RouteApiDoc\RouterStrategy\ZendRouterStrategy;
use Zend\Expressive\Router\Route;

class SpecBuilder
{
    /**
     * @var ZendRouterStrategy
     */
    private $routerStrategy;

    private $resources = [];

    private $potentialCollections = [];

    /**
     * OpenApiWriter constructor.
     * @param ZendRouterStrategy $param
     */
    public function __construct(ZendRouterStrategy $param)
    {
        $this->routerStrategy = $param;
    }

    public function generateSpec(\Zend\Expressive\Application $app) : array
    {
        return [
            'openapi' => '3.0.2',
            'info'=> [
                'version' => '1.0.0',
                'title' => '',
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
                    'summary' => '',
                    'operationId' => '',
                    'tags' => [
                        '',
                    ],
                ];

                $parameters = $this->getParametersForPath($openApiPath);
                if (count($parameters) > 0) {
                    $methodApi['parameters']
                        = $this->getParametersForPath($openApiPath);
                }

                $methodApi['responses'] = $this->suggestResponses($openApiPath, $method);

                $paths[(string)$openApiPath][$method] = $methodApi;
            }

            $this->resources[] = $openApiPath->getSchemaName();

            if (! $openApiPath->isCollection()) {
                $this->potentialCollections[$openApiPath->getRelatedCollection()] = $openApiPath->getSchemaName();
            }
        }
        return $paths;
    }

    public function getParametersForPath(OpenApiPath $path) : array
    {
        $routeParameters = $path->getParameters();

        $parameters = [];

        foreach ($routeParameters as $parameter) {
            $parameters[] = [
                'name' => $parameter,
                'in'=> 'path',
                'required' => true,
                'description' => '',
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        if ($path->isCollection()) {
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

    public function suggestResponses(OpenApiPath $path, string $method) : array
    {
        switch ($method) {
            case 'get':
                $code = 200;
                $response = [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/' . $path->getSchemaName(),
                            ],
                        ],
                    ],
                ];

                break;
            case 'post':
                $code = 201;
                $response = [
                    'description' => 'Null response',
                ];

                break;
            default:

                $code = 'default';
                $response = [
                    'application/json' => [
                        'schema' => [
                            '$ref' => ''
                        ],
                    ],
                ];
        }

        return [
            $code => $response,
        ];
    }

    private function getSchemas() : array
    {
        $schemas = [];

        $collections = [];

        foreach ($this->resources as $resource) {

            if (array_key_exists($resource, $this->potentialCollections)) {
                // do collections last
                $collections[] = $resource;
            } else {
                $schemas[$resource] = [
                    'required' => [
                        'id', 'name'
                    ],
                    'properties' => [
                        'id' => [
                            'type' => 'string', // uuid
                        ],
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ];
            }
        }

        foreach ($collections as $collection) {
            $schemas[$collection] = [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/components/schemas/' . $this->potentialCollections[$collection],
                ]
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