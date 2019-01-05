<?php

namespace RouteOpenApiDoc\PathVisitor;


use RouteOpenApiDoc\OpenApiPath;

class GetVisitor extends AbstractVisitor implements PathVisitorInterface
{
    public function getSummary(OpenApiPath $path): string
    {
        return ($path->isCollection() ? 'List all ' : 'Info for a specific ')
            . strtolower($path->getSchemaName());
    }

    public function generateOperationId(OpenApiPath $path): string
    {
        if ($path->isCollection()) {
            return 'list' . $path->getSchemaName();
        } else {
            $params = $path->getParameters();
            return 'show' . $path->getSchemaName() . 'By'.ucfirst(end($params));
        }
    }

    public function getParameters(OpenApiPath $path): array
    {
        $parameters = parent::getParameters($path);
        if ($path->isCollection()) {
            $parameters[] = [
                'name'=> 'limit',
                'in'=> 'query',
                'description'=> 'How many items to return at one time (max 100)',
                'required'=> false,
                'schema'=> [
                    'type'=> 'integer',
                    'format'=> 'int32'
                ]
            ];
        }

        return $parameters;
    }

    public function suggestRequestBody(OpenApiPath $path): array
    {
        return [];
    }

    public function suggestResponses(OpenApiPath $path): array
    {
        if ($path->isCollection()) {
            return [
                200 =>
                    [
                        'description' => 'Array of ' . strtolower($path->getSchemaName()),
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/components/schemas/' . $path->getRelatedResource(),
                                    ],

                                ],
                            ],
                        ],
                    ],
            ];
        } else {
            return [
                200 =>
                    [
                        'description' => 'Info for a specific ' . strtolower($path->getSchemaName()),
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/' . $path->getSchemaName(),
                                ],
                            ],
                        ],
                    ],

                404 =>
                    [
                        'description' => 'Not found',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Error'
                                ],
                            ],
                        ],
                    ],
            ];
        }
    }
}