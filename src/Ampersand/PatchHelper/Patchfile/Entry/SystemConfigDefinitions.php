<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

class SystemConfigDefinitions
{
    /** @var string */
    private $directory;

    /** @var string */
    private $newFilePath;

    /** @var string */
    private $originalFilePath;

    /** @var array{original: array<string, string>, new: array<string, string>}|null */
    private $definitions;

    /** @var array<string, array<string, string|null>> */
    private $moduleRoots = [];

    public function __construct(string $directory, string $newFilePath, string $originalFilePath)
    {
        $this->directory = $directory;
        $this->newFilePath = $newFilePath;
        $this->originalFilePath = $originalFilePath;
    }

    /**
     * @return array{original: array<string, string>, new: array<string, string>}
     */
    public function getDefinitions()
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $xmlCache = [];
        $original = $this->getDefinitionData($this->originalFilePath, $xmlCache);
        $new = $this->getDefinitionData($this->newFilePath, $xmlCache);

        foreach ($original['includes'] as $identity => $include) {
            if (
                !isset($new['includes'][$identity])
                && $this->includeIsUnchanged($include['path'], $original, $new)
            ) {
                $original['definitions'] = array_merge(
                    $original['definitions'],
                    $this->extractAllDefinitionsFromInclude($include, $xmlCache)
                );
            }
        }

        foreach ($new['includes'] as $identity => $include) {
            if (
                !isset($original['includes'][$identity])
                && $this->includeIsUnchanged($include['path'], $original, $new)
            ) {
                $new['definitions'] = array_merge(
                    $new['definitions'],
                    $this->extractAllDefinitionsFromInclude($include, $xmlCache)
                );
            }
        }

