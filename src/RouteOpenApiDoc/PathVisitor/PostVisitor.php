<?php

namespace RouteOpenApiDoc\PathVisitor;

use RouteOpenApiDoc\OpenApiPath;
use RouteOpenApiDoc\Resource;

class PostVisitor extends AbstractVisitor implements PathVisitorInterface
{
    public function getSummary(OpenApiPath $path): string
    {
        return 'Add a new ' . strtolower($path->getRelatedResource()) . ' to the collection';
    }

    public function generateOperationId(OpenApiPath $path): string
    {
        return 'add' . $path->getRelatedResource();
    }

    public function suggestRequestBody(OpenApiPath $path): array
    {
        $this->resources[] = new Resource('New'.$path->getRelatedResource(), true);

        return [
            'description' => $path->getRelatedResource() . ' to add to the collection',
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/New'.$path->getRelatedResource(),
                    ],
                ],
            ],
        ];
    }

    public function suggestResponses(OpenApiPath $path): array
    {
        return [
            201 =>
                [
                    'description' => 'Null response',
                ]
        ];
    }

}
