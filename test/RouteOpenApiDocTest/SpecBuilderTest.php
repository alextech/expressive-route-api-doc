<?php

namespace RouteOpenApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteOpenApiDoc\OpenApiPath;
use RouteOpenApiDoc\SpecBuilder;
use RouteOpenApiDoc\RouterStrategy\ZendRouterStrategy;
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
        $app->put('/pets/:petId', []);
        $app->get('/no_single', []);
        $app->route('/pets', [], ['OPTIONS']);

        $specWriter = new SpecBuilder(new ZendRouterStrategy());
        $spec = $specWriter->generateSpec($app);
        $spec = json_encode($spec);

        self::assertJsonStringEqualsJsonFile(__DIR__ . '/expectedCombinedSpec.json', $spec);

    }

    public function createMockMiddleware()
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }

//    /**
//     * @dataProvider pathDataProvider
//     *
//     * @param string $path
//     * @param array  $routeParams
//     * @param array  $queryParams
//     */
//    public function testPathParametersForRoute(string $path, array $routeParams, array $queryParams) : void
//    {
//        $routerStrategy = new ZendRouterStrategy();
//
//        $route = new OpenApiPath(
//            $routerStrategy->applyOpenApiPlaceholders(
//                new Route($path, $this->createMockMiddleware())
//            )
//        );
//        $specBuilder = new SpecBuilder($routerStrategy);
//
//        $parameters = $specBuilder->getParametersForPath($route);
//
//        self::assertCount(count($routeParams) + count($queryParams),
//           $parameters);
//
//        $offset = 0;
//        for ($i = 0, $iMax = count($routeParams); $i < $iMax; $i++) {
//            self::assertEquals($routeParams[$i], $parameters[$i]['name']);
//            self::assertTrue($parameters[$i]['required']);
//        }
//
//        $offset = $i;
//        for ($i = 0, $iMax = count($queryParams); $i < $iMax; $i++) {
//            self::assertEquals($queryParams[$i], $parameters[$i + $offset]['name']);
//            self::assertFalse($parameters[$i + $offset]['required']);
//        }
//    }

//    /** @dataProvider pathMethodDataProvider
//     * @param string      $path
//     * @param string      $method
//     * @param int         $code
//     * @param string|null $schemaNameSuffix
//     */
//    public function testSuggestResponseForMethodAndRoute(
//        string $path,
//        string $method,
//        int $code,
//        ?string $schemaNameSuffix
//    ) : void
//    {
//        $routerStrategy = new ZendRouterStrategy();
//        $specBuilder = new SpecBuilder($routerStrategy);
//
//        $responses = $specBuilder->suggestResponses(
//            new OpenApiPath($routerStrategy->applyOpenApiPlaceholders(
//                new Route($path, $this->createMockMiddleware())
//            )),
//            $method
//        );
//
//        self::assertArrayHasKey($code, $responses);
//    }

    public function pathDataProvider() : array
    {
        return [
            ['/pets',        [],        ['limit']],
            ['/pets/:petId', ['petId'], []],
        ];
    }

    public function pathMethodDataProvider() : array
    {
        return [
            ['/pets', 'get',  200, 'Pets'],
            ['/pets/{petId}', 'get',  200, 'Pet'],
            ['/pets/{petId}/toys', 'get',  200, 'Toys'],
            ['/pets/{petId}/toys/{toyId}', 'get',  200, 'Toy'],
            ['/pets', 'post', 201, null],
            ['/pets', 'put', 201, null],
        ];
    }
}
