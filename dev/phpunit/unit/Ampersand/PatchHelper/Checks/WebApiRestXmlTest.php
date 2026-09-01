<?php

use Ampersand\PatchHelper\Checks\WebApiRestXml;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;

class WebApiRestXmlTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/WebApiRestXml/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    protected function setUp(): void
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testRoutesAreAddedRemovedAndChanged()
    {
        $this->assertSame(
            [
                'REST WebAPI changed' => [
                    'GET /V1/array-limit' => 'GET /V1/array-limit',
                    'GET /V1/parameters' => 'GET /V1/parameters',
                    'GET /V1/resources' => 'GET /V1/resources',
                    'GET /V1/secure' => 'GET /V1/secure',
                    'GET /V1/service-class' => 'GET /V1/service-class',
                    'GET /V1/service-method' => 'GET /V1/service-method'
                ],
                'REST WebAPI removed' => [
                    'GET /V1/removed' => 'GET /V1/removed'
                ],
                'REST WebAPI added' => [
                    'POST /V1/added' => 'POST /V1/added'
                ]
            ],
            $this->checkEntry('module-sample')
        );
    }

    public function testAddedAndRemovedWebApiFilesReportEveryRoute()
    {
        $this->assertSame(
            [
                'REST WebAPI added' => [
                    'GET /V1/added-file' => 'GET /V1/added-file'
                ]
            ],
            $this->checkEntry('module-added')
        );
        $this->assertSame(
            [
                'REST WebAPI removed' => [
                    'DELETE /V1/removed-file' => 'DELETE /V1/removed-file'
                ]
            ],
            $this->checkEntry('module-removed')
        );
    }

    public function testPartialRouteWithoutServiceIsAValidDeclaration()
    {
        $this->assertSame(
            [
                'REST WebAPI added' => [
                    'GET /V1/partial' => 'GET /V1/partial'
                ]
            ],
            $this->checkEntry('module-partial')
        );
    }

    public function testRouteOrderingFormattingAndSoapMetadataDoNotChangeRestRoutes()
    {
        $this->assertSame([], $this->checkEntry('module-normalized'));
    }

    public function testUrlOrHttpMethodChangesAreReportedAsRemovalAndAddition()
    {
        $this->assertSame(
            [
                'REST WebAPI removed' => [
                    'GET /V1/original' => 'GET /V1/original'
                ],
                'REST WebAPI added' => [
                    'POST /V1/replacement' => 'POST /V1/replacement'
                ]
            ],
            $this->checkEntry('module-identity')
        );
    }

    public function testMalformedWebApiXmlHasContextualError()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-invalid/etc/webapi.xml',
            'vendor_orig/magento/module-invalid/etc/webapi.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new WebApiRestXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('etc/webapi.xml not parseable: Unable to parse XML');
        $check->check();
    }

    public function testNonWebApiXmlPathCannotBeChecked()
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/module-sample/etc/config.xml',
            'vendor_orig/magento/module-sample/etc/config.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new WebApiRestXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertFalse($check->canCheck());
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function checkEntry(string $module)
    {
        $entry = new Entry(
            $this->testResourcesDir,
            'vendor/magento/' . $module . '/etc/webapi.xml',
            'vendor_orig/magento/' . $module . '/etc/webapi.xml'
        );
        $warnings = $infos = $ignored = [];
        $check = new WebApiRestXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([], $warnings);
        $this->assertSame([], $ignored);

        return $infos;
    }
}
