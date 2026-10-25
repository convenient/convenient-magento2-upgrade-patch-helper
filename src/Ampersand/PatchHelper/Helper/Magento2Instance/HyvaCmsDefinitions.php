<?php

namespace Ampersand\PatchHelper\Helper\Magento2Instance;

use Ampersand\PatchHelper\Patchfile\Entry\HyvaCmsDefinitions as JsonDefinitions;

class HyvaCmsDefinitions
{
    /** @var string */
    private $rootDir;
    /** @var array<string, string> */
    private $modulePaths;
    /** @var array<string, array<string, mixed>> */
    private $components = [];
    /** @var array<string, string> */
    private $includePaths = [];
    /** @var array<string, array<string, mixed>> */
    private $fragments = [];

    /** @param array<string, string> $modulePaths */
    public function __construct(string $rootDir, array $modulePaths)
    {
        $this->rootDir = rtrim($rootDir, '/');
        $this->modulePaths = $modulePaths;
    }

    public function prepare(): void
    {
        $reader = new JsonDefinitions('');
        foreach ($this->modulePaths as $relativePath => $module) {
            $directory = $this->rootDir . '/' . trim($relativePath, '/') . '/etc/hyva_cms';
            $file = $directory . '/components.json';
            if (is_file($file)) {
                $relativeFile = trim($relativePath, '/') . '/etc/hyva_cms/components.json';
                $this->components[$relativeFile] = [
                    'module' => $module,
                    'file' => $relativeFile,
                    'hyva' => str_starts_with($relativeFile, 'vendor/hyva-themes/'),
                    'definitions' => $reader->getDefinitionsFromFilepath($file),
                ];
            }

            foreach (glob($directory . '/*.json') ?: [] as $jsonFile) {
                if (basename($jsonFile) === 'components.json') {
                    continue;
                }
                $relativeJsonFile = trim($relativePath, '/') . '/etc/hyva_cms/' . basename($jsonFile);
                $this->fragments[$relativeJsonFile] = [
                    'module' => $module,
                    'file' => $relativeJsonFile,
                    'hyva' => str_starts_with($relativeJsonFile, 'vendor/hyva-themes/'),
                    'definitions' => $reader->getDefinitionsFromFilepath($jsonFile),
                ];
            }
        }
        foreach ($this->components as $componentFile) {
            foreach ($componentFile['definitions'] as $component) {
                if (!is_array($component)) {
                    continue;
                }
                foreach (['content', 'design', 'advanced'] as $section) {
                    foreach ($reader->getIncludes($component, $section) as $reference) {
                        $this->includePaths[$reference] = $this->resolveInclude($reference);
                    }
                }
            }
        }
    }

    /** @return array<string, array<string, mixed>> */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function resolveInclude(string $reference): string
    {
        $parts = explode('::', $reference, 2);
        if (count($parts) !== 2) {
            return '';
        }
        foreach ($this->modulePaths as $relativePath => $module) {
            if ($module === $parts[0]) {
                return trim($relativePath, '/') . '/' . ltrim($parts[1], '/');
            }
        }
        return '';
    }

    /** @return array<string, string> */
    public function getIncludePaths(): array
    {
        return $this->includePaths;
    }

    /** @return array<string, array<string, mixed>> */
    public function getFragments(): array
    {
        return $this->fragments;
    }
}
