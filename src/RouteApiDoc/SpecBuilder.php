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

                $openApiPath = $this->routerStrategy->applyOpenApiPlaceholders($route);
                $paths[$openApiPath][strtolower($method)] = [
                    'summary' => '',
                    'operationId' => '',
                    'tags' => [
                        '',
                    ],


                    'parameters' => $this->getParametersForRoute($route),
//                    'responses' => $this->suggestResponses($method, $route),
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
}