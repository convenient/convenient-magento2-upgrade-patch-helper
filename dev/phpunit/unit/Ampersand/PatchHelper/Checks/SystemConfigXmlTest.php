<?php

use Ampersand\PatchHelper\Checks\SystemConfigXml;
use Ampersand\PatchHelper\Helper\Magento2Instance;
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
