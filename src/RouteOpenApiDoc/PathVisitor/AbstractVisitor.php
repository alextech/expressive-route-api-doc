<?php

namespace RouteOpenApiDoc\PathVisitor;

use RouteOpenApiDoc\OpenApiPath;

abstract class AbstractVisitor implements PathVisitorInterface
{

    protected $newResources = [];

    public function getParameters(OpenApiPath $path): array
    {
        $routeParameters = $path->getParameters();

        $parameters = [];

        foreach ($routeParameters as $parameter) {
            $parameters[] = [
                'name' => $parameter,
                'in'=> 'path',
                'required' => true,
                'description' => 'The '.$parameter.' of the '.strtolower($path->getSchemaName()).' to retrieve',
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        return $parameters;
    }

    public function getNewResources() : array
    {
        return $this->newResources;
    }
}
