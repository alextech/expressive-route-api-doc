<?php

namespace RouteApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteOpenApiDoc\OpenApiWriter;
use RouteOpenApiDoc\RouterStrategy\ZendRouterStrategy;
use RouteOpenApiDoc\SpecBuilder;
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

    /**
     * @throws \Exception
     */
    public function testSpecAllSchemasOneFile() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->addApplication($this->app);
        $writer->writeSpec();

        self::assertFileExists($this->docFile);
    }

    /**
     * @throws \Exception
     */
    public function testKeepPropertyChanges() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->addApplication($this->app);
        $writer->writeSpec();

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

        $writer->writeSpec();

        self::assertJsonStringEqualsJsonFile($this->docFile, $modifiedSpec);
    }

    /**
     * @throws \Exception
     */
    public function testMerge() : void
    {
        // setup
        $specBuilder = new SpecBuilder(new ZendRouterStrategy());
        $specBuilder->addApplication($this->app);
        $specArray = $specBuilder->generateSpec();

        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        // emulate rename/remove properties in document editing
        $specArray['components']['schemas']['Resource']['required'][0] = 'resource_id';
        unset($specArray['components']['schemas']['Resource']['properties']['id']);
        $specArray['components']['schemas']['Resource']['properties']['resource_id'] = ['type'=>'string'];

        $modifiedSpec = json_encode($specArray,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        file_put_contents($this->docFile, $modifiedSpec);

        // execute
        $this->app->put('/api/pets', []);

        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->addApplication($this->app);
        $writer->writeSpec(true);

        $specBuilder->addApplication($this->app);
        $specArray = $specBuilder->generateSpec();
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['summary'] = 'path get modified by test runner';
        $specArray['paths']['/api/resources/{resource_id}']
            ['get']['responses']
                [200]['description'] = 'Response description modified by test runner';

        $specArray['components']['schemas']['Resource']['required'][0] = 'resource_id';
        unset($specArray['components']['schemas']['Resource']['properties']['id']);
        $specArray['components']['schemas']['Resource']['properties']['resource_id'] = ['type'=>'string'];

        // verify // expected vs actual had to be intentionally reversed
        self::assertJsonStringEqualsJsonFile($this->docFile, json_encode($specArray, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @throws \Exception
     */
    public function testSchemaIndividualFiles() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->addApplication($this->app);
        $writer->writeSpec(false);

        self::assertFileExists($this->docFile);
        self::assertFileExists($this->testSpecDir.'/resource.json');
        self::assertJsonFileEqualsJsonFile(__DIR__ . '/expectedSeparateSchemaSpec.json', $this->docFile);
        self::assertJsonFileEqualsJsonFile(__DIR__ . '/expectedSeparateResourceSchema.json', $this->testSpecDir.'/resource.json');
    }

    /**
     * @throws \Exception
     */
    public function testMergeLeavesModifiedSchemaFiles() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->addApplication($this->app);
        $writer->writeSpec();


        $schemaArray = json_decode(file_get_contents($this->testSpecDir . '/resource.json'), true);
        $schemaArray['Resource']['required'][0] = 'resource_id';
        unset($schemaArray['Resource']['properties']['id']);
        $schemaArray['Resource']['properties']['resource_id'] = ['type' => 'string'];

        $modifiedSchema = json_encode($schemaArray,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        file_put_contents($this->testSpecDir . '/resource.json', $modifiedSchema);

        // execute
        $writer->writeSpec();

        // verify
        self::assertJsonStringEqualsJsonFile($this->testSpecDir . '/resource.json', $modifiedSchema);
    }

    /**
     * @throws \Exception
     */
    public function testThrowExceptionNoDirectorySet() : void
    {
        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $this->expectException(\Exception::class);

        $writer->addApplication($this->app);
        $writer->writeSpec();
    }

    /**
     * @throws \Exception
     */
    public function testMultipleRoutes() : void
    {
        $routes = new RouteCollector($this->prophesize(RouterInterface::class)->reveal());
        $routes->get('/second_router', $this->createMockMiddleware());

        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->addApplication($this->app);
        $writer->addRouteCollector($routes);
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->writeSpec();

        $spec = json_decode(file_get_contents($this->testSpecDir . '/'.self::SPEC_FILE_NAME), true);
        self::assertArrayHasKey('/api/resources/{resource_id}', $spec['paths']);
        self::assertArrayHasKey('/second_router', $spec['paths']);
    }

    /**
     * @throws \Exception
     */
    public function testBasePath() : void
    {
        $routes = new RouteCollector($this->prophesize(RouterInterface::class)->reveal());
        $routes->get('/second_router', $this->createMockMiddleware());

        $writer = new OpenApiWriter(new ZendRouterStrategy());
        $writer->addApplication($this->app, '/basePath');
        $writer->addRouteCollector($routes, '/basePath');
        $writer->setOutputDirectory($this->testSpecDir);
        $writer->writeSpec();

        $spec = json_decode(file_get_contents($this->testSpecDir . '/'.self::SPEC_FILE_NAME), true);
        self::assertArrayHasKey('/basePath/api/resources/{resource_id}', $spec['paths']);
        self::assertArrayNotHasKey('/api/resources/{resource_id}', $spec['paths']);
    }

    public function createMockMiddleware() : MiddlewareInterface
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }
}
