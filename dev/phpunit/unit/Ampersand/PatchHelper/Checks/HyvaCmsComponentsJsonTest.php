<?php

use Ampersand\PatchHelper\Checks\HyvaCmsComponentsJson;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;

class HyvaCmsComponentsJsonTest extends \PHPUnit\Framework\TestCase
{
    private string $resources = BASE_DIR . '/dev/phpunit/unit/resources/checks/HyvaCmsJson/';

    public function testHyvaComponentAddRemoveAndChangeWarnForExternalContributor()
    {
        $warnings = $infos = $ignored = [];
        $m2 = $this->getMockBuilder(Magento2Instance::class)->disableOriginalConstructor()->getMock();
        $m2->method('getHyvaCmsComponentDefinitions')->willReturn([
            'app/code/Example/Theme/etc/hyva_cms/components.json' => [
                'file' => 'app/code/Example/Theme/etc/hyva_cms/components.json',
                'hyva' => false,
                'definitions' => ['added_card' => [], 'removed_card' => [], 'changed_card' => []],
            ],
            'vendor/hyva-themes/other/etc/hyva_cms/components.json' => [
                'file' => 'vendor/hyva-themes/other/etc/hyva_cms/components.json',
                'hyva' => true,
                'definitions' => ['added_card' => []],
            ],
        ]);
        $entry = new Entry(
            $this->resources,
            'vendor/hyva-themes/core/etc/hyva_cms/components.json',
            'vendor_orig/hyva-themes/core/etc/hyva_cms/components.json'
        );
        $check = new HyvaCmsComponentsJson($m2, $entry, '', $warnings, $infos, $ignored);

        $this->assertTrue($check->canCheck());
        $check->check();
        $this->assertSame([
            'Hyva CMS removed' => ['app/code/Example/Theme/etc/hyva_cms/components.json (removed_card)' => 'app/code/Example/Theme/etc/hyva_cms/components.json (removed_card)'],
            'Hyva CMS changed' => ['app/code/Example/Theme/etc/hyva_cms/components.json (changed_card)' => 'app/code/Example/Theme/etc/hyva_cms/components.json (changed_card)'],
            'Hyva CMS added' => ['app/code/Example/Theme/etc/hyva_cms/components.json (added_card)' => 'app/code/Example/Theme/etc/hyva_cms/components.json (added_card)'],
        ], $warnings);
    }

    public function testFormattingOnlyChangeDoesNotWarn()
    {
        $warnings = $infos = $ignored = [];
        $m2 = $this->getMockBuilder(Magento2Instance::class)->disableOriginalConstructor()->getMock();
        $m2->method('getHyvaCmsComponentDefinitions')->willReturn([]);
        $entry = new Entry($this->resources, 'vendor/hyva-themes/format/etc/hyva_cms/components.json', 'vendor_orig/hyva-themes/format/etc/hyva_cms/components.json');
        $check = new HyvaCmsComponentsJson($m2, $entry, '', $warnings, $infos, $ignored);
        $check->check();
        $this->assertSame([], $warnings);
    }
}
