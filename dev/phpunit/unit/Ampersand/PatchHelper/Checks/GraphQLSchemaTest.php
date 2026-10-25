<?php

use Ampersand\PatchHelper\Checks\GraphQLSchema;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;

class GraphQLSchemaTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/GraphQLSchema/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    /** @var array<string, array<int, string>> */
    private $rootFieldDefinitions = [];

    protected function setUp(): void
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->m2->method('getGraphQlRootFieldDefinitions')
            ->willReturnCallback(function () {
                return $this->rootFieldDefinitions;
            });
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

    public function testNullValuesAreComparedWithoutWarningsUnderStrictErrorHandling()
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });
        try {
            $this->assertSame([
                'GraphQL Query changed' => [
                    'Query defaultValue' => 'Query defaultValue',
                    'Query directiveValue' => 'Query directiveValue',
                    'Query nestedValues' => 'Query nestedValues'
                ]
            ], $this->checkEntry('module-null-values'));
        } finally {
            restore_error_handler();
        }
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

    public function testThirdPartyQueryAddedRootFieldIsPromotedToWarning()
    {
        $this->assertThirdPartyRootFieldIsPromoted('Query added', 'GraphQL Query added');
    }

    public function testThirdPartyQueryChangedRootFieldIsPromotedToWarning()
    {
        $this->assertThirdPartyRootFieldIsPromoted('Query changed', 'GraphQL Query changed');
    }

    public function testThirdPartyQueryRemovedRootFieldIsPromotedToWarning()
    {
        $this->assertThirdPartyRootFieldIsPromoted('Query removed', 'GraphQL Query removed', true);
    }

    public function testThirdPartyMutationAddedRootFieldIsPromotedToWarning()
    {
        $this->assertThirdPartyRootFieldIsPromoted('Mutation addedMutation', 'GraphQL Mutation added');
    }

    public function testThirdPartyMutationChangedRootFieldIsPromotedToWarning()
    {
        $this->assertThirdPartyRootFieldIsPromoted('Mutation changedMutation', 'GraphQL Mutation changed');
    }

    public function testThirdPartyMutationRemovedRootFieldIsPromotedToWarning()
    {
        $this->assertThirdPartyRootFieldIsPromoted('Mutation removedMutation', 'GraphQL Mutation removed', true);
    }

    public function testMagentoQueryAddedRootFieldGetsTargetWarning()
    {
        $this->assertMagentoRootFieldGetsTargetWarning('Query added', 'GraphQL Query added');
    }

    public function testMagentoQueryChangedRootFieldGetsTargetWarning()
    {
        $this->assertMagentoRootFieldGetsTargetWarning('Query changed', 'GraphQL Query changed');
    }

    public function testMagentoQueryRemovedRootFieldGetsTargetWarning()
    {
        $this->assertMagentoRootFieldGetsTargetWarning('Query removed', 'GraphQL Query removed', true);
    }

    public function testMagentoMutationAddedRootFieldGetsTargetWarning()
    {
        $this->assertMagentoRootFieldGetsTargetWarning('Mutation addedMutation', 'GraphQL Mutation added');
    }

    public function testMagentoMutationChangedRootFieldGetsTargetWarning()
    {
        $this->assertMagentoRootFieldGetsTargetWarning('Mutation changedMutation', 'GraphQL Mutation changed');
    }

    public function testMagentoMutationRemovedRootFieldGetsTargetWarning()
    {
        $this->assertMagentoRootFieldGetsTargetWarning('Mutation removedMutation', 'GraphQL Mutation removed', true);
    }

    public function testMagentoOnlyAndDistinctRootFieldsRemainInfo()
    {
        $this->rootFieldDefinitions = [
            'Query changed' => [
                'vendor/magento/module-other/etc/schema.graphqls',
                'vendor/magento/module-sample/etc/schema.graphqls'
            ],
            'Mutation changedMutation' => [
                'vendor/magento/module-other/etc/schema.graphqls',
                'vendor/magento/module-sample/etc/schema.graphqls'
            ],
            'Query different' => ['app/code/Vendor/Module/etc/schema.graphqls'],
            'Mutation differentMutation' => ['app/code/Vendor/Module/etc/schema.graphqls']
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/magento/module-sample/etc/schema.graphqls',
            'vendor_orig/magento/module-sample/etc/schema.graphqls'
        );

        $this->assertSame([], $warnings);
        $this->assertSame('Query changed', $infos['GraphQL Query changed']['Query changed']);
        $this->assertSame(
            'Mutation changedMutation',
            $infos['GraphQL Mutation changed']['Mutation changedMutation']
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

    /**
     * @return array{array<string, array<int, string>>, array<string, array<string, string>>}
     */
    private function runCheck(string $newFile, string $originalFile): array
    {
        $entry = new Entry($this->testResourcesDir, $newFile, $originalFile);
        $warnings = $infos = $ignored = [];
        $check = new GraphQLSchema($this->m2, $entry, '', $warnings, $infos, $ignored);

        $check->check();
        $this->assertSame([], $ignored);

        return [$warnings, $infos];
    }

    private function assertThirdPartyRootFieldIsPromoted(string $identity, string $type, bool $removed = false): void
    {
        $source = 'app/code/Vendor/Module/etc/schema.graphqls';
        $contributor = 'vendor/example/module-contributor/etc/schema.graphqls';
        $this->rootFieldDefinitions = [$identity => $removed ? [$contributor] : [$source, $contributor]];

        [$warnings, $infos] = $this->runCheck($source, 'vendor_orig/' . $source);

        $this->assertSame([$type => ["$contributor ($identity)"]], $warnings);
        $this->assertArrayNotHasKey($identity, $infos[$type] ?? []);
    }

    private function assertMagentoRootFieldGetsTargetWarning(string $identity, string $type, bool $removed = false): void
    {
        $source = 'vendor/magento/module-sample/etc/schema.graphqls';
        $contributor = 'app/code/Vendor/Module/etc/schema.graphqls';
        $this->rootFieldDefinitions = [$identity => $removed ? [$contributor] : [$contributor, $source]];

        [$warnings, $infos] = $this->runCheck($source, 'vendor_orig/magento/module-sample/etc/schema.graphqls');

        $targetType = str_starts_with($identity, 'Query ')
            ? 'GraphQL Query target changed'
            : 'GraphQL Mutation target changed';
        $this->assertSame([$targetType => ["$contributor ($identity)"]], $warnings);
        $this->assertSame($identity, $infos[$type][$identity]);
    }
}
