<?php

use Ampersand\PatchHelper\Checks\SystemConfigXml;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;
use Ampersand\PatchHelper\Patchfile\Reader;

class SystemConfigXmlTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/SystemConfigXml/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    protected function setUp(): void
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSystemConfigFieldsAreAddedRemovedAndChanged()
    {
        $entries = $this->getEntries();
        $infos = $this->checkEntry($entries[0]);

        $this->assertEquals(
            [
                'System config removed' => [
                    'general/settings/old_only' => 'general/settings/old_only',
                    'legacy/config/path' => 'legacy/config/path'
                ],
                'System config changed' => [
                    'general/settings/advanced/nested' => 'general/settings/advanced/nested',
                    'general/settings/enabled' => 'general/settings/enabled'
                ],
                'System config added' => [
                    'general/settings/new_only' => 'general/settings/new_only',
                    'new/config/path' => 'new/config/path'
                ]
            ],
            $infos
        );
    }

    public function testAddedSystemConfigFileReportsEveryFieldAsAdded()
    {
        $entries = $this->getEntries();
        $infos = $this->checkEntry($entries[1]);

        $this->assertEquals(
            [
                'System config added' => [
                    'added_section/added_group/added_field' => 'added_section/added_group/added_field'
                ]
            ],
            $infos
        );
    }

    public function testRemovedSystemConfigFileReportsEveryFieldAsRemoved()
    {
        $entries = $this->getEntries();
        $infos = $this->checkEntry($entries[2]);

        $this->assertEquals(
            [
                'System config removed' => [
                    'removed_section/removed_group/removed_field' => 'removed_section/removed_group/removed_field'
                ]
            ],
            $infos
        );
    }

    public function testPresentationOnlyAndFormattingChangesAreIgnored()
    {
        $entries = $this->getEntries();
        $this->assertSame([], $this->checkEntry($entries[3]));
    }

    public function testMalformedSystemConfigXmlHasContextualError()
    {
        $entries = $this->getEntries();
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entries[4], '', $warnings, $infos, $ignored);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('adminhtml/system.xml not parseable');
        $check->check();
    }

    public function testCdataAndNormalTextUseTheSameEffectiveValue()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-cdata/etc/adminhtml/system.xml',
            'vendor_orig/magento/module-cdata/etc/adminhtml/system.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        $this->assertSame(
            [
                'System config changed' => [
                    'cdata/settings/changed' => 'cdata/settings/changed'
                ]
            ],
            $infos
        );
    }

    public function testReferencedFragmentFieldsUseTheirEffectivePaths()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-includes/etc/adminhtml/system/functional.xml',
            'vendor_orig/magento/module-includes/etc/adminhtml/system/functional.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        $this->assertSame(
            [
                'System config changed' => [
                    'custom/effective/path' => 'custom/effective/path',
                    'included/settings/changed' => 'included/settings/changed'
                ],
                'System config removed' => [
                    'included/settings/removed' => 'included/settings/removed'
                ],
                'System config added' => [
                    'included/settings/added' => 'included/settings/added'
                ]
            ],
            $infos
        );
    }

    public function testNestedIncludesPreserveTheContainingGroupPath()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-includes/etc/adminhtml/system/inner.xml',
            'vendor_orig/magento/module-includes/etc/adminhtml/system/inner.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        $this->assertSame(
            [
                'System config changed' => [
                    'included/nested_parent/outer/inner/nested' => 'included/nested_parent/outer/inner/nested'
                ]
            ],
            $infos
        );
    }

    public function testUnreferencedFragmentIsInactive()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-includes/etc/adminhtml/system/unreferenced.xml',
            'vendor_orig/magento/module-includes/etc/adminhtml/system/unreferenced.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        $this->assertSame([], $infos);
    }

    public function testChangedIncludeReferenceReportsRemovedAndAddedFields()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-include-reference/etc/adminhtml/system.xml',
            'vendor_orig/magento/module-include-reference/etc/adminhtml/system.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        $this->assertSame(
            [
                'System config removed' => [
                    'activation/settings/deactivated' => 'activation/settings/deactivated'
                ],
                'System config added' => [
                    'activation/settings/activated' => 'activation/settings/activated'
                ]
            ],
            $infos
        );
    }

    public function testMainFileDoesNotDuplicateChangesOwnedByPatchedFragment()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-includes/etc/adminhtml/system.xml',
            'vendor_orig/magento/module-includes/etc/adminhtml/system.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        $this->assertSame([], $infos);
    }

    public function testMalformedNewIncludeFragmentHasParserContext()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-malformed-new/etc/adminhtml/system/fragment.xml',
            'vendor_orig/magento/module-malformed-new/etc/adminhtml/system/fragment.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        try {
            $check->check();
            $this->fail('Expected malformed system configuration XML to throw an exception');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'adminhtml/system.xml not parseable: Unable to parse XML',
                $exception->getMessage()
            );
            $this->assertMatchesRegularExpression('/line [0-9]+, column [0-9]+:/', $exception->getMessage());
            $this->assertStringContainsString('Opening and ending tag mismatch', $exception->getMessage());
        }
    }

    public function testMalformedOriginalIncludeFragmentHasParserContext()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-malformed-original/etc/adminhtml/system/fragment.xml',
            'vendor_orig/magento/module-malformed-original/etc/adminhtml/system/fragment.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        try {
            $check->check();
            $this->fail('Expected malformed system configuration XML to throw an exception');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'adminhtml/system.xml not parseable: Unable to parse XML',
                $exception->getMessage()
            );
            $this->assertMatchesRegularExpression('/line [0-9]+, column [0-9]+:/', $exception->getMessage());
            $this->assertStringContainsString('Opening and ending tag mismatch', $exception->getMessage());
        }
    }

    public function testCircularIncludeHasContextualError()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-cycle/etc/adminhtml/system/a.xml',
            'vendor_orig/magento/module-cycle/etc/adminhtml/system/a.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('adminhtml/system.xml not parseable: Circular system include detected');
        $check->check();
    }

    /**
     * @return \Ampersand\PatchHelper\Patchfile\Entry[]
     */
    private function getEntries()
    {
        $reader = new Reader($this->testResourcesDir . 'vendor.patch');
        $entries = $reader->getFiles();
        $this->assertCount(5, $entries);
        return $entries;
    }

    /**
     * @param \Ampersand\PatchHelper\Patchfile\Entry $entry
     * @return array<string, array<string, string>>
     */
    private function checkEntry($entry)
    {
        $warnings = $infos = $ignored = [];
        $check = new SystemConfigXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        return $infos;
    }
}
