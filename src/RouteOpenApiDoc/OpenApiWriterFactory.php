<?php

namespace RouteOpenApiDoc;

use Psr\Container\ContainerInterface;

class OpenApiWriterFactory
{
    /**
     * @param ContainerInterface $container
     * @param $name
     * @return OpenApiWriter
     * @throws \Exception
     */
    public function __invoke(ContainerInterface $container, $name) : OpenApiWriter
    {
        $config = $container->get('config');

        if (! isset($config['openapi_writer']) || ! is_array($config['openapi_writer'])) {
            throw new \Exception('Could not create OpenApiWriter: Missing "openapi_writer" configuration key');
        }

        $config = $config['openapi_writer'];

        if (! isset($config['router_strategy'])) {
            throw new \Exception('Could not create OpenApiWriter: Missing "router_strategy configuration key for "openapi_writer"');
        }

        $openApiWriter = new OpenApiWriter($container->get($config['router_strategy']));
        $openApiWriter->setOutputDirectory($config['output_directory']);

        return $openApiWriter;
    }
}
