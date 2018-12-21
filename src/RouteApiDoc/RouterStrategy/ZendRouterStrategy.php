<?php

namespace RouteApiDoc\RouterStrategy;

class ZendRouterStrategy
{

    public function extractRoutesWithParameters(
        \Zend\Expressive\Application $app
    ) : array
    {
        $routes = $app->getRoutes();

        $documentedRoutes = [];

        /** @var \Zend\Expressive\Router\Route $route */
        foreach ($routes as $route) {
            $path = $route->getPath();
            $result = preg_match_all('$(?<=/:)([^/]*)$', $path, $paramMatches);
            $documentedRoutes[$path] = $paramMatches[0];
        }

        return $documentedRoutes;
    }
}
