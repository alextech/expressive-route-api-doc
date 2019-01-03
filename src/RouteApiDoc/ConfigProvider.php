<?php

namespace RouteApiDoc;

use RouteApiDoc\RouterStrategy\ZendRouterStrategy;

class ConfigProvider
{
    public function __invoke() : array
    {
        return $this->getDependencyConfig();
    }

    public function getDependencyConfig() : array
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
