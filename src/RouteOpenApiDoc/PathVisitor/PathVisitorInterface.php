<?php

namespace RouteOpenApiDoc\PathVisitor;

use RouteOpenApiDoc\OpenApiPath;

interface PathVisitorInterface
{
    public function getSummary(OpenApiPath $path) : string;
    public function generateOperationId(OpenApiPath $path) : string;

    public function getParameters(OpenApiPath $path) : array;
    public function suggestRequestBody(OpenApiPath $path) : array;
    public function suggestResponses(OpenApiPath $path) : array;

    public function getNewResources() : array;
}
