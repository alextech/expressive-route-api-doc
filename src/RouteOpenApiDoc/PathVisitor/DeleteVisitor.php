<?php

namespace RouteOpenApiDoc\PathVisitor;

use RouteOpenApiDoc\OpenApiPath;

class DeleteVisitor extends AbstractVisitor implements PathVisitorInterface
{
    public function getSummary(OpenApiPath $path): string
    {
        return 'Delete specified ' . $path->getSchemaName();
    }

    public function generateOperationId(OpenApiPath $path): string
    {
        return 'delete' . $path->getSchemaName();
    }

    public function getParameters(OpenApiPath $path): array
    {
        $parameters = parent::getParameters($path);

        if (! $path->isCollection()) {
            $lastParameterIdx = count($parameters) - 1;
            $parameter = $parameters[$lastParameterIdx]['name'];
            $parameters[$lastParameterIdx]['description']
                = 'The '.$parameter.' of the '.strtolower($path->getSchemaName()).' to delete';
        }

        return $parameters;
    }

    public function suggestRequestBody(OpenApiPath $path): array
    {
        return [];
    }

    public function suggestResponses(OpenApiPath $path): array
    {
        return [
            200 =>
                [
                    'description' => $path->getSchemaName().' deleted successfully',
                ],
            400 =>
                [
                    'description' => 'Invalid ID supplied',
                ],
            404 =>
                [
                    'description' => $path->getSchemaName().' not found',
                ],
        ];
    }
}
