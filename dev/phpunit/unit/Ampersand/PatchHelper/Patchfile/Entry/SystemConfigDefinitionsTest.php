<?php

use Ampersand\PatchHelper\Patchfile\Entry\SystemConfigDefinitions;

class SystemConfigDefinitionsTest extends \PHPUnit\Framework\TestCase
{
    public function testModuleLookupsCacheHitsAndMissesSeparatelyForEachVendorTree()
    {
        $root = sys_get_temp_dir() . '/patch-helper-module-lookup-' . bin2hex(random_bytes(8));
        $vendor = $root . '/vendor';
        $original = $root . '/vendor_orig';
        mkdir($vendor . '/etc', 0777, true);
        mkdir($original . '/etc', 0777, true);
        try {
            file_put_contents($vendor . '/etc/module.xml', '<config><module name="Review_Existing"/></config>');
            file_put_contents($original . '/etc/module.xml', '<config><module name="Review_Added"/></config>');
            $definitions = new SystemConfigDefinitions($root, '', '');
            $lookup = new \ReflectionMethod(SystemConfigDefinitions::class, 'findMagentoModuleRoot');
            $lookup->setAccessible(true);

            $this->assertSame($vendor, $lookup->invoke($definitions, 'Review_Existing', $vendor));
            $this->assertNull($lookup->invoke($definitions, 'Review_Added', $vendor));
            $this->assertSame($original, $lookup->invoke($definitions, 'Review_Added', $original));

            // Changing the filesystem exposes a repeat scan; this parser should reuse its cached results.
            file_put_contents($vendor . '/etc/module.xml', '<config><module name="Review_Added"/></config>');
            $this->assertSame($vendor, $lookup->invoke($definitions, 'Review_Existing', $vendor));
            $this->assertNull($lookup->invoke($definitions, 'Review_Added', $vendor));

            // A separate parser must discover the current tree rather than inherit another parser's cache.
            $freshDefinitions = new SystemConfigDefinitions($root, '', '');
            $this->assertSame($vendor, $lookup->invoke($freshDefinitions, 'Review_Added', $vendor));
        } finally {
            foreach ([$vendor, $original] as $side) {
                unlink($side . '/etc/module.xml');
                rmdir($side . '/etc');
                rmdir($side);
            }
            rmdir($root);
        }
    }
}
