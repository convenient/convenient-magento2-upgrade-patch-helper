<?php

use Ampersand\PatchHelper\Checks\CspWhitelistXml;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;
use Ampersand\PatchHelper\Patchfile\Reader;

class CspWhitelistXmlTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/CspWhitelistXml/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    protected function setUp(): void
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCspDeclarationsAreAddedRemovedAndChanged()
    {
        $infos = $this->checkEntry($this->getEntries()[0]);

        $this->assertSame(
            [
                'CSP removed' => [
                    'script-src/shared_hash' => 'script-src/shared_hash',
                    'script-src/removed_host' => 'script-src/removed_host',
                    'script-src/type_only' => 'script-src/type_only'
                ],
                'CSP added' => [
                    'script-src/type_only' => 'script-src/type_only',
                    'script-src/shared_hash' => 'script-src/shared_hash',
                    'script-src/added_host' => 'script-src/added_host'
                ]
            ],
            $infos
        );
    }

    public function testAddedCspFileReportsEveryDeclarationAsAdded()
    {
        $this->assertSame(
            [
                'CSP added' => [
                    'img-src/new_host' => 'img-src/new_host'
                ]
            ],
            $this->checkEntry($this->getEntries()[1])
        );
    }

    public function testRemovedCspFileReportsEveryDeclarationAsRemoved()
    {
        $this->assertSame(
            [
                'CSP removed' => [
                    'connect-src/old_host' => 'connect-src/old_host'
                ]
            ],
            $this->checkEntry($this->getEntries()[2])
        );
    }

    public function testReorderedAndFormattedCspDeclarationsAreNotReported()
    {
        $this->assertSame([], $this->checkEntry($this->getEntries()[3]));
    }

    public function testCspValueWhitespaceChangesAreReported()
    {
        $this->assertSame(
            [
                'CSP removed' => [
                    'style-src/space-sensitive' => 'style-src/space-sensitive'
                ],
                'CSP added' => [
                    'style-src/space-sensitive' => 'style-src/space-sensitive'
                ]
            ],
            $this->checkEntry($this->getEntries()[8])
        );
    }

    public function testDeclarationsInEveryValuesBlockAreChecked()
    {
        $this->assertSame(
            [
                'CSP changed' => [
                    'connect-src/second-block' => 'connect-src/second-block'
                ]
            ],
            $this->checkEntry($this->getEntries()[9])
        );
    }

    public function testHashDeclarationIdDoesNotChangeEffectivePolicy()
    {
        $this->assertSame([], $this->checkEntry($this->getEntries()[10]));
    }

    public function testEffectiveHostAndHashValueChangesAreReported()
    {
        $this->assertSame(
            [
                'CSP changed' => [
                    'script-src/algorithm-hash' => 'script-src/algorithm-hash',
                    'connect-src/changed-host' => 'connect-src/changed-host'
                ]
            ],
            $this->checkEntry($this->getEntries()[11])
        );
    }

    public function testNonCspXmlPathCannotBeChecked()
    {
        $entry = new Entry($this->testResourcesDir, 'vendor/magento/module-sample/etc/config.xml', 'vendor_orig/magento/module-sample/etc/config.xml');
        $warnings = $infos = $ignored = [];
        $check = new CspWhitelistXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertFalse($check->canCheck());
    }

    public function testDeclarationsWithTheSameValueIdInDifferentPoliciesAreDistinct()
    {
        $this->assertSame(
            [
                'CSP changed' => [
                    'script-src/shared' => 'script-src/shared'
                ],
                'CSP added' => [
                    'img-src/shared' => 'img-src/shared'
                ]
            ],
            $this->checkEntry($this->getEntries()[4])
        );
    }

    public function testPolicyOnlyChangesAreNotReported()
    {
        $this->assertSame([], $this->checkEntry($this->getEntries()[5]));
    }

    public function testMalformedCspXmlHasContextualError()
    {
        $this->assertMalformedEntryHasContext($this->getEntries()[6]);
    }

    public function testMalformedOriginalCspXmlHasContextualError()
    {
        $this->assertMalformedEntryHasContext($this->getEntries()[7]);
    }

    /**
     * @return \Ampersand\PatchHelper\Patchfile\Entry[]
     */
    private function getEntries()
    {
        $entries = (new Reader($this->testResourcesDir . 'vendor.patch'))->getFiles();
        $this->assertCount(12, $entries);
        return $entries;
    }

    /**
     * @param \Ampersand\PatchHelper\Patchfile\Entry $entry
     * @return void
     */
    private function assertMalformedEntryHasContext($entry)
    {
        $warnings = $infos = $ignored = [];
        $check = new CspWhitelistXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        try {
            $check->check();
            $this->fail('Expected malformed CSP XML to throw an exception');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'etc/csp_whitelist.xml not parseable: Unable to parse XML',
                $exception->getMessage()
            );
            $this->assertMatchesRegularExpression('/line [0-9]+, column [0-9]+:/', $exception->getMessage());
            $this->assertStringContainsString('Opening and ending tag mismatch', $exception->getMessage());
        }
    }

    /**
     * @param \Ampersand\PatchHelper\Patchfile\Entry $entry
     * @return array<string, array<string, string>>
     */
    private function checkEntry($entry)
    {
        $warnings = $infos = $ignored = [];
        $check = new CspWhitelistXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        return $infos;
    }
}
