<?php

namespace RouteOpenApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteOpenApiDoc\SpecBuilder;
use RouteOpenApiDoc\RouterStrategy\ZendRouterStrategy;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;
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
        $app->delete('/pets/:petId', []);
        $app->route('/pets', [], ['OPTIONS']); // to be ignored

        $specWriter = new SpecBuilder(new ZendRouterStrategy());
        $spec = $specWriter->generateSpec($app);
        $spec = json_encode($spec);

        self::assertJsonStringEqualsJsonFile(__DIR__ . '/expectedCombinedSpec.json', $spec);

    }

    public function createMockMiddleware()
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }

}
