<?php

namespace Ampersand\PatchHelper\Service;

use Ampersand\PatchHelper\Helper\Magento2Instance;
use Ampersand\PatchHelper\Patchfile\Entry as PatchEntry;

class HyvaCompatModuleResolver
{
    /** @var Magento2Instance */
    private $m2;

    /** @var PatchEntry */
    private $patchEntry;

    /** @var string */
    private $type;

    /** @var callable(string, string, string, \Magento\Theme\Model\Theme, string): (string|false) */
    private $resolveFile;

    /** @var array<string, string[]>|null */
    private $compatModules;

    /**
     * @param callable(string, string, string, \Magento\Theme\Model\Theme, string): (string|false) $resolveFile
     */
    public function __construct(Magento2Instance $m2, PatchEntry $patchEntry, string $type, callable $resolveFile)
    {
        $this->m2 = $m2;
        $this->patchEntry = $patchEntry;
        $this->type = $type;
        $this->resolveFile = $resolveFile;
    }

    /**
     * @return array<string, string[]>
     */
    private function getHyvaCompatModules(): array
    {
        // Hyva is optional; check availability before requesting its services.
        $registryClass = 'Hyva\\CompatModuleFallback\\Model\\CompatModuleRegistry';
        if (!class_exists($registryClass)) {
            return [];
        }

        $config = $this->m2->getAreaConfig();
        $ruleClass = 'Magento\\Framework\\View\\Design\\Fallback\\Rule\\ModularSwitch';
        // Frontend configuration can disable a plugin declared globally.
        $plugins = array_replace_recursive(
            $config['global'][$ruleClass]['plugins'] ?? [],
            $config['frontend'][$ruleClass]['plugins'] ?? []
        );
        foreach ($plugins as $plugin) {
            if (
                ($plugin['instance'] ?? '') === 'Hyva\\CompatModuleFallback\\Plugin\\ViewFileOverride'
                && empty($plugin['disabled'])
            ) {
                $registry = $this->m2->getObjectManager()->get($registryClass);
                $modules = [];
                // Retain the registry's compatibility-module order for each original module.
                foreach ($registry->getOrigModules() as $originalModule) {
                    $modules[$originalModule] = $registry->getCompatModulesFor($originalModule);
                }
                return $modules;
            }
        }
        return [];
    }

    /**
     * @param string $module
     * @param string $name
     * @param string $area
     * @param \Magento\Theme\Model\Theme $theme
     * @param string[] $paths
     * @return string[]
     */
    public function resolveOverrides($module, $name, $area, $theme, array $paths): array
    {
        $this->compatModules = $this->compatModules ?? $this->getHyvaCompatModules();
        $compatModules = $this->compatModules;
        if (!$compatModules) {
            return $paths;
        }

        $root = rtrim($this->m2->getMagentoRoot(), '/') . '/';
        $moduleDirs = [];
        foreach ($this->m2->getListOfPathsToModules() as $path => $moduleName) {
            $moduleDirs[$moduleName] = $root . rtrim($path, '/');
        }
        $changedFile = realpath($root . $this->patchEntry->getPath());

        // Original-module change: suppress only when a compatibility file takes precedence.
        if (!empty($compatModules[$module])) {
            $source = $this->findHyvaModuleFile($module, $name, $area, $compatModules[$module], $moduleDirs);
            if ($source && $source['module'] !== $module) {
                $paths = [];
            }
        }

        // Compatibility-module change: look for overrides using the original module names.
        foreach ($compatModules as $originalModule => $replacements) {
            if (!in_array($module, $replacements, true)) {
                continue;
            }
            // A compatibility file may sit inside an Original_Module subdirectory.
            $originalName = str_starts_with($name, $originalModule . '/')
                ? substr($name, strlen($originalModule) + 1) : $name;
            $source = $this->findHyvaModuleFile($originalModule, $originalName, $area, $replacements, $moduleDirs);
            // Do not attribute overrides to a compatibility file shadowed by an earlier candidate.
            if (!$source || $source['path'] !== $changedFile) {
                continue;
            }
            $override = ($this->resolveFile)($this->type, $originalName, $area, $theme, $originalModule);
            if (!$override) {
                continue;
            }
            // A module-supplied file is the fallback source, not a theme override.
            $overridePath = $root . sanitize_filepath($root, $override);
            foreach (array_merge([$originalModule], $replacements) as $sourceModule) {
                if (
                    isset($moduleDirs[$sourceModule])
                    && str_starts_with($overridePath, $moduleDirs[$sourceModule] . '/')
                ) {
                    continue 2;
                }
            }
            $paths[] = $override;
        }
        return $paths;
    }

    /**
     * Magento RulePool checks frontend before base. Hyva ViewFileOverride inserts each
     * compatibility module's namespaced and ordinary directories before the original module.
     * Locale-specific web directories are omitted because resolve() passes no locale.
     *
     * @param string $module
     * @param string $name
     * @param string $area
     * @param string[] $replacements
     * @param array<string, string> $moduleDirs
     * @return array{module: string, path: string}|null
     */
    private function findHyvaModuleFile($module, $name, $area, array $replacements, array $moduleDirs): ?array
    {
        if (!isset($moduleDirs[$module])) {
            return null;
        }
        $directory = $this->type === 'static' ? 'web' : 'templates';
        foreach ([$area, 'base'] as $fallbackArea) {
            foreach (array_merge($replacements, [$module]) as $candidateModule) {
                if (!isset($moduleDirs[$candidateModule])) {
                    continue;
                }
                $names = $candidateModule === $module ? [$name] : [$module . '/' . $name, $name];
                foreach ($names as $candidateName) {
                    $path = $moduleDirs[$candidateModule] . '/view/' . $fallbackArea
                        . '/' . $directory . '/' . $candidateName;
                    if (is_file($path)) {
                        // Registration alone is insufficient: the first existing file wins.
                        return ['module' => $candidateModule, 'path' => (string) realpath($path)];
                    }
                }
            }
        }
        return null;
    }
}
