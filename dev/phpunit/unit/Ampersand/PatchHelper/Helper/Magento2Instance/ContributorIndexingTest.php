<?php

use Ampersand\PatchHelper\Helper\Magento2Instance\GraphQlSchemaDefinitions;
use Ampersand\PatchHelper\Helper\Magento2Instance\WebApiRestDefinitions;

class ContributorIndexingTest extends \PHPUnit\Framework\TestCase
{
    public function testRestIndexRetainsValidFilesOnBothSidesOfMalformedFile()
    {
        $root = BASE_DIR . '/dev/phpunit/unit/resources/checks/WebApiRestXml';
        $first = 'vendor/magento/module-added/etc/webapi.xml';
        $invalid = 'vendor/magento/module-invalid/etc/webapi.xml';
        $last = 'vendor/example/module-third-party-added/etc/webapi.xml';
        $definitions = new WebApiRestDefinitions($root, ["$root/$first", "$root/$invalid", "$root/$last"]);

        $errors = $definitions->prepare();

        $this->assertSame([
            'GET /V1/added-file' => [$first],
            'POST /V1/third-party-added' => [$last]
        ], $definitions->getRouteDefinitions());
        $this->assertCount(1, $errors);
        $this->assertStringContainsString($invalid, $errors[0]->getMessage());
        $this->assertStringContainsString('Unable to parse XML', $errors[0]->getMessage());
        $this->assertInstanceOf(\InvalidArgumentException::class, $errors[0]->getPrevious());
    }

    public function testGraphQlIndexRetainsValidFilesOnBothSidesOfMalformedFile()
    {
        $root = BASE_DIR . '/dev/phpunit/unit/resources/checks/GraphQLSchema';
        $first = 'vendor/magento/module-added/etc/schema.graphqls';
        $invalid = 'vendor/magento/module-invalid/etc/schema.graphqls';
        $last = 'vendor/magento/module-partial/etc/schema.graphqls';
        $definitions = new GraphQlSchemaDefinitions($root, ["$root/$first", "$root/$invalid", "$root/$last"]);

        $errors = $definitions->prepare();

        $this->assertSame([
            'Query addedFile' => [$first],
            'Query partial' => [$last]
        ], $definitions->getRootFieldDefinitions());
        $this->assertCount(1, $errors);
        $this->assertStringContainsString($invalid, $errors[0]->getMessage());
        $this->assertStringContainsString('Unable to parse', $errors[0]->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $errors[0]->getPrevious());
    }
}
