<?php
namespace RouteOpenApiDoc;


use RouteOpenApiDoc\PathVisitor\DeleteVisitor;
use RouteOpenApiDoc\PathVisitor\GetVisitor;
use RouteOpenApiDoc\PathVisitor\PathVisitorInterface;
use RouteOpenApiDoc\PathVisitor\PostVisitor;
use RouteOpenApiDoc\PathVisitor\PutVisitor;
use RouteOpenApiDoc\RouterStrategy\RouterStrategyInterface;
use Zend\Expressive\Application;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteCollector;

class SpecBuilder
{
    /**
     * @var RouterStrategyInterface
     */
    private $routerStrategy;

    /** @var Resource[] */
    private $resources = [];

    private $visitors;

    /** @var Route[][] */
    private $routes = ['/' => []];


    /**
     * OpenApiWriter constructor.
     * @param RouterStrategyInterface $param
     */
    public function __construct(RouterStrategyInterface $param)
    {
        $this->routerStrategy = $param;

        $this->visitors = [
            'get' => GetVisitor::class,
            'post' => PostVisitor::class,
            'put' => PutVisitor::class,
            'delete' => DeleteVisitor::class,
        ];
    }

    public function addApplication(Application $app, string $basePath = '') : void
    {
        $this->addRoutes($app->getRoutes(), $basePath);
    }

    public function addRouteCollector(RouteCollector $routeCollector, string $basePath = '') : void
    {
        $this->addRoutes($routeCollector->getRoutes(), $basePath);
    }

    private function addRoutes(array $routes, string $basePath) : void
    {
        if (! array_key_exists($basePath, $this->routes)) {
            $this->routes[$basePath] = [];
        }
        $this->routes[$basePath] = array_merge($this->routes[$basePath], $routes);
    }

    public function generateSpec() : array
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
            'paths' => $this->getApiPaths(),
            'components' => [
                'schemas' => $this->getSchemas(),
            ]
        ];
    }

    public function setHttpMethodVisitor(string $method, string $strategy) : void
    {
        $method = strtolower($method);
        $this->visitors[$method] = $strategy;
    }

    /**
     * @param \Zend\Expressive\Application $app
     *
     * @return array
     */
    private function getApiPaths(): array
    {
        $paths = [];
        foreach ($this->routes as $basePath => $routes) {
            foreach ($routes as $route) {

                $openApiPath = new OpenApiPath(
                    $basePath . $this->routerStrategy->applyOpenApiPlaceholders($route)
                );

                foreach ($route->getAllowedMethods() as $method) {
                    $method = strtolower($method);

                    if (! array_key_exists($method, $this->visitors)) {
                        continue;
                    }

                    /** @var PathVisitorInterface $visitor */
                    $visitor = new $this->visitors[$method];


                    $methodApi = [
                        'summary' => $visitor->getSummary($openApiPath),
                        'operationId' => $visitor->generateOperationId($openApiPath),
                        'tags' => [
                            strtolower($openApiPath->getTag()),
                        ],
                    ];

                    $parameters = $visitor->getParameters($openApiPath);
                    if (count($parameters) > 0) {
                        $methodApi['parameters'] = $parameters;
                    }

                    $requestBody = $visitor->suggestRequestBody($openApiPath);
                    if (count($requestBody) > 0) {
                        $methodApi['requestBody'] = $requestBody;
                    }

                    $methodApi['responses'] = $visitor->suggestResponses($openApiPath);

                    $paths[(string)$openApiPath][$method] = $methodApi;

                    $this->resources = array_merge($this->resources, $visitor->getResources());
                }

            }
        }
        return $paths;
    }

    private function getSchemas() : array
    {
        $schemas = [];

        foreach ($this->resources as $resource) {
            $schemas[$resource->getName()] = $resource->getSchemaTemplate();
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