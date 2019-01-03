<?php

namespace RouteOpenApiDoc;

use RouteOpenApiDoc\RouterStrategy\ZendRouterStrategy;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'invokables' => [
                ZendRouterStrategy::class,
            ],
            'factories' => [
                OpenApiWriter::class => OpenApiWriterFactory::class,
            ],
        ];
    }
}
