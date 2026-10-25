<?php

use Ampersand\PatchHelper\Checks\HyvaCmsIncludeJson;
use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry;

class HyvaCmsIncludeJsonTest extends \PHPUnit\Framework\TestCase
{
    private string $resources = BASE_DIR . '/dev/phpunit/unit/resources/checks/HyvaCmsJson/';

    public function testMaskedIncludedFieldsUseTheThreeDistinctIncludeWarnings()
    {
        $warnings = $infos = $ignored = [];
        $m2 = $this->getMockBuilder(Magento2Instance::class)->disableOriginalConstructor()->getMock();
        $m2->method('getHyvaCmsComponentDefinitions')->willReturn([$this->component()]);
        $m2->method('getHyvaCmsIncludePaths')->willReturn(['Source_Module::etc/hyva_cms/fields.json' => 'vendor/hyva-themes/source/etc/hyva_cms/fields.json']);
        $m2->method('getHyvaCmsFragments')->willReturn([]);
        $entry = new Entry($this->resources, 'vendor/hyva-themes/source/etc/hyva_cms/fields.json', 'vendor_orig/hyva-themes/source/etc/hyva_cms/fields.json');
        $check = new HyvaCmsIncludeJson($m2, $entry, '', $warnings, $infos, $ignored);
        $check->check();
        $value = 'app/code/Example/Theme/etc/hyva_cms/components.json (example_card)';
        $this->assertSame([
            'Hyva CMS Include changed' => [$value => $value],
            'Hyva CMS Include removed' => [$value => $value],
            'Hyva CMS Include added' => [$value => $value],
        ], $warnings);
    }

    private function component(): array
    {
        return [
            'file' => 'app/code/Example/Theme/etc/hyva_cms/components.json',
            'hyva' => false,
            'definitions' => [
                'example_card' => [
                    'content' => [
                        'includes' => 'Source_Module::etc/hyva_cms/fields.json',
                        'added_field' => ['type' => 'text'],
                        'changed_field' => ['type' => 'text'],
                        'removed_field' => ['type' => 'text'],
                    ],
                ],
            ],
        ];
    }
}
