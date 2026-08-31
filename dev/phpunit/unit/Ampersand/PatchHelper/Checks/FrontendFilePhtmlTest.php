<?php

use Ampersand\PatchHelper\Checks;
use Ampersand\PatchHelper\Checks\FrontendFilePhtml;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Reader;
use Ampersand\PatchHelper\Service\GetAppCodePathFromVendorPath;
use Hyva\CompatModuleFallback\Model\CompatModuleRegistry;

class FrontendFilePhtmlTest extends \PHPUnit\Framework\TestCase
{
    private string $testResourcesDir = BASE_DIR . '/dev/phpunit/unit/resources/checks/FrontendFilePhtml/';

    /** @var Magento2Instance|\PHPUnit\Framework\MockObject\MockObject */
    private $m2;

    /** @var \Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification|\PHPUnit\Framework\MockObject\MockObject */
    private $minificationResolver;

    public function testFrontendFilePhtml()
    {
        $this->m2 = $this->getMockBuilder(Magento2Instance::class)
            ->disableOriginalConstructor()
            ->getMock();

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
                'app/design/frontend/Ampersand/theme/Magento_Checkout/templates/cart/form.phtml',
                'vendor/magento/some/path/theme/Magento_Checkout/templates/cart/form.phtml',
                'vendor/ampersand/upgrade-patch-helper-test-module/src/theme/Magento_Checkout/templates/cart/form.phtml'
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
            'app/code/Magento/Checkout/view/frontend/templates/cart/form.phtml',
            $appCodeFilePath
        );

        $infos = [];
        $warnings = ['Override (phtml/js/html)' => []];
        $ignored = [];

        $check = new FrontendFilePhtml($this->m2, $entry, $appCodeFilePath, $warnings, $infos, $ignored);
        $this->assertTrue($check->canCheck(), 'Check should be checkable');
        chdir($this->testResourcesDir);
        $check->check();

        $this->assertEmpty($ignored, 'We should have no ignore level items');
        $this->assertEmpty($infos, 'We should have no info level items');
        $this->assertNotEmpty($warnings, 'We should have a warning');
        $expectedWarnings = [
            'Override (phtml/js/html)' => [
                'app/design/frontend/Ampersand/theme/Magento_Checkout/templates/cart/form.phtml',
                'vendor/ampersand/upgrade-patch-helper-test-module/src/theme/Magento_Checkout/templates/cart/form.phtml'
            ]
        ];
        $this->assertEquals($expectedWarnings, $warnings);
    }

    public function testHyvaCompatibilityTemplateChangeIsReportedAgainstOriginalModuleOverride(): void
    {
        $resources = BASE_DIR . '/dev/phpunit/unit/resources/checks/FrontendFilePhtml/hyva-compatibility';
        $basePath = 'vendor/amasty/gdpr-cookie/view/frontend/templates/widget/settings.phtml';
        $compatPath = 'vendor/amasty/module-gdpr-cookie-hyva/view/frontend/templates/widget/settings.phtml';
        $override = 'app/design/frontend/Vendor/hyva-theme/Amasty_GdprCookie/templates/widget/settings.phtml';

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
            ['template', 'widget/settings.phtml', 'frontend', $baseTheme, null, 'Amasty_GdprCookie', $compatPath],
            ['template', 'widget/settings.phtml', 'frontend', $theme, null, 'Amasty_GdprCookie', $override],
            ['template', 'widget/settings.phtml', 'frontend', $baseTheme, null, 'Amasty_GdprCookieHyva', $compatPath],
            ['template', 'widget/settings.phtml', 'frontend', $theme, null, 'Amasty_GdprCookieHyva', $compatPath],
        ]);

        $m2 = $this->getMockBuilder(Magento2Instance::class)->disableOriginalConstructor()->getMock();
        $m2->method('getObjectManager')->willReturn($objectManager);
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
                $this->assertTrue($entry->fileWasModified());
                $this->assertTrue($entry->vendorChangeIsMeaningful());
                $warnings = $infos = $ignored = [];
                $appCodePath = (new GetAppCodePathFromVendorPath($m2, $entry))->getAppCodePathFromVendorPath();
                $check = new FrontendFilePhtml($m2, $entry, $appCodePath, $warnings, $infos, $ignored);
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

        $this->assertSame([
            $compatPath => [Checks::TYPE_FILE_OVERRIDE => [$override]],
        ], $warningsBySource); // issue 130
    }
}
