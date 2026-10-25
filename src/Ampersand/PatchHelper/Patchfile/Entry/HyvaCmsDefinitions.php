<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

/**
 * Extracts Hyva CMS component definitions and include references.
 *
 * Hyva states that "each top-level key represents a unique component identifier".
 *
 * Hyva documents includes as accepting "either a single path string or an array of paths".
 * Non-string array entries are ignored for tolerant extraction; include order is preserved.
 *
 * @see https://docs.hyva.io/hyva-commerce/features/cms/component-declaration-schema.html
 * @see https://docs.hyva.io/hyva-commerce/features/cms/component-fields.html#reusing-common-fields-with-includes
 */
class HyvaCmsDefinitions
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /** @return array<string, mixed> */
    public function getDefinitions(string $file)
    {
        $path = $this->directory === '' ? $file : $this->directory . DIRECTORY_SEPARATOR . $file;
        return $this->getDefinitionsFromFilepath($path);
    }

    /** @return array<string, mixed> */
    public function getDefinitionsFromFilepath(string $path)
    {
        if (!is_file($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $definitions = json_decode((string)$json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($definitions)) {
            throw new \RuntimeException(sprintf('Unable to parse %s: %s', $path, json_last_error_msg()));
        }
        return $definitions;
    }

    /**
     * @param array<string, mixed> $component
     * @return string[]
     */
    public function getIncludes(array $component, string $section)
    {
        $includes = $component[$section]['includes'] ?? [];
        if (is_string($includes)) {
            return [$includes];
        }
        if (!is_array($includes)) {
            return [];
        }
        return array_values(array_filter($includes, 'is_string'));
    }
}
