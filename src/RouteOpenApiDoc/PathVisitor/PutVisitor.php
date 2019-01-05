<?php

namespace RouteOpenApiDoc\PathVisitor;

use RouteOpenApiDoc\OpenApiPath;

class PutVisitor extends AbstractVisitor implements PathVisitorInterface
{
    public function getSummary(OpenApiPath $path): string
    {
        return 'Update specified ' . $path->getSchemaName();
    }

    public function generateOperationId(OpenApiPath $path): string
    {
        return 'update' . $path->getSchemaName();
    }

    public function getParameters(OpenApiPath $path): array
    {
        $parameters = parent::getParameters($path);

        if (! $path->isCollection()) {
            $lastParameterIdx = count($parameters) - 1;
            $parameter = $parameters[$lastParameterIdx]['name'];
            $parameters[$lastParameterIdx]['description']
                = 'The '.$parameter.' of the '.strtolower($path->getSchemaName()).' to update';
        }

        return $parameters;
    }

    public function suggestRequestBody(OpenApiPath $path): array
    {
        return [
            'description' => $path->getSchemaName() . ' to update',
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/New'.$path->getSchemaName(),
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
                    'description' => $path->getSchemaName().' replacement update accepted',
                ],
            400 =>
                [
                    'description' => 'Invalid ID supplied',
                ],
            404 =>
                [
                    'description' => $path->getSchemaName().' not found',
                ],
            405 =>
                [
                    'description' => 'Validation exception',
                ],
        ];
    }

}
