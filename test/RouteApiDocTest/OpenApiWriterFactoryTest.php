<?php

namespace RouteApiDocTest;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RouteApiDoc\OpenApiWriter;
use RouteApiDoc\OpenApiWriterFactory;
use RouteApiDoc\RouterStrategy\RouterStrategyInterface;
use RouteApiDoc\RouterStrategy\ZendRouterStrategy;

class OpenApiWriterFactoryTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreateWriterInstance() : void
    {
        $routerStrategy = $this->prophesize(RouterStrategyInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ZendRouterStrategy::class)
            ->willReturn($routerStrategy);
        $container->get('config')
            ->willReturn([
                'openapi_writer' => [
                    'router_strategy' => ZendRouterStrategy::class,
                    'output_directory' => __DIR__.'/out',
                ],
            ]);

        $factory = new OpenApiWriterFactory();
        $writer = $factory($container->reveal(), '');
        self::assertInstanceOf(OpenApiWriter::class, $writer);
    }
}
