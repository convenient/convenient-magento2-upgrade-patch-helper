<?php

use Ampersand\PatchHelper\Helper\Magento2Instance\HyvaCmsDefinitions;

class HyvaCmsDefinitionsContextTest extends \PHPUnit\Framework\TestCase
{
    public function testItResolvesIncludesAndCollectsFragmentsWithoutComponents()
    {
        $root = BASE_DIR . '/dev/phpunit/unit/resources/checks/HyvaCmsJson/context';
        $definitions = new HyvaCmsDefinitions($root, [
            'modules/Component/' => 'Example_Component',
            'modules/Fragment/' => 'Example_Fragment',
        ]);
        $definitions->prepare();
        $this->assertSame(
            'modules/Fragment/etc/hyva_cms/fields.json',
            $definitions->getIncludePaths()['Example_Fragment::etc/hyva_cms/fields.json']
        );
        $this->assertArrayHasKey('modules/Fragment/etc/hyva_cms/fields.json', $definitions->getFragments());
    }
}
