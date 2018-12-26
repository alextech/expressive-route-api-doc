<?php
namespace RouteApiDoc;


use RouteApiDoc\RouterStrategy\ZendRouterStrategy;
use Zend\Expressive\Router\Route;

use Doctrine\Common\Inflector;

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
        $routes = $app->getRoutes();

        $paths = [];
        foreach ($routes as $route) {
            foreach ($route->getAllowedMethods() as $method) {
                $method = strtolower($method);

                $openApiPath = $this->routerStrategy->applyOpenApiPlaceholders($route);
                $paths[$openApiPath][$method] = [
                    'summary' => '',
                    'operationId' => '',
                    'tags' => [
                        '',
                    ],

                    'parameters' => $this->getParametersForRoute($route),
                    'responses' => $this->suggestResponses($openApiPath, $method),
                ];
            }
        }

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
            'paths' => $paths,
            'components' => [
                'schemas' => $this->getSchemas(),
            ]
        ];
    }

    public function getParametersForRoute(Route $route) : array
    {
        $routeParameters = $this->routerStrategy->extractParameters($route);

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

        return $parameters;
    }

    public function suggestResponses(string $path, string $method) : array
    {
        switch ($method) {
            case 'get':
                $code = 200;
                $response = [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/' . $this->getSchemaNameFromPath($path),
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

    private function getSchemaNameFromPath($path) : string
    {
        // if path ends in }, it is probably for a parameterized single entity
        // if not, it is a collection
        if (substr($path, strlen($path) - 1) === '}') {
            $resourceEnd = strrpos($path, '{') - 2;
            $resourceStart = strrpos($path, '/', -(strlen($path) - $resourceEnd))+1;
            $resource = substr($path, $resourceStart, $resourceEnd + 1 - $resourceStart);

            $potentialCollection = $resource = Inflector\Inflector::classify($resource);

            $resource = Inflector\Inflector::singularize($resource);

            $this->potentialCollections[$potentialCollection] = $resource;
        } else {
            $resource = substr($path, strrpos($path, '/') + 1);

            $resource = Inflector\Inflector::classify($resource);
        }

        $this->resources[] = $resource;

        return $resource;
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
                        'id' => 'string', // uuid
                        'name' => 'string'
                    ]
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

        return $schemas;
    }
}