<?php

namespace RouteApiDoc;

use RouteApiDoc\RouterStrategy\RouterStrategyInterface;
use RouteApiDoc\RouterStrategy\ZendRouterStrategy;

class OpenApiWriter
{
    /**
     * @var SpecBuilder
     */
    private $specBuilder;

    private $directory;

    private $docFileName = 'api_doc.json';

    public function __construct(RouterStrategyInterface $routerStrategy)
    {
        $this->specBuilder = new SpecBuilder($routerStrategy);
    }

    // TODO option to write schemas to one file or multiple. One file would not need to write to a directory

    /**
     * @param \Zend\Expressive\Application $app
     * @param bool $singleFile
     * @throws \Exception
     */
    public function writeSpec(
        \Zend\Expressive\Application $app,
        bool $singleFile = true
    ) : void
    {
        if (! isset($this->directory) || $this->directory === null) {
            throw new \Exception('Cannot create OpenApi spec: output directory is not set.');
        }

        $spec = $this->specBuilder->generateSpec($app);

        if ($singleFile) {
            $this->writeDoc($this->directory . $this->docFileName, $spec);
        } else {
            $this->writeDocAndSchemas($this->directory, $spec);
        }
    }

    public function setOutputDirectory(string $directory) : void
    {
        if (substr_compare($directory, '/', strlen($directory) - 1, 1) !== 0) {
            $directory .= '/';
        }

        $this->directory = $directory;
    }

    private function writeDoc(string $file, array $spec) : void
    {

        if (file_exists($file)) {
            $existingSpec = json_decode(file_get_contents($file), true);
            $spec = self::array_merge_recursive_distinct($spec, $existingSpec);
        }

        $result = file_put_contents($file, json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));


        if ($result === false) {
            throw new \RuntimeException('Problem writing route spec to file "'.$file.'" ');
        }
    }

    private function writeDocAndSchemas(string $directory, array $spec) : void
    {
        $schemas = $spec['components']['schemas'];

        foreach ($schemas as $schemaName => $schema) {
            $fileName = strtolower($schemaName) . '.json';

            $spec['components']['schemas'][$schemaName] = [
                '$ref' => $fileName . '#/' . $schemaName,
            ];

            $this->writeDoc($directory . '/' . $fileName, [$schemaName => $schema]);
        }

        $this->writeDoc($directory . $this->docFileName, $spec);
    }

    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    private static function array_merge_recursive_distinct ( array &$array1, array &$array2 )
    {
        $merged = $array1;

        foreach ( $array2 as $key => &$value )
        {
            if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
            {
                $merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value );
            }
            else
            {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }

}
