<?php

namespace RouteApiDoc\RouterStrategy;

use Zend\Expressive\Router\Route;

class ZendRouterStrategy
{

    /**
     * @param Route $route
     * @return Route[]
     */
    public function extractParameters(Route $route) : array
    {
        preg_match_all('$(?<=/:)([^/]*)$', $route->getPath(), $paramMatches);

        return $paramMatches[0];
    }

    /**
     * @param Route $route
     * @return string
     */
    public function applyOpenApiPlaceholders(Route $route) : string
    {
        $path = $route->getPath();
        $start = 0;
        while (($paramPosition = strpos($path, ':', $start)) !== false) {
            $path = substr_replace($path, '{', $paramPosition, 1);
            $segmentEndPosition = strpos($path, '/', $paramPosition);

            // last parameter in URL
            if ($segmentEndPosition === false) {
                $path .= '}';
            } else {
                $path = substr_replace($path, '}', $segmentEndPosition, 0);
            }

            $start = $paramPosition + 1;
        }

        return $path;
    }
}
