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

    /** @dataProvider changedPackages */
    public function testHyvaCompatibilityTemplateChangeIsReportedAgainstOriginalModuleOverride(string $package): void
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

        $expected = $package === 'gdpr-cookie' ? [] : [
            $compatPath => [Checks::TYPE_FILE_OVERRIDE => [$override]],
        ];
        $this->assertSame($expected, $warningsBySource); // issue 130
    }

    public function changedPackages(): array
    {
        return ['both packages' => [''], 'base only' => ['gdpr-cookie'], 'compatibility only' => ['module-gdpr-cookie-hyva']];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHyvaThemeWithoutCompatibilityPackage(): void
    {
        $this->assertFalse(class_exists(CompatModuleRegistry::class));
        $m2 = $this->compatibilityInstance();
        $m2->expects($this->never())->method('getObjectManager');
        $m2->expects($this->never())->method('getAreaConfig');
        $override = 'app/design/frontend/Vendor/hyva-theme/Amasty_GdprCookie/templates/widget/settings.phtml';
        $this->assertSame([Checks::TYPE_FILE_OVERRIDE => [$override]], $this->analyseModuleFile(
            $m2,
            'vendor/amasty/gdpr-cookie/view/frontend/templates/widget/settings.phtml',
            ['Amasty_GdprCookie' => $override]
        ));
    }

    /** @dataProvider inactiveCompatibilityConfig */
    public function testUnavailableCompatibilityFallbackRetainsOriginalWarning(array $config): void
    {
        $this->getMockBuilder(CompatModuleRegistry::class)->setMethods(['getOrigModules'])->getMock();
        $m2 = $this->compatibilityInstance();
        $m2->method('getAreaConfig')->willReturn($config);
        $m2->expects($this->never())->method('getObjectManager');
        $override = 'app/design/frontend/Vendor/hyva-theme/Amasty_GdprCookie/templates/widget/settings.phtml';
        $this->assertSame([Checks::TYPE_FILE_OVERRIDE => [$override]], $this->analyseModuleFile(
            $m2,
            'vendor/amasty/gdpr-cookie/view/frontend/templates/widget/settings.phtml',
            ['Amasty_GdprCookie' => $override]
        ));
    }

    public function inactiveCompatibilityConfig(): array
    {
        $rule = 'Magento\Framework\View\Design\Fallback\Rule\ModularSwitch';
        return [
            'no configuration' => [[]],
            'disabled in frontend overrides global enablement' => [[
                'global' => [$rule => ['plugins' => ['hyva_compat_module_fallback' => [
                    'instance' => 'Hyva\CompatModuleFallback\Plugin\ViewFileOverride',
                ]]]],
                'frontend' => [$rule => ['plugins' => ['hyva_compat_module_fallback' => ['disabled' => true]]]],
            ]],
        ];
    }

    /** @dataProvider moduleFallbackCases */
    public function testCompatibilityFilePrecedence(array $mappings, string $changedPath, string $requestModule, bool $warns): void
    {
        $m2 = $this->compatibilityInstance();
        $this->enableCompatibility($m2, $mappings);
        $override = 'app/design/frontend/Vendor/hyva-theme/' . $requestModule . '/templates/widget/settings.phtml';
        $this->assertSame($warns ? [Checks::TYPE_FILE_OVERRIDE => [$override]] : [], $this->analyseModuleFile(
            $m2,
            $changedPath,
            [$requestModule => $override]
        ));
    }

    public function moduleFallbackCases(): array
    {
        $base = 'vendor/amasty/gdpr-cookie/view/frontend/templates/widget/settings.phtml';
        $compat = 'vendor/amasty/module-gdpr-cookie-hyva/view/frontend/templates/widget/settings.phtml';
        $other = 'vendor/test/compat/view/frontend/templates/Amasty_GdprCookie/widget/settings.phtml';
        return [
            'empty registry' => [[], $base, 'Amasty_GdprCookie', true],
            'registered replacement has no file' => [['Amasty_GdprCookie' => ['Missing_Compat']], $base, 'Amasty_GdprCookie', true],
            'unrelated mapping does not match by filename' => [['Other_Module' => ['Amasty_GdprCookieHyva']], $base, 'Amasty_GdprCookie', true],
            'first compatibility module wins' => [['Amasty_GdprCookie' => ['Amasty_GdprCookieHyva', 'Test_Compat']], $compat, 'Amasty_GdprCookie', true],
            'later compatibility module is shadowed' => [['Amasty_GdprCookie' => ['Amasty_GdprCookieHyva', 'Test_Compat']], $other, 'Amasty_GdprCookie', false],
            'registration order is respected' => [['Amasty_GdprCookie' => ['Test_Compat', 'Amasty_GdprCookieHyva']], $other, 'Amasty_GdprCookie', true],
            'module-prefixed file wins over ordinary file' => [['Amasty_GdprCookie' => ['Test_Compat']], 'vendor/test/compat/view/frontend/templates/widget/settings.phtml', 'Amasty_GdprCookie', false],
            'original frontend wins over compatibility base' => [['Amasty_GdprCookie' => ['Base_Compat']], $base, 'Amasty_GdprCookie', true],
            'compatibility base used when frontend is missing' => [['Base_Original' => ['Base_Compat']], 'vendor/test/base-compat/view/base/templates/widget/settings.phtml', 'Base_Original', true],
            'explicit compatibility namespace still works' => [['Amasty_GdprCookie' => ['Amasty_GdprCookieHyva']], $compat, 'Amasty_GdprCookieHyva', true],
        ];
    }

    /** @dataProvider ordinaryThemes */
    public function testCompatibilityDoesNotAffectOrdinaryThemes(string $area, string $themeCode): void
    {
        $m2 = $this->compatibilityInstance($area, $themeCode);
        $m2->expects($this->never())->method('getObjectManager');
        $m2->expects($this->never())->method('getAreaConfig');
        $override = 'app/design/' . $area . '/' . $themeCode . '/Amasty_GdprCookie/templates/widget/settings.phtml';
        $this->assertSame([Checks::TYPE_FILE_OVERRIDE => [$override]], $this->analyseModuleFile(
            $m2,
            'vendor/amasty/gdpr-cookie/view/' . $area . '/templates/widget/settings.phtml',
            ['Amasty_GdprCookie' => $override]
        ));
    }

    public function ordinaryThemes(): array
    {
        return ['non-Hyva frontend' => ['frontend', 'Vendor/luma-theme'], 'admin' => ['adminhtml', 'Vendor/hyva-theme']];
    }

    public function testCompatibilityModuleCanSupplyMultipleOriginalModules(): void
    {
        $m2 = $this->compatibilityInstance();
        $this->enableCompatibility($m2, [
            'Amasty_GdprCookie' => ['Amasty_GdprCookieHyva'],
            'Base_Original' => ['Amasty_GdprCookieHyva'],
        ]);
        $overrides = [
            'Amasty_GdprCookie' => 'app/design/frontend/Vendor/hyva-theme/Amasty_GdprCookie/templates/widget/settings.phtml',
            'Base_Original' => 'app/design/frontend/Vendor/hyva-theme/Base_Original/templates/widget/settings.phtml',
        ];
        $this->assertSame([Checks::TYPE_FILE_OVERRIDE => array_values($overrides)], $this->analyseModuleFile(
            $m2,
            'vendor/amasty/module-gdpr-cookie-hyva/view/frontend/templates/widget/settings.phtml',
            $overrides
        ));
    }

    /** @dataProvider vendorFallbackSources */
    public function testVendorFallbackIsNotReportedAsALocalOverride(string $source): void
    {
        $m2 = $this->compatibilityInstance();
        $this->enableCompatibility($m2, ['Amasty_GdprCookie' => ['Amasty_GdprCookieHyva']]);
        $this->assertSame([], $this->analyseModuleFile(
            $m2,
            'vendor/amasty/module-gdpr-cookie-hyva/view/frontend/templates/widget/settings.phtml',
            ['Amasty_GdprCookie' => $m2->getMagentoRoot() . '/' . $source]
        ));
    }

    public function vendorFallbackSources(): array
    {
        return [
            'core resolver without frontend plugin' => ['vendor/amasty/gdpr-cookie/view/frontend/templates/widget/settings.phtml'],
            'frontend resolver with plugin' => ['vendor/amasty/module-gdpr-cookie-hyva/view/frontend/templates/widget/settings.phtml'],
        ];
    }

    public function testRegistryErrorsAreNotSilentlyIgnored(): void
    {
        $m2 = $this->compatibilityInstance();
        $registry = $this->enableCompatibility($m2, ['Broken_Module' => []]);
        $registry->expects($this->once())->method('getCompatModulesFor')->willThrowException(new RuntimeException('Invalid registry configuration'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid registry configuration');
        $this->analyseModuleFile($m2, 'vendor/amasty/gdpr-cookie/view/frontend/templates/widget/settings.phtml', []);
    }

    private function compatibilityInstance(string $area = 'frontend', string $themeCode = 'Vendor/hyva-theme'): Magento2Instance
    {
        $m2 = $this->getMockBuilder(Magento2Instance::class)->disableOriginalConstructor()->getMock();
        $theme = $this->getMockBuilder(\Magento\Theme\Model\Theme::class)->setMethods(['getCode'])->getMock();
        $theme->method('getCode')->willReturn($themeCode);
        $m2->method('getMagentoRoot')->willReturn($this->testResourcesDir . 'hyva-compatibility');
        $m2->method('getCustomThemes')->with($area)->willReturn([$theme]);
        $m2->method('getHyvaThemes')->willReturn(['Vendor/hyva-theme' => $theme]);
        $m2->method('getHyvaBaseThemes')->willReturn([]);
        $m2->method('getListOfHyvaThemeDirectories')->willReturn([]);
        $m2->method('getListOfHyvaThemeFallbackDirectories')->willReturn([]);
        $m2->method('getListOfPathsToLibrarys')->willReturn([]);
        $m2->method('getListOfThemeDirectories')->willReturn([]);
        $modules = [
            'vendor/amasty/gdpr-cookie/' => 'Amasty_GdprCookie',
            'vendor/amasty/module-gdpr-cookie-hyva/' => 'Amasty_GdprCookieHyva',
            'vendor/test/compat/' => 'Test_Compat',
            'vendor/test/base-compat/' => 'Base_Compat',
            'vendor/test/base-original/' => 'Base_Original',
            'vendor/test/missing/' => 'Missing_Compat',
        ];
        $m2->method('getListOfPathsToModules')->willReturn($modules);
        $m2->method('getModuleFromPath')->willReturnCallback(function ($path) use ($modules) {
            foreach ($modules as $directory => $module) {
                if (str_starts_with($path, $directory)) {
                    return $module;
                }
            }
            return '';
        });
        return $m2;
    }

    private function enableCompatibility(Magento2Instance $m2, array $mappings)
    {
        $m2->method('getAreaConfig')->willReturn([
            'frontend' => ['Magento\Framework\View\Design\Fallback\Rule\ModularSwitch' => [
                'plugins' => ['hyva_compat_module_fallback' => ['instance' => 'Hyva\CompatModuleFallback\Plugin\ViewFileOverride']],
            ]],
        ]);
        $registry = $this->getMockBuilder(CompatModuleRegistry::class)->setMethods(['getOrigModules', 'getCompatModulesFor'])->getMock();
        $registry->method('getOrigModules')->willReturn(array_keys($mappings));
        $registry->method('getCompatModulesFor')->willReturnCallback(function ($module) use ($mappings) {
            return $mappings[$module] ?? [];
        });
        $om = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)->setMethods(['get'])->getMock();
        $om->method('get')->with(CompatModuleRegistry::class)->willReturn($registry);
        $m2->method('getObjectManager')->willReturn($om);
        return $registry;
    }

    private function analyseModuleFile(Magento2Instance $m2, string $path, array $resolvedModules): array
    {
        $entry = $this->getMockBuilder(\Ampersand\PatchHelper\Patchfile\Entry::class)->disableOriginalConstructor()->getMock();
        $entry->method('getPath')->willReturn($path);
        $resolver = $this->getMockBuilder(\Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification::class)
            ->setMethods(['resolve'])->getMock();
        $resolver->method('resolve')->willReturnCallback(function ($type, $name, $area, $theme, $locale, $module) use ($resolvedModules, $path) {
            return $resolvedModules[$module] ?? $path;
        });
        $m2->method('getMinificationResolver')->willReturn($resolver);
        $warnings = $infos = $ignored = [];
        $appCodePath = (new GetAppCodePathFromVendorPath($m2, $entry))->getAppCodePathFromVendorPath();
        $cwd = getcwd();
        try {
            chdir($m2->getMagentoRoot());
            (new FrontendFilePhtml($m2, $entry, $appCodePath, $warnings, $infos, $ignored))->check();
        } finally {
            chdir($cwd);
        }
        return $warnings;
    }
}
