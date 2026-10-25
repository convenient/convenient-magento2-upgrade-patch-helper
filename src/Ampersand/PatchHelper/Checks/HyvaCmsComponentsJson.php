<?php

namespace Ampersand\PatchHelper\Checks;

use Ampersand\PatchHelper\Checks;

class HyvaCmsComponentsJson extends AbstractCheck
{
    public function canCheck()
    {
        return $this->isVendorComponentsFile($this->patchEntry->getPath())
            || $this->isVendorComponentsFile($this->patchEntry->getOriginalPath());
    }

    public function check()
    {
        try {
            $before = $this->patchEntry->getHyvaCmsDefinitionsFromOriginalFile();
            $after = $this->patchEntry->getHyvaCmsDefinitionsFromNewFile();
            foreach ($this->changes($before, $after) as $component => $type) {
                foreach ($this->m2->getHyvaCmsComponentDefinitions() as $contributor) {
                    if (
                        !$this->isEligibleContributor($contributor)
                        || !isset($contributor['definitions'][$component])
                    ) {
                        continue;
                    }
                    $value = $contributor['file'] . ' (' . $component . ')';
                    $this->warnings[$type][$value] = $value;
                }
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Hyva CMS components.json not parseable: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<string, string>
     */
    private function changes(array $before, array $after)
    {
        $changes = [];
        foreach ($before as $name => $definition) {
            if (!array_key_exists($name, $after)) {
                $changes[$name] = Checks::TYPE_HYVA_CMS_REMOVED;
            } elseif ($definition !== $after[$name]) {
                $changes[$name] = Checks::TYPE_HYVA_CMS_CHANGED;
            }
        }
        foreach ($after as $name => $definition) {
            if (!array_key_exists($name, $before)) {
                $changes[$name] = Checks::TYPE_HYVA_CMS_ADDED;
            }
        }
        return $changes;
    }

    /** @return bool */
    private function isVendorComponentsFile(string $path)
    {
        return str_starts_with($path, 'vendor/') && str_ends_with($path, '/etc/hyva_cms/components.json');
    }

    /**
     * @param array<string, mixed> $contributor
     * @return bool
     */
    private function isEligibleContributor(array $contributor)
    {
        $source = $this->patchEntry->getPath() ?: $this->patchEntry->getOriginalPath();
        $sourceRoot = $this->moduleRoot($source);
        $contributorRoot = $this->moduleRoot($contributor['file']);
        if ($sourceRoot === $contributorRoot) {
            return false;
        }
        return !(str_starts_with($sourceRoot, 'vendor/hyva-themes/') && !empty($contributor['hyva']));
    }

    /** @return string */
    private function moduleRoot(string $file)
    {
        return substr($file, 0, (int)strpos($file, '/etc/hyva_cms/'));
    }
}
