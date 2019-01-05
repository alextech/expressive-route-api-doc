<?php
namespace RouteOpenApiDoc;


use RouteOpenApiDoc\PathVisitor\DeleteVisitor;
use RouteOpenApiDoc\PathVisitor\GetVisitor;
use RouteOpenApiDoc\PathVisitor\PathVisitorInterface;
use RouteOpenApiDoc\PathVisitor\PostVisitor;
use RouteOpenApiDoc\PathVisitor\PutVisitor;
use RouteOpenApiDoc\RouterStrategy\RouterStrategyInterface;

class SpecBuilder
{
    /**
     * @var RouterStrategyInterface
     */
    private $routerStrategy;

    /** @var Resource[] */
    private $resources = [];

    private $visitors;

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

                if (! array_key_exists($method, $this->visitors)) {
                    continue;
                }

                /** @var PathVisitorInterface $visitor */
                $visitor = new $this->visitors[$method];


                $methodApi = [
                    'summary' => $visitor->getSummary($openApiPath),
                    'operationId' => $visitor->generateOperationId($openApiPath),
                    'tags' => [
                        strtolower($openApiPath->getRelatedCollection()),
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