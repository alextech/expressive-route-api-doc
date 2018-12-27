<?php

namespace RouteApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteApiDoc\OpenApiWriter;
use RouteApiDoc\RouterStrategy\ZendRouterStrategy;
use RouteApiDoc\SpecBuilder;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;
use Zend\Expressive\Router\RouteCollector;
use Zend\Expressive\Router\RouterInterface;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\Stratigility\MiddlewarePipeInterface;

class OpenApiWriterTest extends TestCase
{
    /**
     * @var Application
     */
    private $app;

    private $testSpecFilePath = __DIR__.'/out/spec_test.json';

    public function setUp()
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

        $app->get('/api/resources/:resource_id',[]);


        $this->app = $app;
    }

    public function tearDown()
    {
        unlink($this->testSpecFilePath);
    }

    public function testCreateFile() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToFile($this->app, $this->testSpecFilePath);

        self::assertFileExists($this->testSpecFilePath);
    }

    public function testKeepPropertyChanges() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToFile($this->app, $this->testSpecFilePath);

        $specArray = json_decode(file_get_contents($this->testSpecFilePath), true);

        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        $modifiedSpec = json_encode($specArray,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        file_put_contents($this->testSpecFilePath, $modifiedSpec);

        $writer->writeSpecToFile($this->app, $this->testSpecFilePath);

        self::assertJsonStringEqualsJsonFile($this->testSpecFilePath, $modifiedSpec);
    }

    public function testMerge() : void
    {
        // setup
        $specBuilder = new SpecBuilder(new ZendRouterStrategy());
        $specArray = $specBuilder->generateSpec($this->app);

        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        $modifiedSpec = json_encode($specArray,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        file_put_contents($this->testSpecFilePath, $modifiedSpec);

        // execute
        $this->app->put('/api/pets', []);

        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToFile($this->app, $this->testSpecFilePath);

        $specBuilder = new SpecBuilder(new ZendRouterStrategy());
        $specArray = $specBuilder->generateSpec($this->app);
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        // verify
        self::assertJsonStringEqualsJsonFile($this->testSpecFilePath, json_encode($specArray, JSON_UNESCAPED_SLASHES));
    }

    public function createMockMiddleware() : MiddlewareInterface
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }
}
