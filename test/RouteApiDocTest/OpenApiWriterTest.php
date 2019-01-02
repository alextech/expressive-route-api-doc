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

    private $testSpecDir = __DIR__.'/out';

    private $docFile;

    private const SPEC_FILE_NAME = 'api_doc.json';

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


        $this->docFile = $this->testSpecDir.'/'.self::SPEC_FILE_NAME;

        $this->app = $app;
    }

    public function tearDown()
    {
        array_map('unlink', glob($this->testSpecDir.'/*.json'));
    }

    public function testSpecAllSchemasOneFile() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToDirectory($this->app, $this->testSpecDir);

        self::assertFileExists($this->docFile);
    }

    public function testKeepPropertyChanges() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToDirectory($this->app, $this->testSpecDir);

        $specArray = json_decode(file_get_contents($this->docFile), true);

        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        $modifiedSpec = json_encode($specArray,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        file_put_contents($this->docFile, $modifiedSpec);

        $writer->writeSpecToDirectory($this->app, $this->testSpecDir);

        self::assertJsonStringEqualsJsonFile($this->docFile, $modifiedSpec);
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

        file_put_contents($this->docFile, $modifiedSpec);

        // execute
        $this->app->put('/api/pets', []);

        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToDirectory($this->app, $this->testSpecDir);

        $specBuilder = new SpecBuilder(new ZendRouterStrategy());
        $specArray = $specBuilder->generateSpec($this->app);
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        // verify
        self::assertJsonStringEqualsJsonFile($this->docFile, json_encode($specArray, JSON_UNESCAPED_SLASHES));
    }

    public function testSchemaIndividualFiles() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->writeSpecToDirectory($this->app, $this->testSpecDir, false);

        self::assertFileExists($this->docFile);
        self::assertFileExists($this->testSpecDir.'/resource.json');
        self::assertJsonFileEqualsJsonFile(__DIR__ . '/expectedSeparateSchemaSpec.json', $this->docFile);
        self::assertJsonFileEqualsJsonFile(__DIR__ . '/expectedSeparateResourceSchema.json', $this->testSpecDir.'/resource.json');
    }

    public function createMockMiddleware() : MiddlewareInterface
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }
}
