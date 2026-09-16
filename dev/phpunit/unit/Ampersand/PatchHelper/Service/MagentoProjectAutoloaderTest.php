<?php

use Ampersand\PatchHelper\Service\MagentoProjectAutoloader;

class MagentoProjectAutoloaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var string[] */
    private $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryDirectories) as $directory) {
            @unlink($directory . '/app/bootstrap.php');
            @rmdir($directory . '/app');
            @unlink($directory . '/vendor.patch');
            @rmdir($directory);
        }

        unset($GLOBALS['magento_project_autoloader_bootstrap_count']);
    }

    /**
     * @dataProvider projectPathProvider
     *
     * @param string[] $arguments
     * @param string|null $expectedProjectPath
     * @return void
     */
    public function testGetProjectPath(array $arguments, $expectedProjectPath)
    {
        $this->assertSame($expectedProjectPath, MagentoProjectAutoloader::getProjectPath($arguments));
    }

    /**
     * @return array<string, array{0: string[], 1: string|null}>
     */
    public function projectPathProvider()
    {
        return [
            'missing analyse command' => [
                ['patch-helper.php', 'list'],
                null,
            ],
            'project with no options' => [
                ['patch-helper.php', 'analyse', '/project'],
                '/project',
            ],
            'abbreviated analyse command' => [
                ['patch-helper.php', 'ana', '/project'],
                '/project',
            ],
            'shortest analyse command abbreviation' => [
                ['patch-helper.php', 'a', '/project'],
                '/project',
            ],
            'case insensitive analyse command' => [
                ['patch-helper.php', 'ANALYSE', '/project'],
                '/project',
            ],
            'analyse token after unrelated command' => [
                ['patch-helper.php', 'list', 'analyse', '/project'],
                null,
            ],
            'global option before command' => [
                ['patch-helper.php', '-vvv', 'analyse', '/project'],
                '/project',
            ],
            'global option after command' => [
                ['patch-helper.php', 'analyse', '--no-interaction', '/project'],
                '/project',
            ],
            'auto theme update long option value' => [
                ['patch-helper.php', 'analyse', '--auto-theme-update', '5', '/project'],
                '/project',
            ],
            'auto theme update long option inline value' => [
                ['patch-helper.php', 'analyse', '--auto-theme-update=5', '/project'],
                '/project',
            ],
            'auto theme update short option value' => [
                ['patch-helper.php', 'analyse', '-a', '5', '/project'],
                '/project',
            ],
            'auto theme update short option inline value' => [
                ['patch-helper.php', 'analyse', '-a5', '/project'],
                '/project',
            ],
            'sort by type flag' => [
                ['patch-helper.php', 'analyse', '--sort-by-type', '/project'],
                '/project',
            ],
            'phpstorm threeway flag' => [
                ['patch-helper.php', 'analyse', '--phpstorm-threeway-diff-commands', '/project'],
                '/project',
            ],
            'vendor namespaces long option value' => [
                ['patch-helper.php', 'analyse', '--vendor-namespaces', 'Ampersand,Amazon', '/project'],
                '/project',
            ],
            'vendor namespaces long option inline value' => [
                ['patch-helper.php', 'analyse', '--vendor-namespaces=Ampersand,Amazon', '/project'],
                '/project',
            ],
            'filter long option value' => [
                ['patch-helper.php', 'analyse', '--filter', 'module.xml', '/project'],
                '/project',
            ],
            'filter long option inline value' => [
                ['patch-helper.php', 'analyse', '--filter=module.xml', '/project'],
                '/project',
            ],
            'pad table columns long option value' => [
                ['patch-helper.php', 'analyse', '--pad-table-columns', '120', '/project'],
                '/project',
            ],
            'pad table columns long option inline value' => [
                ['patch-helper.php', 'analyse', '--pad-table-columns=120', '/project'],
                '/project',
            ],
            'php strict errors flag' => [
                ['patch-helper.php', 'analyse', '--php-strict-errors', '/project'],
                '/project',
            ],
            'show info flag' => [
                ['patch-helper.php', 'analyse', '--show-info', '/project'],
                '/project',
            ],
            'show ignore flag' => [
                ['patch-helper.php', 'analyse', '--show-ignore', '/project'],
                '/project',
            ],
            'combined options' => [
                [
                    'patch-helper.php',
                    'analyse',
                    '--show-info',
                    '--filter',
                    'module.xml',
                    '--vendor-namespaces=Ampersand,Amazon',
                    '--pad-table-columns=120',
                    '/project',
                ],
                '/project',
            ],
            'options after project' => [
                ['patch-helper.php', 'analyse', '/project', '--show-info'],
                '/project',
            ],
            'options terminator' => [
                ['patch-helper.php', 'analyse', '--', '/project'],
                '/project',
            ],
            'dash prefixed project after options terminator' => [
                ['patch-helper.php', 'analyse', '--', '-project'],
                '-project',
            ],
            'optional option without value before terminator' => [
                ['patch-helper.php', 'analyse', '--auto-theme-update', '--', '/project'],
                '/project',
            ],
        ];
    }

    /**
     * @dataProvider commandPositionProvider
     *
     * @param string[] $arguments
     * @param string[] $commandNames
     * @param int|null $expectedPosition
     * @return void
     */
    public function testGetCommandPosition(array $arguments, array $commandNames, $expectedPosition)
    {
        $this->assertSame(
            $expectedPosition,
            MagentoProjectAutoloader::getCommandPosition($arguments, $commandNames)
        );
    }

    /**
     * @return array<string, array{0: string[], 1: string[], 2: int|null}>
     */
    public function commandPositionProvider()
    {
        return [
            'exact command wins over longer command' => [
                ['patch-helper.php', 'analyse'],
                ['analyse', 'analyse-cache'],
                1,
            ],
            'unique command abbreviation' => [
                ['patch-helper.php', 'analys'],
                ['analyse', 'cache'],
                1,
            ],
            'case insensitive command' => [
                ['patch-helper.php', 'ANALYSE'],
                ['analyse', 'cache'],
                1,
            ],
            'ambiguous command abbreviation' => [
                ['patch-helper.php', 'a'],
                ['analyse', 'another'],
                null,
            ],
            'unrelated first command' => [
                ['patch-helper.php', 'list', 'analyse'],
                ['analyse'],
                null,
            ],
        ];
    }

    public function testRegisterReturnsWarningWhenVendorPatchIsMissing()
    {
        $project = $this->createProject(false, '<?php');

        $this->assertSame(
            "Early Magento bootstrap skipped: vendor.patch is missing or unreadable: $project/vendor.patch. "
                . 'Continuing with normal command handling.',
            MagentoProjectAutoloader::registerFromCommandArguments(['patch-helper.php', 'analyse', $project])
        );
    }

    public function testRegisterReturnsWarningWhenMagentoBootstrapThrows()
    {
        $project = $this->createProject(
            true,
            "<?php\nthrow new \\RuntimeException('fixture bootstrap failure');\n"
        );

        $this->assertSame(
            'Early Magento bootstrap skipped: Magento bootstrap failed: fixture bootstrap failure. '
                . 'Continuing with normal command handling.',
            MagentoProjectAutoloader::registerFromCommandArguments(['patch-helper.php', 'analyse', $project])
        );
    }

    public function testRegisterSkipsInformationalRequests()
    {
        $project = $this->createProject(
            true,
            "<?php\n\$GLOBALS['magento_project_autoloader_bootstrap_count'] = 1;\n"
        );

        $this->assertNull(
            MagentoProjectAutoloader::registerFromCommandArguments(
                ['patch-helper.php', 'analyse', $project, '--help']
            )
        );
        $this->assertArrayNotHasKey('magento_project_autoloader_bootstrap_count', $GLOBALS);
    }

    public function testRegisterLoadsMagentoBootstrapOnlyOnce()
    {
        $project = $this->createProject(
            true,
            "<?php\n\$GLOBALS['magento_project_autoloader_bootstrap_count'] = "
                . "(\$GLOBALS['magento_project_autoloader_bootstrap_count'] ?? 0) + 1;\n"
        );
        $arguments = ['patch-helper.php', 'analyse', $project];

        $this->assertNull(MagentoProjectAutoloader::registerFromCommandArguments($arguments));
        $this->assertNull(MagentoProjectAutoloader::registerFromCommandArguments($arguments));
        $this->assertSame(1, $GLOBALS['magento_project_autoloader_bootstrap_count']);
    }

    /**
     * @param bool $withVendorPatch
     * @param string $bootstrapContents
     * @return string
     */
    private function createProject($withVendorPatch, $bootstrapContents)
    {
        $project = sys_get_temp_dir() . '/magento-project-autoloader-' . uniqid('', true);
        mkdir($project . '/app', 0777, true);
        $this->temporaryDirectories[] = $project;

        if ($withVendorPatch) {
            file_put_contents($project . '/vendor.patch', 'fixture');
        }
        file_put_contents($project . '/app/bootstrap.php', $bootstrapContents);

        return $project;
    }
}
