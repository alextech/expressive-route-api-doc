<?php

namespace RouteOpenApiDoc\PathVisitor;

use RouteOpenApiDoc\OpenApiPath;
use RouteOpenApiDoc\Resource;

abstract class AbstractVisitor implements PathVisitorInterface
{

    /** @var Resource[] */
    protected $resources = [];

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

    /**
     * @return Resource[]
     */
    public function getResources() : array
    {
        return $this->resources;
    }
}
