<?php

namespace RouteApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteApiDoc\SpecBuilder;
use RouteApiDoc\RouterStrategy\ZendRouterStrategy;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteCollector;
use Zend\Expressive\Router\RouterInterface;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\Stratigility\MiddlewarePipeInterface;

class SpecBuilderTest extends TestCase
{
    public function testCreateSpecFromRoutes() : void
    {

        $factory = $this->prophesize(MiddlewareFactory::class);
        $pipeline = $this->prophesize(MiddlewarePipeInterface::class);
        $router = $this->prophesize(RouterInterface::class);
        $runner = $this->prophesize(RequestHandlerRunner::class);

        $factory
            ->prepare([])
            ->willReturn($this->createMockMiddleware());

        $app = new Application(
            $factory->reveal(),
            $pipeline->reveal(),
            new RouteCollector($router->reveal()),
            $runner->reveal()
        );

        $app->get('/pets', []);
        $app->post('/pets', []);
        $app->route('/pets/:petId', [], ['GET']);

        $specWriter = new SpecBuilder(new ZendRouterStrategy());
        $spec = $specWriter->generateSpec($app);
        $spec = json_encode($spec);

        self::assertJsonStringEqualsJsonFile(__DIR__.'/expectedSpec.json', $spec);

    }

    public function createMockMiddleware()
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }

    /**
     * @dataProvider routeDataProvider
     */
    public function testPathParametersForRoute(string $path, int $paramCount, array $paramNames) : void
    {
        $route = new Route($path, $this->createMockMiddleware());
        $specBuilder = new SpecBuilder(new ZendRouterStrategy());

        $parameters = $specBuilder->getParametersForRoute($route);

        self::assertCount($paramCount, $parameters);

        self::assertEquals($paramNames, array_column($parameters, 'name'));

        foreach ($parameters as $parameter) {
            // path parameters are quired as opposed to query parameters.
            self::assertEquals($parameter['required'], true);
        }
    }

    public function routeDataProvider() : array
    {
        return [
            ['/pets',        0,  []  ],
            ['/pets/:petId', 1, ['petId']],
        ];
    }
}
