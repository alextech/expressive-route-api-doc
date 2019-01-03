<?php

namespace RouteOpenApiDoc;

use Doctrine\Common\Inflector;

class OpenApiPath
{
    /**
     * @var string
     */
    private $path;

    private $cached = false;

    private $schemaName;

    private $relatedCollection;

    /** @var bool */
    private $isCollection;

    private $relatedResource;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    private function doPathAnalysis() : void
    {
        $path = $this->path;

        // if path ends in }, it is probably for a parameterized single entity
        // if not, it is a collection
        if (substr($path, strlen($path) - 1) === '}') {
            $resourceEnd = strrpos($path, '{') - 2;
            $resourceStart = strrpos($path, '/', -(strlen($path) - $resourceEnd))+1;
            $resource = substr($path, $resourceStart, $resourceEnd + 1 - $resourceStart);

            $this->relatedCollection = $resource = Inflector\Inflector::classify($resource);

            $resource = Inflector\Inflector::singularize($resource);

            $this->isCollection = false;
        } else {
            $resource = substr($path, strrpos($path, '/') + 1);

            $this->relatedCollection = $resource = Inflector\Inflector::classify($resource);

            $this->relatedResource = Inflector\Inflector::singularize($resource);

            $this->isCollection = true;
        }

        $this->schemaName = $resource;

        $this->cached = true;
    }

    public function getSchemaName() : string
    {
        if (! $this->cached) {
            $this->doPathAnalysis();
        }

        return $this->schemaName;
    }

    public function getParameters() : array
    {
        preg_match_all('$(?<=/\{)([^}]*)$', $this->path, $paramMatches);

        return $paramMatches[0];
    }

    public function isCollection() : bool
    {
        if (! $this->cached) {
            $this->doPathAnalysis();
        }

        return $this->isCollection;
    }

    public function getRelatedCollection() : string
    {
        if (! $this->cached) {
            $this->doPathAnalysis();
        }

        return $this->relatedCollection;
    }

    public function getRelatedResource() : string
    {
        if (! $this->cached) {
            $this->doPathAnalysis();
        }

        return $this->relatedResource;
    }

    public function __toString() : string
    {
        return $this->path;
    }
}
