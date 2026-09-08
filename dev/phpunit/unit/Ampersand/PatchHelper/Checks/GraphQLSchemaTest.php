<?php

use Ampersand\PatchHelper\Checks\GraphQLSchema;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;

class GraphQLSchemaTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/GraphQLSchema/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    protected function setUp(): void
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testQueriesAndMutationsAreAddedRemovedAndChanged()
    {
        $this->assertSame(
            [
                'GraphQL Mutation changed' => [
                    'Mutation changedMutation' => 'Mutation changedMutation'
                ],
                'GraphQL Mutation removed' => [
                    'Mutation removedMutation' => 'Mutation removedMutation'
                ],
                'GraphQL Query changed' => [
                    'Query changed' => 'Query changed'
                ],
                'GraphQL Query removed' => [
                    'Query removed' => 'Query removed'
                ],
                'GraphQL Mutation added' => [
                    'Mutation addedMutation' => 'Mutation addedMutation'
                ],
                'GraphQL Query added' => [
                    'Query added' => 'Query added',
                    'Query extended' => 'Query extended'
                ]
            ],
            $this->checkEntry('module-sample')
        );
    }

    public function testSupersededRootDeclarationsDoNotContributeFields()
    {
        $this->assertSame([], $this->checkEntry('module-superseded-root'));
        $this->assertSame(
            [
                'GraphQL Query changed' => [
                    'Query retained' => 'Query retained'
                ]
            ],
            $this->checkEntry('module-retained-root')
        );
    }

    public function testReachableSupportingContractsChangeTheirRootField()
    {
        $this->assertSame(
            [
                'GraphQL Query changed' => [
                    'Query products' => 'Query products'
                ]
            ],
            $this->checkEntry('module-reachable')
        );
    }

    public function testImplementedInterfaceChangesItsRootField()
    {
        $this->assertSame(
            [
                'GraphQL Query changed' => [
                    'Query products' => 'Query products'
                ]
            ],
            $this->checkEntry('module-interface-reachable')
        );
    }

    public function testFormattingDescriptionsAndUnrelatedTypesDoNotChangeRootFields()
    {
        $this->assertSame([], $this->checkEntry('module-normalized'));
    }

    public function testPartialAndAbsentRootDeclarationsAreValid()
    {
        $this->assertSame(
            [
                'GraphQL Query added' => [
                    'Query partial' => 'Query partial'
                ]
            ],
            $this->checkEntry('module-partial')
        );
        $this->assertSame([], $this->checkEntry('module-without-roots'));
    }

    public function testAddedAndRemovedSchemaFilesReportEveryRootField()
    {
        $this->assertSame(
            [
                'GraphQL Query added' => [
                    'Query addedFile' => 'Query addedFile'
                ]
            ],
            $this->checkEntry('module-added')
        );
        $this->assertSame(
            [
                'GraphQL Mutation removed' => [
                    'Mutation removedFile' => 'Mutation removedFile'
                ]
            ],
            $this->checkEntry('module-removed')
        );
    }

    public function testMalformedSchemaHasContextualError()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-invalid/etc/schema.graphqls',
            'vendor_orig/magento/module-invalid/etc/schema.graphqls'
        );
        $warnings = $infos = $ignored = [];
        $check = new GraphQLSchema($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('etc/schema.graphqls not parseable: Unable to parse');
        $check->check();
    }

    public function testNonSchemaPathCannotBeChecked()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-sample/etc/config.graphqls',
            'vendor_orig/magento/module-sample/etc/config.graphqls'
        );
        $warnings = $infos = $ignored = [];
        $check = new GraphQLSchema($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertFalse($check->canCheck());
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function checkEntry(string $module)
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/' . $module . '/etc/schema.graphqls',
            'vendor_orig/magento/' . $module . '/etc/schema.graphqls'
        );
        $warnings = $infos = $ignored = [];
        $check = new GraphQLSchema($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        return $infos;
    }
}
