<?php

use Ampersand\PatchHelper\Checks\WebApiRestXml;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;

class WebApiRestXmlTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/WebApiRestXml/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    /** @var array<string, array<int, string>> */
    private $routeDefinitions = [];

    protected function setUp(): void
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->m2->method('getWebApiRestRouteDefinitions')
            ->willReturnCallback(function () {
                return $this->routeDefinitions;
            });
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

    public function testThirdPartyRouteSharedWithMagentoIsPromotedToWarning()
    {
        $this->routeDefinitions = [
            'GET /V1/shared' => [
                'app/code/Vendor/Module/etc/webapi.xml',
                'vendor/magento/module-shared/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'app/code/Vendor/Module/etc/webapi.xml',
            'vendor_orig/app/code/Vendor/Module/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI changed' => ['vendor/magento/module-shared/etc/webapi.xml (GET /V1/shared)']],
            $warnings
        );
        $this->assertSame([], $infos);
    }

    public function testThirdPartyRouteSharedWithAnotherThirdPartyIsPromotedToWarning()
    {
        $this->routeDefinitions = [
            'GET /V1/shared' => [
                'app/code/Vendor/Module/etc/webapi.xml',
                'vendor/example/module-shared/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'app/code/Vendor/Module/etc/webapi.xml',
            'vendor_orig/app/code/Vendor/Module/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI changed' => ['vendor/example/module-shared/etc/webapi.xml (GET /V1/shared)']],
            $warnings
        );
        $this->assertSame([], $infos);
    }

    public function testThirdPartyAddedRouteSharedWithAnotherThirdPartyIsPromotedToWarning()
    {
        $this->routeDefinitions = [
            'POST /V1/third-party-added' => [
                'vendor/example/module-contributor/etc/webapi.xml',
                'vendor/example/module-third-party-added/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/example/module-third-party-added/etc/webapi.xml',
            'vendor_orig/example/module-third-party-added/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI added' => ['vendor/example/module-contributor/etc/webapi.xml (POST /V1/third-party-added)']],
            $warnings
        );
        $this->assertSame([], $infos);
    }

    public function testThirdPartyRemovedRouteSharedWithAnotherThirdPartyIsPromotedToWarning()
    {
        $this->routeDefinitions = [
            'GET /V1/third-party-removed' => [
                'vendor/example/module-contributor/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/example/module-third-party-removed/etc/webapi.xml',
            'vendor_orig/example/module-third-party-removed/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI removed' => ['vendor/example/module-contributor/etc/webapi.xml (GET /V1/third-party-removed)']],
            $warnings
        );
        $this->assertSame([], $infos);
    }

    public function testThirdPartyChangedRouteSharedWithAnotherThirdPartyIsPromotedToWarning()
    {
        $this->routeDefinitions = [
            'GET /V1/third-party-changed' => [
                'vendor/example/module-contributor/etc/webapi.xml',
                'vendor/example/module-third-party-changed/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/example/module-third-party-changed/etc/webapi.xml',
            'vendor_orig/example/module-third-party-changed/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI changed' => ['vendor/example/module-contributor/etc/webapi.xml (GET /V1/third-party-changed)']],
            $warnings
        );
        $this->assertSame([], $infos);
    }

    public function testMagentoRouteSharedWithMagentoRemainsInfo()
    {
        $this->routeDefinitions = [
            'GET /V1/secure' => [
                'vendor/magento/module-other/etc/webapi.xml',
                'vendor/magento/module-sample/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/magento/module-sample/etc/webapi.xml',
            'vendor_orig/magento/module-sample/etc/webapi.xml'
        );

        $this->assertSame([], $warnings);
        $this->assertSame('GET /V1/secure', $infos['REST WebAPI changed']['GET /V1/secure']);
    }

    public function testMagentoRouteSharedWithThirdPartyGetsTargetWarning()
    {
        $this->routeDefinitions = [
            'GET /V1/secure' => [
                'app/code/Vendor/Module/etc/webapi.xml',
                'vendor/magento/module-sample/etc/webapi.xml'
            ]
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/magento/module-sample/etc/webapi.xml',
            'vendor_orig/magento/module-sample/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI target changed' => ['app/code/Vendor/Module/etc/webapi.xml (GET /V1/secure)']],
            $warnings
        );
        $this->assertSame('GET /V1/secure', $infos['REST WebAPI changed']['GET /V1/secure']);
    }

    public function testMagentoAddedRouteSharedWithThirdPartyGetsTargetWarning()
    {
        $this->routeDefinitions = [
            'GET /V1/added-file' => [
                'app/code/Vendor/Module/etc/webapi.xml',
                'vendor/magento/module-added/etc/webapi.xml'
            ],
        ];

        [$addedWarnings, $infos] = $this->runCheck(
            'vendor/magento/module-added/etc/webapi.xml',
            'vendor_orig/magento/module-added/etc/webapi.xml'
        );
        $this->assertSame(
            ['REST WebAPI target changed' => ['app/code/Vendor/Module/etc/webapi.xml (GET /V1/added-file)']],
            $addedWarnings
        );
        $this->assertSame(
            ['REST WebAPI added' => ['GET /V1/added-file' => 'GET /V1/added-file']],
            $infos
        );
    }

    public function testMagentoRemovedRouteSharedWithThirdPartyGetsTargetWarning()
    {
        $this->routeDefinitions = [
            'DELETE /V1/removed-file' => ['app/code/Vendor/Module/etc/webapi.xml']
        ];

        [$warnings, $infos] = $this->runCheck(
            'vendor/magento/module-removed/etc/webapi.xml',
            'vendor_orig/magento/module-removed/etc/webapi.xml'
        );

        $this->assertSame(
            ['REST WebAPI target changed' => ['app/code/Vendor/Module/etc/webapi.xml (DELETE /V1/removed-file)']],
            $warnings
        );
        $this->assertSame(
            ['REST WebAPI removed' => ['DELETE /V1/removed-file' => 'DELETE /V1/removed-file']],
            $infos
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

    /**
     * @return array{array<string, array<int|string, string>>, array<string, array<string, string>>}
     */
    private function runCheck(string $newFile, string $originalFile): array
    {
        $entry = new Entry($this->testResourcesDir, $newFile, $originalFile);
        $warnings = $infos = $ignored = [];
        $check = new WebApiRestXml($this->m2, $entry, '', $warnings, $infos, $ignored);

        $check->check();
        $this->assertSame([], $ignored);

        return [$warnings, $infos];
    }
}
