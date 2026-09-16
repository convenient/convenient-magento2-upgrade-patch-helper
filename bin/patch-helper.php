#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$earlyBootstrapWarning = Ampersand\PatchHelper\Service\MagentoProjectAutoloader::registerFromCommandArguments(
    $_SERVER['argv'] ?? []
);
if ($earlyBootstrapWarning !== null) {
    fwrite(STDERR, $earlyBootstrapWarning . PHP_EOL);
}

use Symfony\Component\Console\Application;

$currentMemoryLimit = ini_get('memory_limit');

if ($currentMemoryLimit !== false && $currentMemoryLimit !== '-1' && (int) $currentMemoryLimit < 4096) {
    ini_set('memory_limit', '4G');
}

$application = new Application();
$analyseCommand = new Ampersand\PatchHelper\Command\AnalyseCommand();
$application->add($analyseCommand);
$application->run();
exit(0);
