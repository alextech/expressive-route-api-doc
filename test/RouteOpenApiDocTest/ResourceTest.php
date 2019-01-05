<?php

namespace RouteOpenApiDocTest;

use PHPUnit\Framework\TestCase;
use RouteOpenApiDoc\Resource;

class ResourceTest extends TestCase
{
    public function testExistingResourceTemplateHasId() : void
    {
        $resource = new Resource('name', false);
        $template = $resource->getSchemaTemplate();
        self::assertContains('id', $template['required']);
        self::assertArrayHasKey('id', $template['properties'], 'id');
    }

    public function testNewResourceTemplateHasNoId() : void
    {
        $resource = new Resource('name', true);
        $template = $resource->getSchemaTemplate();
        self::assertNotContains('id', $template['required']);
        self::assertArrayNotHasKey('id', $template['properties'], 'id');
    }
}
