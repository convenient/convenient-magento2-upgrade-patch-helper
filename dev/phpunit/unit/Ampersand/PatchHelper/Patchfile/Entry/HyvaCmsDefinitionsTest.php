<?php

use Ampersand\PatchHelper\Patchfile\Entry\HyvaCmsDefinitions;

class HyvaCmsDefinitionsTest extends \PHPUnit\Framework\TestCase
{
    public function testItExtractsStringAndOrderedIncludes()
    {
        $reader = new HyvaCmsDefinitions('');
        $this->assertSame(['Example_Module::one.json'], $reader->getIncludes(['content' => ['includes' => 'Example_Module::one.json']], 'content'));
        $this->assertSame(
            ['Example_Module::one.json', 'Example_Module::two.json'],
            $reader->getIncludes(['design' => ['includes' => ['Example_Module::one.json', 'Example_Module::two.json']]], 'design')
        );
    }
}