        ksort($original['definitions']);
        ksort($new['definitions']);
        $this->definitions = [
            'original' => $original['definitions'],
            'new' => $new['definitions']
        ];
        return $this->definitions;
    }

    /**
     * @param string $file
     * @param array<string, \SimpleXMLElement> $xmlCache
     * @return array{
     *     definitions: array<string, string>,
     *     includes: array<string, array{path: string, prefix: string[], moduleRoot: string, sideRoot: string}>,
     *     moduleRoot: string,
     *     sideRoot: string
     * }
     */
    private function getDefinitionData(string $file, array &$xmlCache)
    {
        $empty = ['definitions' => [], 'includes' => [], 'moduleRoot' => '', 'sideRoot' => ''];
        if (!$this->isSystemConfigFilePath($file)) {
            return $empty;
        }

        $adminhtmlPosition = strpos($file, '/etc/adminhtml/');
        if ($adminhtmlPosition === false) {
            return $empty;
        }

        $moduleRoot = $this->directory . DIRECTORY_SEPARATOR . substr($file, 0, $adminhtmlPosition);
        $sideName = strstr($file, '/', true);
        if ($sideName === false) {
            return $empty;
        }
        $sideRoot = $this->directory . DIRECTORY_SEPARATOR . $sideName;
        $targetFile = $this->directory . DIRECTORY_SEPARATOR . $file;
        $empty['moduleRoot'] = $moduleRoot;
        $empty['sideRoot'] = $sideRoot;
        if (!is_file($targetFile)) {
            return $empty;
        }

        if (str_ends_with($file, '/etc/adminhtml/system.xml')) {
            $contexts = [[]];
        } else {
            $mainFile = $moduleRoot . '/etc/adminhtml/system.xml';
            if (!is_file($mainFile)) {
                return $empty;
            }
            $contexts = [];
            $this->findFileContexts(
                $this->loadXmlFile($mainFile, $xmlCache),
                $targetFile,
                $moduleRoot,
                $sideRoot,
                [],
                [$mainFile],
                $contexts,
                $xmlCache
            );
        }

        if ($contexts === []) {
            return $empty;
        }

        $contexts = array_values(array_map('unserialize', array_unique(array_map('serialize', $contexts))));
        $xml = $this->loadXmlFile($targetFile, $xmlCache);
        $definitions = [];
        $includes = [];
        foreach ($contexts as $context) {
            $this->collectNodeData(
                $xml,
                $context,
                $moduleRoot,
                $sideRoot,
                false,
                [$targetFile],
                $definitions,
                $includes,
                $xmlCache
            );
        }

        ksort($includes);
        return [
            'definitions' => $definitions,
            'includes' => $includes,
            'moduleRoot' => $moduleRoot,
            'sideRoot' => $sideRoot
        ];
    }

    private function isSystemConfigFilePath(string $file): bool
    {
        return str_ends_with($file, '/etc/adminhtml/system.xml')
            || (str_contains($file, '/etc/adminhtml/system/') && str_ends_with($file, '.xml'));
    }

    /**
     * @param string[] $path
     * @param string[] $stack
     * @param array<int, string[]> $contexts
     * @param array<string, \SimpleXMLElement> $xmlCache
     * @return void
     */
    private function findFileContexts(
        \SimpleXMLElement $node,
        string $targetFile,
        string $moduleRoot,
        string $sideRoot,
        array $path,
        array $stack,
        array &$contexts,
        array &$xmlCache
    ) {
        foreach ($node->children() as $child) {
            $childName = $child->getName();
            if ($childName === 'include' && isset($child['path'])) {
                $includedFile = $this->resolveInclude((string)$child['path'], $moduleRoot, $sideRoot, true);
                if ($includedFile === $targetFile) {
                    $contexts[] = $path;
                }
                $this->assertIncludeIsNotCircular($includedFile, $stack);
                $this->findFileContexts(
                    $this->loadXmlFile($includedFile, $xmlCache),
                    $targetFile,
                    $this->getModuleRoot($includedFile),
                    $sideRoot,
                    $path,
                    array_merge($stack, [$includedFile]),
                    $contexts,
                    $xmlCache
                );
                continue;
            }

            $childPath = $path;
            if (($childName === 'section' || $childName === 'group') && (string)$child['id'] !== '') {
                $childPath[] = (string)$child['id'];
            }
            if ($childName !== 'field') {
                $this->findFileContexts(
                    $child,
                    $targetFile,
                    $moduleRoot,
                    $sideRoot,
                    $childPath,
                    $stack,
                    $contexts,
                    $xmlCache
                );
            }
        }
    }

    /**
     * @param string[] $path
     * @param string[] $stack
     * @param array<string, string> $definitions
     * @param array<string, array{path: string, prefix: string[], moduleRoot: string, sideRoot: string}> $includes
     * @param array<string, \SimpleXMLElement> $xmlCache
     * @return void
     */
    private function collectNodeData(
        \SimpleXMLElement $node,
        array $path,
        string $moduleRoot,
        string $sideRoot,
        bool $expandIncludes,
        array $stack,
        array &$definitions,
        array &$includes,
        array &$xmlCache
    ) {
        foreach ($node->children() as $child) {
            $childName = $child->getName();
            if ($childName === 'field') {
                $field = json_decode(json_encode((array)$child), true);
                if (!is_array($field) || !isset($field['@attributes']['id'])) {
                    continue;
                }

                $fieldPath = implode('/', array_merge($path, [$field['@attributes']['id']]));
                if (isset($field['config_path'])) {
                    $configPath = $this->getValue($field['config_path']);
                    if ($configPath !== null && $configPath !== '') {
                        $fieldPath = $configPath;
                    }
                }

                unset($field['label'], $field['comment']);
                unset($field['@attributes']['translate'], $field['@attributes']['sortOrder']);
                recur_ksort($field);
                $definitions[$fieldPath] = md5(serialize($field));
                continue;
            }

            if ($childName === 'include' && isset($child['path'])) {
                $includePath = (string)$child['path'];
                if (!$expandIncludes) {
                    $includes[serialize([$path, $includePath])] = [
                        'path' => $includePath,
                        'prefix' => $path,
                        'moduleRoot' => $moduleRoot,
                        'sideRoot' => $sideRoot
                    ];
                    continue;
                }

                $includedFile = $this->resolveInclude($includePath, $moduleRoot, $sideRoot, true);
                $this->assertIncludeIsNotCircular($includedFile, $stack);
                $this->collectNodeData(
                    $this->loadXmlFile($includedFile, $xmlCache),
                    $path,
                    $this->getModuleRoot($includedFile),
                    $sideRoot,
                    true,
                    array_merge($stack, [$includedFile]),
                    $definitions,
                    $includes,
                    $xmlCache
                );
                continue;
            }

            $childPath = $path;
            if (($childName === 'section' || $childName === 'group') && (string)$child['id'] !== '') {
                $childPath[] = (string)$child['id'];
            }
            $this->collectNodeData(
                $child,
                $childPath,
                $moduleRoot,
                $sideRoot,
                $expandIncludes,
                $stack,
                $definitions,
                $includes,
                $xmlCache
            );
        }
    }

    /**
     * @param array{path: string, prefix: string[], moduleRoot: string, sideRoot: string} $include
     * @param array<string, \SimpleXMLElement> $xmlCache
     * @return array<string, string>
     */
    private function extractAllDefinitionsFromInclude(array $include, array &$xmlCache)
    {
        $includedFile = $this->resolveInclude(
            $include['path'],
            $include['moduleRoot'],
            $include['sideRoot'],
            true
        );
        $definitions = [];
        $nestedIncludes = [];
        $this->collectNodeData(
            $this->loadXmlFile($includedFile, $xmlCache),
            $include['prefix'],
            $this->getModuleRoot($includedFile),
            $include['sideRoot'],
            true,
            [$includedFile],
            $definitions,
            $nestedIncludes,
            $xmlCache
        );
        return $definitions;
    }

    /**
     * @param array{moduleRoot: string, sideRoot: string} $original
     * @param array{moduleRoot: string, sideRoot: string} $new
     */
    private function includeIsUnchanged(string $includePath, array $original, array $new): bool
    {
        $originalFile = $this->resolveInclude(
            $includePath,
            $original['moduleRoot'],
            $original['sideRoot'],
            false
        );
        $newFile = $this->resolveInclude($includePath, $new['moduleRoot'], $new['sideRoot'], false);
        return $originalFile !== null
            && $newFile !== null
            && file_get_contents($originalFile) === file_get_contents($newFile);
    }

    /**
     * @return string|null
     */
    private function resolveInclude(
        string $includePath,
        string $currentModuleRoot,
        string $sideRoot,
        bool $required
    ) {
        $parts = explode('::', $includePath, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException('Invalid system include path: ' . $includePath);
        }

        $moduleRoot = null;
        if ($currentModuleRoot !== '' && $this->getMagentoModuleName($currentModuleRoot) === $parts[0]) {
            $moduleRoot = $currentModuleRoot;
        } elseif ($sideRoot !== '') {
            $moduleRoot = $this->findMagentoModuleRoot($parts[0], $sideRoot);
        }

        $relativePath = ltrim($parts[1], '/');
        if ($moduleRoot !== null && !str_contains($relativePath, '..')) {
            $file = $moduleRoot . '/etc/adminhtml/' . $relativePath;
            if (is_file($file)) {
                return $file;
            }
        }

        if ($required) {
            throw new \InvalidArgumentException('Included system configuration file not found: ' . $includePath);
        }
        return null;
    }

    /**
     * @return string|null
     */
    private function findMagentoModuleRoot(string $moduleName, string $sideRoot)
    {
        if (array_key_exists($moduleName, $this->moduleRoots[$sideRoot] ?? [])) {
            return $this->moduleRoots[$sideRoot][$moduleName];
        }
        if (!is_dir($sideRoot)) {
            return $this->moduleRoots[$sideRoot][$moduleName] = null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sideRoot, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getFilename() !== 'module.xml' || basename($file->getPath()) !== 'etc') {
                continue;
            }
            $moduleRoot = dirname($file->getPath());
            if ($this->getMagentoModuleName($moduleRoot) === $moduleName) {
                return $this->moduleRoots[$sideRoot][$moduleName] = $moduleRoot;
            }
        }
        return $this->moduleRoots[$sideRoot][$moduleName] = null;
    }

    /**
     * @return string|null
     */
    private function getMagentoModuleName(string $moduleRoot)
    {
        $moduleFile = $moduleRoot . '/etc/module.xml';
        if (!is_file($moduleFile)) {
            return null;
        }
        $contents = file_get_contents($moduleFile);
        if (preg_match('/<module\\b[^>]*\\bname\\s*=\\s*["\\\']([^"\\\']+)/', $contents, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function getModuleRoot(string $file): string
    {
        $position = strpos($file, '/etc/adminhtml/');
        if ($position === false) {
            throw new \InvalidArgumentException('System configuration path is outside etc/adminhtml: ' . $file);
        }
        return substr($file, 0, $position);
    }

    /**
     * @param array<string, \SimpleXMLElement> $xmlCache
     */
    private function loadXmlFile(string $file, array &$xmlCache): \SimpleXMLElement
    {
        if (isset($xmlCache[$file])) {
            return $xmlCache[$file];
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        try {
            libxml_clear_errors();
            $xml = simplexml_load_file($file, \SimpleXMLElement::class, LIBXML_NOCDATA);
            if ($xml === false) {
                $message = 'Unable to parse XML ' . $file;
                foreach (libxml_get_errors() as $error) {
                    $message .= sprintf(
                        ' at line %d, column %d: %s',
                        $error->line,
                        $error->column,
                        trim($error->message)
                    );
                }
                throw new \InvalidArgumentException($message);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        $xmlCache[$file] = $xml;
        return $xml;
    }

    /**
     * @param string[] $stack
     */
    private function assertIncludeIsNotCircular(string $includedFile, array $stack): void
    {
        if (in_array($includedFile, $stack, true)) {
            throw new \InvalidArgumentException(
                'Circular system include detected: ' . implode(' -> ', array_merge($stack, [$includedFile]))
            );
        }
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function getValue($value)
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value) && isset($value[0]) && is_string($value[0])) {
            return trim($value[0]);
        }
        return null;
    }
}
