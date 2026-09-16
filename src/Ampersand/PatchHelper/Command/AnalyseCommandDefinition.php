<?php

namespace Ampersand\PatchHelper\Command;

class AnalyseCommandDefinition
{
    private const ANALYSE_COMMAND = 'analyse';
    private const PROJECT_ARGUMENT_NAME = 'project';
    private const PROJECT_ARGUMENT_DESCRIPTION = 'The path to the magento2 project';

    private const VALUE_NONE = 'none';
    private const VALUE_OPTIONAL = 'optional';
    private const VALUE_REQUIRED = 'required';

    private const ANALYSE_OPTIONS = [
        [
            'name' => 'auto-theme-update',
            'shortcut' => 'a',
            'valueMode' => self::VALUE_OPTIONAL,
            'description' => 'Fuzz factor for automatically applying changes to local theme',
        ],
        [
            'name' => 'sort-by-type',
            'shortcut' => null,
            'valueMode' => self::VALUE_NONE,
            'description' => 'Sort the output by override type',
        ],
        [
            'name' => 'phpstorm-threeway-diff-commands',
            'shortcut' => null,
            'valueMode' => self::VALUE_NONE,
            'description' => 'Output phpstorm threeway diff commands',
        ],
        [
            'name' => 'vendor-namespaces',
            'shortcut' => null,
            'valueMode' => self::VALUE_OPTIONAL,
            'description' => 'Only show custom modules with these namespaces (comma separated list)',
        ],
        [
            'name' => 'filter',
            'shortcut' => null,
            'valueMode' => self::VALUE_REQUIRED,
            'description' => 'Filter the patchfile for entries containing this phrase, used during tool development',
        ],
        [
            'name' => 'pad-table-columns',
            'shortcut' => null,
            'valueMode' => self::VALUE_REQUIRED,
            'description' => 'Pad the table column width',
        ],
        [
            'name' => 'php-strict-errors',
            'shortcut' => null,
            'valueMode' => self::VALUE_NONE,
            'description' => 'Any php errors/warnings/notices will throw an exception',
        ],
        [
            'name' => 'show-info',
            'shortcut' => null,
            'valueMode' => self::VALUE_NONE,
            'description' => 'Show all INFO level reports',
        ],
        [
            'name' => 'show-ignore',
            'shortcut' => null,
            'valueMode' => self::VALUE_NONE,
            'description' => 'Show all IGNORE level reports',
        ],
    ];

    /**
     * @return string
     */
    public static function getAnalyseCommandName()
    {
        return self::ANALYSE_COMMAND;
    }

    /**
     * @return string
     */
    public static function getProjectArgumentName()
    {
        return self::PROJECT_ARGUMENT_NAME;
    }

    /**
     * @return string
     */
    public static function getProjectArgumentDescription()
    {
        return self::PROJECT_ARGUMENT_DESCRIPTION;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public static function getAnalyseOptions()
    {
        return self::ANALYSE_OPTIONS;
    }

    /**
     * @param string $valueMode
     * @return bool
     */
    public static function optionAcceptsValue($valueMode)
    {
        return $valueMode === self::VALUE_OPTIONAL || $valueMode === self::VALUE_REQUIRED;
    }

    /**
     * @param string $valueMode
     * @return bool
     */
    public static function optionRequiresValue($valueMode)
    {
        return $valueMode === self::VALUE_REQUIRED;
    }
}
