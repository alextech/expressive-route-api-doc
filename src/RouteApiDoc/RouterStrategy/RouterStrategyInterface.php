<?php

namespace RouteApiDoc\RouterStrategy;

use Zend\Expressive\Router\Route;

interface RouterStrategyInterface
{
    /**
     * @param Route $route
     * @return string
     */
    public function applyOpenApiPlaceholders(Route $route): string;
}