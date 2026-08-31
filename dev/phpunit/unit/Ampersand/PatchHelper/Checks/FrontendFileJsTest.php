<?php

use Ampersand\PatchHelper\Checks;
use Ampersand\PatchHelper\Checks\FrontendFileJs;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Reader;
use Ampersand\PatchHelper\Service\GetAppCodePathFromVendorPath;
use Hyva\CompatModuleFallback\Model\CompatModuleRegistry;

class FrontendFileJsTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/FrontendFileJs/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    /** @var \Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification|\PHPUnit\Framework\MockObject\MockObject */
    private $minificationResolver;

    public function testFrontendFileJs()
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->m2->expects($this->never())->method('getObjectManager');
        $this->m2->expects($this->never())->method('getAreaConfig');

        $this->m2->expects($this->once())
            ->method('getListOfThemeDirectories')
            ->willReturn([]);

        $this->m2->expects($this->once())
            ->method('getHyvaBaseThemes')
            ->willReturn([]);

        $this->m2->expects($this->once())
            ->method('getListOfPathsToLibrarys')
            ->willReturn(
                [
                    'vendor/magento/framework/' => 'magento/framework'
                ]
            );

        $this->minificationResolver = $this->getMockBuilder(\Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification::class)
            ->setMethods(['resolve'])
            ->getMock();

        $this->m2->expects($this->any())
            ->method('getMinificationResolver')
            ->willReturn($this->minificationResolver);

        $this->m2->expects($this->once())
            ->method('getListOfPathsToModules')
            ->willReturn(
                [
                    'vendor/magento/module-checkout/' => 'Magento_Checkout'
                ]
            );

        $themeMock = $this->getMockBuilder(\Magento\Theme\Model\Theme::class)
            ->setMethods(['getCode'])
            ->getMock();
        $themeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('some_code');

        $this->m2->expects($this->any())
            ->method('getCustomThemes')
            ->willReturn(
                [
                    $themeMock,
                    $themeMock,
                    $themeMock,
                ]
            );

        $this->minificationResolver->expects($this->any())
            ->method('resolve')
            ->willReturnOnConsecutiveCalls(
                'app/design/frontend/Ampersand/theme/Magento_Checkout/web/js/model/place-order.js',
                'vendor/magento/some/path/theme/Magento_Checkout/web/js/model/place-order.js',
                'vendor/ampersand/test/path/theme/Magento_Checkout/web/js/model/place-order.js'
            );

        $reader = new Reader(
            $this->testResourcesDir . 'vendor.patch'
        );

        $entries = $reader->getFiles();
        $this->assertNotEmpty($entries, 'We should have a patch file to read');

        $entry = $entries[0];

        $appCodeGetter = new GetAppCodePathFromVendorPath($this->m2, $entry);
        $appCodeFilePath = $appCodeGetter->getAppCodePathFromVendorPath();
        $this->assertEquals(
            'app/code/Magento/Checkout/view/frontend/web/js/model/place-order.js',
            $appCodeFilePath
        );

        $infos = [];
        $warnings = ['Override (phtml/js/html)' => []];
        $ignored = [];

        $check = new FrontendFileJs($this->m2, $entry, $appCodeFilePath, $warnings, $infos, $ignored);
        $this->assertTrue($check->canCheck(), 'Check should be checkable');
        chdir($this->testResourcesDir);
        $check->check();

        $this->assertEmpty($ignored, 'We should have no ignore level items');
        $this->assertEmpty($infos, 'We should have no info level items');
        $this->assertNotEmpty($warnings, 'We should have a warning');
        $expectedWarnings = [
            'Override (phtml/js/html)' => [
                'app/design/frontend/Ampersand/theme/Magento_Checkout/web/js/model/place-order.js',
                'vendor/ampersand/test/path/theme/Magento_Checkout/web/js/model/place-order.js'
            ]
        ];
        $this->assertEquals($expectedWarnings, $warnings);
    }

    /** @dataProvider changedPackages */
    public function testHyvaCompatibilityJavaScriptChangeIsReportedAgainstOriginalModuleOverride(string $package): void
    {
        $resources = BASE_DIR . '/dev/phpunit/unit/resources/checks/FrontendFileJs/hyva-compatibility';
        $basePath = 'vendor/amasty/gdpr-cookie/view/frontend/web/js/cookie-settings.js';
        $compatPath = 'vendor/amasty/module-gdpr-cookie-hyva/view/frontend/web/js/cookie-settings.js';
        $override = 'app/design/frontend/Vendor/hyva-theme/Amasty_GdprCookie/web/js/cookie-settings.js';

        $baseTheme = $this->getMockBuilder(\Magento\Theme\Model\Theme::class)
            ->setMethods(['getCode'])->getMock();
        $baseTheme->method('getCode')->willReturn('Hyva/default');
        $theme = $this->getMockBuilder(\Magento\Theme\Model\Theme::class)
            ->setMethods(['getCode'])->getMock();
        $theme->method('getCode')->willReturn('Vendor/hyva-theme');

        $registry = $this->getMockBuilder(CompatModuleRegistry::class)
            ->setMethods(['getCompatModulesFor', 'getCompatModules', 'getOrigModules'])->getMock();
        $registry->method('getCompatModulesFor')->willReturnMap([
            ['Amasty_GdprCookie', ['Amasty_GdprCookieHyva']],
            ['Amasty_GdprCookieHyva', []],
        ]);
        $registry->method('getCompatModules')->willReturn(['Amasty_GdprCookieHyva']);
        $registry->method('getOrigModules')->willReturn(['Amasty_GdprCookie']);
        $objectManager = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->setMethods(['get'])->getMock();
        $objectManager->method('get')->with(CompatModuleRegistry::class)->willReturn($registry);

        $resolver = $this->getMockBuilder(\Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification::class)
            ->setMethods(['resolve'])->getMock();
        // Hyva fallback preserves original-module theme overrides; explicit compat requests do not reverse-map them.
        $resolver->method('resolve')->willReturnMap([
            ['static', 'js/cookie-settings.js', 'frontend', $baseTheme, null, 'Amasty_GdprCookie', $compatPath],
            ['static', 'js/cookie-settings.js', 'frontend', $theme, null, 'Amasty_GdprCookie', $override],
            ['static', 'js/cookie-settings.js', 'frontend', $baseTheme, null, 'Amasty_GdprCookieHyva', $compatPath],
            ['static', 'js/cookie-settings.js', 'frontend', $theme, null, 'Amasty_GdprCookieHyva', $compatPath],
        ]);

        $m2 = $this->getMockBuilder(Magento2Instance::class)->disableOriginalConstructor()->getMock();
        $m2->method('getObjectManager')->willReturn($objectManager);
        $m2->method('getAreaConfig')->willReturn([
            'frontend' => [
                'Magento\\Framework\\View\\Design\\Fallback\\Rule\\ModularSwitch' => [
                    'plugins' => ['hyva_compat_module_fallback' => [
                        'instance' => 'Hyva\\CompatModuleFallback\\Plugin\\ViewFileOverride',
                    ]],
                ],
            ],
        ]);
        $m2->method('getMagentoRoot')->willReturn($resources);
        $m2->method('getListOfPathsToModules')->willReturn([
            'vendor/amasty/gdpr-cookie/' => 'Amasty_GdprCookie',
            'vendor/amasty/module-gdpr-cookie-hyva/' => 'Amasty_GdprCookieHyva',
        ]);
        $m2->method('getModuleFromPath')->willReturnMap([
            [$basePath, 'Amasty_GdprCookie'],
            [$compatPath, 'Amasty_GdprCookieHyva'],
        ]);
        $m2->method('getListOfPathsToLibrarys')->willReturn([]);
        $m2->method('getListOfThemeDirectories')->willReturn([
            'vendor/hyva-themes/magento2-default-theme/',
            'app/design/frontend/Vendor/hyva-theme/',
        ]);
        $m2->method('getListOfHyvaThemeDirectories')->willReturn([
            'vendor/hyva-themes/magento2-default-theme/',
            'app/design/frontend/Vendor/hyva-theme/',
        ]);
        $m2->method('getListOfHyvaThemeFallbackDirectories')->willReturn([]);
        $m2->method('getHyvaBaseThemes')->willReturn(['Hyva/default' => $baseTheme]);
        $m2->method('getHyvaThemes')->willReturn(['Hyva/default' => $baseTheme, 'Vendor/hyva-theme' => $theme]);
        $m2->method('getCustomThemes')->with('frontend')->willReturn([$theme]);
        $m2->method('getMinificationResolver')->willReturn($resolver);

        $entries = (new Reader($resources . '/vendor.patch'))->getFiles();
        $this->assertCount(2, $entries);
        $warningsBySource = [];
        $cwd = getcwd();
        try {
            chdir($resources);
            foreach ($entries as $entry) {
                if ($package && !str_starts_with($entry->getPath(), 'vendor/amasty/' . $package . '/')) {
                    continue;
                }
                $this->assertTrue($entry->fileWasModified());
                $this->assertTrue($entry->vendorChangeIsMeaningful());
                $warnings = $infos = $ignored = [];
                $appCodePath = (new GetAppCodePathFromVendorPath($m2, $entry))->getAppCodePathFromVendorPath();
                $check = new FrontendFileJs($m2, $entry, $appCodePath, $warnings, $infos, $ignored);
                $this->assertTrue($check->canCheck());
                $check->check();
                $this->assertEmpty($infos);
                $this->assertEmpty($ignored);
                if ($warnings) {
                    $warningsBySource[$entry->getPath()] = $warnings;
                }
            }
        } finally {
            chdir($cwd);
        }

        $expected = $package === 'gdpr-cookie' ? [] : [
            $compatPath => [Checks::TYPE_FILE_OVERRIDE => [$override]],
        ];
        $this->assertSame($expected, $warningsBySource); // issue 130
    }

    public function changedPackages(): array
    {
        return ['both packages' => [''], 'base only' => ['gdpr-cookie'], 'compatibility only' => ['module-gdpr-cookie-hyva']];
    }
}
