<?php

namespace Ampersand\PatchHelper\Service;

use Ampersand\PatchHelper\Command\AnalyseCommandDefinition;

/**
 * Loads the target Magento project without constructing Symfony Console first.
 *
 * @see \Symfony\Component\Console\Input\ArgvInput
 */
class MagentoProjectAutoloader
{
    /**
     * Loads the target Magento project before Symfony Console is first used.
     *
     * @param string[] $arguments
     * @return string|null
     */
    public static function registerFromCommandArguments(array $arguments)
    {
        if (self::isInformationalRequest($arguments)) {
            return null;
        }

        $projectPath = self::getProjectPath($arguments);
        if ($projectPath === null) {
            return null;
        }

        if (!is_dir($projectPath) || !is_readable($projectPath)) {
            return self::getBootstrapWarning("project directory is missing or unreadable: $projectPath");
        }

        $projectPath = rtrim($projectPath, '/\\');
        $patchPath = $projectPath . DIRECTORY_SEPARATOR . 'vendor.patch';
        if (!is_file($patchPath) || !is_readable($patchPath)) {
            return self::getBootstrapWarning("vendor.patch is missing or unreadable: $patchPath");
        }

        $bootstrapPath = $projectPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrapPath) || !is_readable($bootstrapPath)) {
            return self::getBootstrapWarning("Magento bootstrap is missing or unreadable: $bootstrapPath");
        }

        $autoloadPath = $projectPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_file($autoloadPath) || !is_readable($autoloadPath)) {
            return self::getBootstrapWarning("Vendor autoload is missing or unreadable: $autoloadPath");
        }

        try {
            require_once $bootstrapPath;
        } catch (\Throwable $throwable) {
            return self::getBootstrapWarning('Magento bootstrap failed: ' . $throwable->getMessage());
        }

        return null;
    }

    /**
     * Finds the required project argument using the relevant Symfony Console token rules.
     *
     * @param string[] $arguments
     * @return string|null
     * @see \Symfony\Component\Console\Input\ArgvInput::parse()
     * @see \Symfony\Component\Console\Input\ArgvInput::getFirstArgument()
     */
    public static function getProjectPath(array $arguments)
    {
        $commandPosition = self::getCommandPosition(
            $arguments,
            [AnalyseCommandDefinition::getAnalyseCommandName()]
        );
        if ($commandPosition === null) {
            return null;
        }

        $options = self::getOptionsByName();
        $shortcuts = self::getOptionsByShortcut();
        $parseOptions = true;
        $argumentCount = count($arguments);

        for ($position = $commandPosition + 1; $position < $argumentCount; $position++) {
            $argument = $arguments[$position];
            if ($parseOptions && $argument === '--') {
                $parseOptions = false;
                continue;
            }

            if ($parseOptions && strpos($argument, '--') === 0) {
                self::consumeLongOption($argument, $options, $arguments, $position);
                continue;
            }

            if ($parseOptions && strpos($argument, '-') === 0 && $argument !== '-') {
                self::consumeShortOption($argument, $shortcuts, $arguments, $position);
                continue;
            }

            return $argument;
        }

        return null;
    }

    /**
     * Finds the first command token when it uniquely matches a supported command name.
     *
     * @param string[] $arguments
     * @param string[] $commandNames
     * @return int|null
     */
    public static function getCommandPosition(array $arguments, array $commandNames)
    {
        $parseOptions = true;
        $argumentCount = count($arguments);

        for ($position = 1; $position < $argumentCount; $position++) {
            $argument = $arguments[$position];
            if ($parseOptions && $argument === '--') {
                $parseOptions = false;
                continue;
            }

            if ($parseOptions && strpos($argument, '-') === 0 && $argument !== '-') {
                continue;
            }

            return self::resolveCommandName($argument, $commandNames) === null ? null : $position;
        }

        return null;
    }

    /**
     * @param string $argument
     * @param string[] $commandNames
     * @return string|null
     */
    private static function resolveCommandName($argument, array $commandNames)
    {
        if ($argument === '') {
            return null;
        }

        foreach ($commandNames as $commandName) {
            if ($argument === $commandName) {
                return $commandName;
            }
        }

        $matches = array_values(array_filter($commandNames, function ($commandName) use ($argument) {
            return strpos($commandName, $argument) === 0;
        }));
        if (count($matches) === 1) {
            return $matches[0];
        }
        if (count($matches) > 1) {
            return null;
        }

        $matches = array_values(array_filter($commandNames, function ($commandName) use ($argument) {
            return stripos($commandName, $argument) === 0;
        }));

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * @param string[] $arguments
     * @return bool
     */
    private static function isInformationalRequest(array $arguments)
    {
        foreach ($arguments as $argument) {
            if ($argument === '--') {
                return false;
            }
            if (in_array($argument, ['--help', '-h', '--version', '-V'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $reason
     * @return string
     */
    private static function getBootstrapWarning($reason)
    {
        $reason = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $reason);

        return 'Early Magento bootstrap skipped: ' . trim((string)$reason)
            . '. Continuing with normal command handling.';
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private static function getOptionsByName()
    {
        $options = [];
        foreach (AnalyseCommandDefinition::getAnalyseOptions() as $option) {
            $options[$option['name']] = $option;
        }
        return $options;
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private static function getOptionsByShortcut()
    {
        $options = [];
        foreach (AnalyseCommandDefinition::getAnalyseOptions() as $option) {
            if ($option['shortcut'] !== null) {
                $options[$option['shortcut']] = $option;
            }
        }
        return $options;
    }

    /**
     * Mirrors Symfony's handling of long options with inline or following values.
     *
     * @param string $argument
     * @param array<string, array<string, string|null>> $options
     * @param string[] $arguments
     * @param int $position
     * @return void
     * @see \Symfony\Component\Console\Input\ArgvInput::parseLongOption()
     * @see \Symfony\Component\Console\Input\ArgvInput::addLongOption()
     */
    private static function consumeLongOption($argument, array $options, array $arguments, &$position)
    {
        $option = substr($argument, 2);
        if (strpos($option, '=') !== false) {
            return;
        }

        if (
            !isset($options[$option])
            || !AnalyseCommandDefinition::optionAcceptsValue($options[$option]['valueMode'])
        ) {
            return;
        }

        self::consumeFollowingOptionValue($arguments, $position);
    }

    /**
     * Mirrors Symfony's handling of short options and short option sets.
     *
     * @param string $argument
     * @param array<string, array<string, string|null>> $shortcuts
     * @param string[] $arguments
     * @param int $position
     * @return void
     * @see \Symfony\Component\Console\Input\ArgvInput::parseShortOption()
     * @see \Symfony\Component\Console\Input\ArgvInput::parseShortOptionSet()
     */
    private static function consumeShortOption($argument, array $shortcuts, array $arguments, &$position)
    {
        $shortcutsInArgument = substr($argument, 1);
        $shortcutCount = strlen($shortcutsInArgument);

        for ($shortcutPosition = 0; $shortcutPosition < $shortcutCount; $shortcutPosition++) {
            $shortcut = $shortcutsInArgument[$shortcutPosition];
            if (!isset($shortcuts[$shortcut])) {
                continue;
            }

            if (!AnalyseCommandDefinition::optionAcceptsValue($shortcuts[$shortcut]['valueMode'])) {
                continue;
            }

            if ($shortcutPosition === $shortcutCount - 1) {
                self::consumeFollowingOptionValue($arguments, $position);
            }
            return;
        }
    }

    /**
     * Matches Symfony's rule that an option value may not begin with a dash.
     *
     * @param string[] $arguments
     * @param int $position
     * @return void
     * @see \Symfony\Component\Console\Input\ArgvInput::addLongOption()
     */
    private static function consumeFollowingOptionValue(array $arguments, &$position)
    {
        $nextPosition = $position + 1;
        if (
            isset($arguments[$nextPosition])
            && ($arguments[$nextPosition] === '' || strpos($arguments[$nextPosition], '-') !== 0)
        ) {
            $position = $nextPosition;
        }
    }
}
