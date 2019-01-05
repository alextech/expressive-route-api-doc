<?php

namespace RouteOpenApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteOpenApiDoc\OpenApiPath;
use RouteOpenApiDoc\PathVisitor\PathVisitorInterface;
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

        $app = $this->createApp();

        $app->get('/pets', []);
        $app->post('/pets', []);
        $app->route('/pets/:petId', [], ['GET']);
        $app->put('/pets/:petId', []);
        $app->get('/no_single', []);
        $app->delete('/pets/:petId', []);
        $app->route('/pets', [], ['OPTIONS']); // to be ignored

        $specBuilder = new SpecBuilder(new ZendRouterStrategy());
        $spec = $specBuilder->generateSpec($app);
        $spec = json_encode($spec);

        self::assertJsonStringEqualsJsonFile(__DIR__ . '/expectedCombinedSpec.json', $spec);

    }

    public function testSetHttpMethodVisitor() : void
    {
        $specBuilder = new SpecBuilder(new ZendRouterStrategy());
        $specBuilder->setHttpMethodVisitor('mockVerb', StubPathVisitor::class);

        $app = $this->createApp();
        $app->route('/path', [], ['mockVerb']);

        $spec = $specBuilder->generateSpec($app);

        self::assertArrayHasKey('mockverb', $spec['paths']['/path']);

        return;
    }

    public function createMockMiddleware()
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }

    /**
     * @return Application
     */
    private function createApp(): Application
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
        return $app;
    }

}

class StubPathVisitor implements PathVisitorInterface
{

    public function getSummary(OpenApiPath $path): string
    {
        return '';
    }

    public function generateOperationId(OpenApiPath $path): string
    {
        return '';
    }

    public function getParameters(OpenApiPath $path): array
    {
        return [];
    }

    public function suggestRequestBody(OpenApiPath $path): array
    {
        return [];
    }

    public function suggestResponses(OpenApiPath $path): array
    {
        return [];
    }

    public function getNewResources(): array
    {
        return [];
    }

}
