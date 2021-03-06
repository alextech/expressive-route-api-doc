<?php

namespace RouteOpenApiDocTest;

use PHPUnit\Framework\TestCase;
use RouteOpenApiDoc\OpenApiPath;

class OpenApiPathTest extends TestCase
{

    /** @dataProvider pathDataProvider
     * @param string $routePath
     * @param string $resourceName
     */
    public function testGetSchema(string $routePath, string $resourceName) : void
    {
        $path = new OpenApiPath($routePath);

        self::assertEquals($resourceName, $path->getSchemaName());
    }

    /**
     * @dataProvider pathDataProvider
     * @param string $routePath
     * @param string $resourceName
     * @param array  $parameters
     */
    public function testExtractRoutesWithParameters(string $routePath, string $resourceName, array $parameters) : void
    {
        $path = new OpenApiPath($routePath);

        self::assertEquals($parameters, $path->getParameters());
    }

    /**
     * @dataProvider pathDataProvider
     *
     * @param string $routePath
     * @param string $resourceName
     * @param array  $parameters
     * @param bool   $isCollection
     */
    public function testIsCollection(string $routePath, string $resourceName, array $parameters, bool $isCollection) : void
    {
        $path = new OpenApiPath($routePath);

        self::assertEquals($isCollection, $path->isCollection());
    }

    /**
     * @dataProvider pathDataProvider
     * @param $routePath
     */
    public function testGetTag($routePath) : void
    {
        $path = new OpenApiPath($routePath);

        self::assertEquals('pets', $path->getTag());
    }

    /**
     * @dataProvider pathRelationsDataProvider
     * @param string $routePath
     * @param string $collection
     * @param string $resource
     */
    public function testGetRelatedCollection(string $routePath, string $collection, string $resource) : void
    {
        $path = new OpenApiPath($routePath);

        self::assertEquals($collection, $path->getRelatedCollection());
    }

    /**
     * @dataProvider pathRelationsDataProvider
     * @param string $routePath
     * @param string $collection
     * @param string $resource
     */
    public function testGetRelatedResource(string $routePath, string $collection, string $resource) : void
    {
        $path = new OpenApiPath($routePath);

        self::assertEquals($resource, $path->getRelatedResource());
    }

    public function pathDataProvider() : array
    {
        return [
            ['/pets', 'Pets', [], true],
            ['/pets/{petId}', 'Pet', ['petId'], false],
            ['/pets/{petId}/toys', 'Toys', ['petId'], true],
            ['/pets/{petId}/toys/{toyId}', 'Toy', ['petId', 'toyId'], false],
        ];
    }

    public function pathRelationsDataProvider() : array
    {
        return [
            ['/pets', 'Pets', 'Pet'],
            ['/pets/{petId}', 'Pets', 'Pet'],
            ['/pets/{petId}/toys', 'Toys', 'Toy'],
            ['/pets/{petId}/toys/{toyId}', 'Toys', 'Toy'],
        ];
    }
}
