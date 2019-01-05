<?php

namespace RouteOpenApiDoc;

class Resource
{
    private $name;
    private $isNew;

    /**
     * Resource constructor.
     * @param string $name
     * @param bool $isNew If schema is used to create new resource, or output existing
     */
    public function __construct(string $name, bool $isNew)
    {
        $this->name = $name;
        $this->isNew = $isNew;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function isNew() : bool
    {
        return $this->isNew;
    }

    public function getSchemaTemplate() : array
    {
        if ($this->isNew) {
            return $this->getAsNew();
        } else {
            return $this->getAsExisting();
        }
    }

    private function getAsExisting() : array
    {
        return
        [
            'required' => [
                'id',
                'name'
            ],
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'name' => [
                    'type' => 'string',
                ],
            ],
        ];
    }

    private function getAsNew() : array
    {
        return
            [
                'required' => [
                    'name'
                ],
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ];
    }
}
