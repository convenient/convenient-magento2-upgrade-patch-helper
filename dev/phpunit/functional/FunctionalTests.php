<?php

class FunctionalTests extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $versionPath
     * @param string $arguments
     * @return string
     */
    private function generateAnalyseCommand($versionPath, $arguments = '')
    {
        if (!str_contains($arguments, '--php-strict-errors')) {
            $arguments .= ' --php-strict-errors ';
        }

        $baseDir = BASE_DIR;
        $command = "php {$baseDir}/bin/patch-helper.php analyse $arguments {$baseDir}{$versionPath}";
        echo PHP_EOL . "Generated command: $command" . PHP_EOL;
        return $command;
    }

    /**
     * @link https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper/issues/9
     * @depends testMagentoTwoFourNoDb
     * @group v24nodb
     */
    public function testVirtualTypesNoException()
    {
        copy(
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch.bak'
        );
        copy(
            BASE_DIR . '/dev/phpunit/functional/resources/reflection-exception.diff',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch'
        );
        $this->assertFileEquals(
            BASE_DIR . '/dev/phpunit/functional/resources/reflection-exception.diff',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch',
            "vendor.patch did not update for this test"
        );

        exec($this->generateAnalyseCommand('/dev/instances/magentom24nodb', '-vvv'), $output, $return);

        copy(
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch.bak',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch'
        );
        $this->assertEquals(0, $return, "The return code of the command was not zero");
        $output = implode(PHP_EOL, $output);
        $this->assertStringContainsString('(virtualType?)', $output, 'Output should mention virtualType exception');
    }

    /**
     * @group v23
     */
    public function testMagentoTwoThree()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom23/app/etc/env.php', "Magento 2.3 is not installed");

        exec($this->generateAnalyseCommand('/dev/instances/magentom23', '--pad-table-columns 130 --sort-by-type --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, array_filter($output, function ($line) {
            return !preg_match('/^(INFO|IGNORE) count:/', $line);
        }));

        $this->assertReportOutput('/dev/phpunit/functional/expected_output/magentom23.out.txt', $output);
    }

    /**
     * @group v23
     */
    public function testMagentoTwoThreeShowCustomModules()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom23/app/etc/env.php', "Magento 2.3 is not installed");

        exec($this->generateAnalyseCommand('/dev/instances/magentom23', '--pad-table-columns 130 --sort-by-type --vendor-namespaces Ampersand,Amazon'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, array_filter($output, function ($line) {
            return !preg_match('/^(INFO|IGNORE) count:/', $line);
        }));

        $this->assertReportOutput(
            '/dev/phpunit/functional/expected_output/magentom23VendorNamespaces.out.txt',
            $output
        );
    }

    /**
     * @link https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper/pull/27
     * @depends testMagentoTwoThree
     * @group v23
     */
    public function testAutoApplyPatches()
    {
        $vendorPatchPath = BASE_DIR . '/dev/instances/magentom23/vendor.patch';
        $vendorPatchBackupPath = $vendorPatchPath . '.bak';
        $overridePath = BASE_DIR
            . '/dev/instances/magentom23/app/design/frontend/Ampersand/theme/Magento_Bundle/templates/js/components.phtml';
        $overrideBaselinePath = BASE_DIR . '/dev/phpunit/functional/resources/auto-apply-before.phtml';

        copy(
            $vendorPatchPath,
            $vendorPatchBackupPath
        );
        try {
            copy($overrideBaselinePath, $overridePath);
            copy(
                BASE_DIR . '/dev/phpunit/functional/resources/template-change.diff',
                $vendorPatchPath
            );
            $this->assertFileEquals(
                BASE_DIR . '/dev/phpunit/functional/resources/template-change.diff',
                $vendorPatchPath,
                "vendor.patch did not update for this test"
            );

            exec($this->generateAnalyseCommand('/dev/instances/magentom23', '--auto-theme-update 5'), $output, $return);

            $this->assertEquals(0, $return);
            $this->assertFileEquals(
                BASE_DIR . '/dev/phpunit/functional/expected_output/auto-apply-patch.txt',
                $overridePath,
                "This file did not get auto patched properly"
            );

            $lastLine = array_pop($output);
            $this->assertStringStartsWith('You should review the above', $lastLine);

            $output = implode(PHP_EOL, $output);

            $this->assertReportOutput(
                '/dev/phpunit/functional/expected_output/magentom23-auto-apply.out.txt',
                $output
            );
        } finally {
            copy($vendorPatchBackupPath, $vendorPatchPath);
            unlink($vendorPatchBackupPath);
            copy($overrideBaselinePath, $overridePath);
        }
    }

    /**
     * @link https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper/issues/9
     * @depends testMagentoTwoThree
     * @group v23
     */
    public function testUnifiedDiffIsProvided()
    {
        copy(
            BASE_DIR . '/dev/instances/magentom23/vendor.patch',
            BASE_DIR . '/dev/instances/magentom23/vendor.patch.bak'
        );
        copy(
            BASE_DIR . '/dev/phpunit/functional/resources/not-a-unified-diff.txt',
            BASE_DIR . '/dev/instances/magentom23/vendor.patch'
        );
        $this->assertFileEquals(
            BASE_DIR . '/dev/phpunit/functional/resources/not-a-unified-diff.txt',
            BASE_DIR . '/dev/instances/magentom23/vendor.patch',
            "vendor.patch did not update for this test"
        );

        exec($this->generateAnalyseCommand('/dev/instances/magentom23'), $output, $return);
        copy(
            BASE_DIR . '/dev/instances/magentom23/vendor.patch.bak',
            BASE_DIR . '/dev/instances/magentom23/vendor.patch'
        );
        $this->assertEquals(1, $return);
    }

    /**
     * @group v24
     */
    public function testMagentoTwoFour()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom24/app/etc/env.php', "Magento 2.4 is not installed");

        exec($this->generateAnalyseCommand('/dev/instances/../instances/magentom24', '--pad-table-columns 130 --sort-by-type --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, $output);

        $this->assertReportOutput('/dev/phpunit/functional/expected_output/magentom24.out.txt', $output);
    }

    /**
     * @group v24
     */
    public function testMagentoTwoFourVirtualPlugin()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom24/app/etc/env.php', "Magento 2.4 is not installed");

        exec($this->generateAnalyseCommand('/dev/instances/magentom24'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");
    }

    /**
     * @group v2451nodb
     */
    public function testMagentoTwoFourFivePointOneNoDb()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom2451nodb/app/etc/di.xml', "Magento 2.4 directory is wrong");
        $this->assertFileDoesNotExist(BASE_DIR . '/dev/instances/magentom2451nodb/app/etc/env.php', "Magento 2.4 is installed when it shouldnt be");

        exec($this->generateAnalyseCommand('/dev/instances/../instances/magentom2451nodb', '--pad-table-columns 130 --sort-by-type --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, array_filter($output, function ($line) {
            return !preg_match('/^(INFO|IGNORE) count:/', $line);
        }));

        // We should get the same output regardless of whether we are connected to a DB or not
        $this->assertReportOutput(
            '/dev/phpunit/functional/expected_output/magentom2451-nodb.out.txt',
            $output
        );
    }

    /**
     * @group v24nodbnohyva
     */
    public function testMagentoTwoFourNoDbWithoutHyva()
    {
        $instancePath = BASE_DIR . '/dev/instances/magentom24nodbnohyva';
        $this->assertFileExists($instancePath . '/app/etc/di.xml', "Magento 2.4 directory is wrong");
        $this->assertFileDoesNotExist($instancePath . '/app/etc/env.php', "Magento 2.4 is installed when it shouldnt be");
        $this->assertDirectoryDoesNotExist($instancePath . '/vendor/hyva-themes', "Hyva is installed when it shouldnt be");

        exec($this->generateAnalyseCommand('/dev/instances/../instances/magentom24nodbnohyva', '--pad-table-columns 130 --sort-by-type --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, array_filter($output, function ($line) {
            return !preg_match('/^(INFO|IGNORE) count:/', $line);
        }));

        $this->assertReportOutput(
            '/dev/phpunit/functional/expected_output/magentom24-nodb-nohyva.out.txt',
            $output
        );
    }

    /**
     * @group v24nodb
     */
    public function testMagentoTwoFourNoDb()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom24nodb/app/etc/di.xml', "Magento 2.4 directory is wrong");
        $this->assertFileDoesNotExist(BASE_DIR . '/dev/instances/magentom24nodb/app/etc/env.php', "Magento 2.4 is installed when it shouldnt be");

        exec($this->generateAnalyseCommand('/dev/instances/../instances/magentom24nodb', '--pad-table-columns 130 --sort-by-type --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, $output);

        // We should get the same output regardless of whether we are connected to a DB or not
        $this->assertReportOutput('/dev/phpunit/functional/expected_output/magentom24-nodb.out.txt', $output);
    }

    /**
     * @group v24nodb
     */
    public function testMagentoTwoFourNoDbShowInfo()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom24nodb/app/etc/di.xml', "Magento 2.4 directory is wrong");
        $this->assertFileDoesNotExist(BASE_DIR . '/dev/instances/magentom24nodb/app/etc/env.php', "Magento 2.4 is installed when it shouldnt be");

        exec($this->generateAnalyseCommand('/dev/instances/../instances/magentom24nodb', '--pad-table-columns 130 --sort-by-type --show-info --show-ignore --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, $output);

        // We should get the same output regardless of whether we are connected to a DB or not
        $this->assertReportOutput('/dev/phpunit/functional/expected_output/magentom24-show-info.out.txt', $output);
    }

    /**
     * @group v24nodb
     */
    public function testMagentoTwoFourNoDbPhpstormThreewayDiffFlag()
    {
        $this->assertFileExists(BASE_DIR . '/dev/instances/magentom24nodb/app/etc/di.xml', "Magento 2.4 directory is wrong");
        $this->assertFileDoesNotExist(BASE_DIR . '/dev/instances/magentom24nodb/app/etc/env.php', "Magento 2.4 is installed when it shouldnt be");

        exec($this->generateAnalyseCommand('/dev/instances/../instances/magentom24nodb', '--pad-table-columns 130 --phpstorm-threeway-diff-commands --sort-by-type --vendor-namespaces Ampersand'), $output, $return);
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, $output);

        $this->assertReportOutput(
            '/dev/phpunit/functional/expected_output/magentom24nodb-threeway-diff.out.txt',
            $output
        );
    }

    /**
     * @link https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper/pull/67#issuecomment-1238978463
     *
     * @group v24nodb
     */
    public function testConsumersChangedDiff()
    {
        copy(
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch.bak'
        );
        copy(
            BASE_DIR . '/dev/phpunit/functional/resources/changed-consumers.diff',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch'
        );
        $this->assertFileEquals(
            BASE_DIR . '/dev/phpunit/functional/resources/changed-consumers.diff',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch',
            "vendor.patch did not update for this test"
        );

        exec($this->generateAnalyseCommand('/dev/instances/magentom24nodb', '--show-info --sort-by-type --pad-table-columns 100'), $output, $return);

        copy(
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch.bak',
            BASE_DIR . '/dev/instances/magentom24nodb/vendor.patch'
        );
        $this->assertEquals(0, $return, "The return code of the command was not zero");

        $lastLine = array_pop($output);
        $this->assertStringStartsWith('You should review the above', $lastLine);

        $output = implode(PHP_EOL, $output);

        $this->assertReportOutput(
            '/dev/phpunit/functional/expected_output/magentom24nodb-changed-consumers.out.txt',
            $output
        );
    }

    /**
     * @param string $expectedOutputFilepath
     * @param string $output
     * @return void
     */
    private function assertReportOutput($expectedOutputFilepath, $output)
    {
        $expectedOutput = $this->fileGetContents($expectedOutputFilepath);
        if ($expectedOutput === $output) {
            return;
        }

        $actualOutputDirectory = BASE_DIR . '/dev/phpunit/functional/actual_output';
        if (!is_dir($actualOutputDirectory)) {
            mkdir($actualOutputDirectory, 0777, true);
        }
        $actualOutputPath = $actualOutputDirectory . '/' . basename($expectedOutputFilepath) . '.actual';
        file_put_contents($actualOutputPath, $output);

        $diff = (new \SebastianBergmann\Diff\Differ("--- Expected report output\n+++ Actual report output\n"))
            ->diff($expectedOutput, $output);

        $this->fail(
            "Report output did not match. Actual output saved to $actualOutputPath" . PHP_EOL . PHP_EOL . $diff
        );
    }

    /**
     * @param $filepath
     * @return string
     */
    private function fileGetContents($filepath)
    {
        return \trim(\file_get_contents(BASE_DIR . $filepath));
    }
}
