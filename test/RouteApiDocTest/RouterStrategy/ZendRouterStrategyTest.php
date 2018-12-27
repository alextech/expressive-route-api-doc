<?php

namespace RouteApiDocTest\RouterStrategy;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RouteApiDoc\RouterStrategy\ZendRouterStrategy;
use Zend\Expressive\Router\Route;

class ZendRouterStrategyTest extends TestCase
{
    public function testExtractRoutesWithParameters() : void
    {
        $routerStrategy = new ZendRouterStrategy();

        $route = new Route('/resource/:resource_id', $this->createMockMiddleware());
        $parameters = $routerStrategy->extractParameters($route);
        self::assertEquals(['resource_id'], $parameters);

        $route = new Route('/resource/:resource_id/sub_resource/:sub_id', $this->createMockMiddleware());
        $parameters = $routerStrategy->extractParameters($route);
        self::assertEquals(['resource_id', 'sub_id'], $parameters);
    }

    public function testApplyOpenApiPlaceholders() : void {
        $routerStrategy = new ZendRouterStrategy();

        $route = new Route('/resource/:resource_id', $this->createMockMiddleware());
        $path = $routerStrategy->applyOpenApiPlaceholders($route);
        self::assertEquals('/resource/{resource_id}', $path);

        $route = new Route('/resource/:resource_id/sub_resource/:sub_id', $this->createMockMiddleware());
        $path = $routerStrategy->applyOpenApiPlaceholders($route);
        self::assertEquals('/resource/{resource_id}/sub_resource/{sub_id}', $path);

    }

    public function createMockMiddleware()
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }
}
