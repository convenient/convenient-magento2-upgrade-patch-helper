<?php

namespace Ampersand\PatchHelper\Checks;

use Ampersand\PatchHelper\Checks;
use Ampersand\PatchHelper\Patchfile\Entry\HyvaCmsDefinitions;

/**
 * Reports changed include fields that conflict with external component declarations.
 * This checks declared fields and include order, rather than building a fully merged component.
 */
class HyvaCmsIncludeJson extends AbstractCheck
{
    public function canCheck()
    {
        // Inspect both paths so deleted fragments can still be matched to components using them.
        // Only known include fragments qualify; components.json has its own check.
        foreach ([$this->patchEntry->getPath(), $this->patchEntry->getOriginalPath()] as $path) {
            if (
                str_starts_with($path, 'vendor/')
                && str_contains($path, '/etc/hyva_cms/')
                && !str_ends_with($path, '/components.json')
                && in_array($path, $this->m2->getHyvaCmsIncludePaths(), true)
            ) {
                return true;
            }
        }
        return false;
    }

    public function check()
    {
        try {
            $source = $this->patchEntry->getPath() ?: $this->patchEntry->getOriginalPath();
            $before = $this->patchEntry->getHyvaCmsDefinitionsFromOriginalFile();
            $after = $this->patchEntry->getHyvaCmsDefinitionsFromNewFile();
            $changes = $this->changes($before, $after);
            $reader = new HyvaCmsDefinitions('');
            foreach ($this->m2->getHyvaCmsComponentDefinitions() as $contributor) {
                if (!$this->isEligibleContributor($source, $contributor)) {
                    continue;
                }
                foreach ($contributor['definitions'] as $componentName => $component) {
                    foreach (['content', 'design', 'advanced'] as $section) {
                        $includes = $reader->getIncludes($component, $section);
                        // A component can import the fragment in one section but not the others.
                        if (!$this->includesSource($includes, $source)) {
                            continue;
                        }
                        $types = $this->warningTypes(
                            $before,
                            $after,
                            $changes,
                            $component,
                            $section,
                            $includes,
                            $source
                        );
                        foreach ($types as $type) {
                            // Report the component to review, once per warning type across its sections.
                            $value = $contributor['file'] . ' (' . $componentName . ')';
                            $this->warnings[$type][$value] = $value;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Hyva CMS include JSON not parseable: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<string, string>
     */
    private function changes(array $before, array $after)
    {
        // Compare field definitions by name, not JSON formatting or their position in the file.
        $changes = [];
        foreach ($before as $field => $definition) {
            if (!array_key_exists($field, $after)) {
                $changes[$field] = 'removed';
            } elseif ($definition !== $after[$field]) {
                $changes[$field] = 'changed';
            }
        }
        foreach ($after as $field => $definition) {
            if (!array_key_exists($field, $before)) {
                $changes[$field] = 'added';
            }
        }
        return $changes;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, string> $changes
     * @param array<string, mixed> $component
     * @param string[] $includes
     * @return string[]
     */
    private function warningTypes(
        array $before,
        array $after,
        array $changes,
        array $component,
        string $section,
        array $includes,
        string $source
    ) {
        if ($this->patchEntry->fileWasRemoved()) {
            // The caller already confirmed this section still references the missing fragment.
            return [Checks::TYPE_HYVA_CMS_INCLUDE_REMOVED];
        }
        $types = [];
        foreach ($changes as $field => $change) {
            // An inline field or a later external include can hide the vendor's changed definition.
            // Fields inherited without either kind of override do not need this conflict warning.
            $isMasked = isset($component[$section][$field])
                || $this->isMaskedByLaterExternalInclude($includes, $source, $field);
            if ($isMasked) {
                $types[] = $this->warningTypeForChange($change);
            }
            // A newly inherited field may also collide with an inline field in another editor section.
            if ($change === 'added' && $this->existsInOtherSection($component, $section, $field)) {
                $types[] = Checks::TYPE_HYVA_CMS_INCLUDE_ADDED;
            }
        }
        return array_values(array_unique($types));
    }

    private function warningTypeForChange(string $change): string
    {
        if ($change === 'added') {
            return Checks::TYPE_HYVA_CMS_INCLUDE_ADDED;
        }
        if ($change === 'removed') {
            return Checks::TYPE_HYVA_CMS_INCLUDE_REMOVED;
        }
        return Checks::TYPE_HYVA_CMS_INCLUDE_CHANGED;
    }

    /**
     * @param string[] $includes
     * @return bool
     */
    private function isMaskedByLaterExternalInclude(array $includes, string $source, string $field)
    {
        $foundSource = false;
        $paths = $this->m2->getHyvaCmsIncludePaths();
        $fragments = $this->m2->getHyvaCmsFragments();
        foreach ($includes as $include) {
            $path = $paths[$include] ?? '';
            if ($path === $source) {
                $foundSource = true;
                continue;
            }
            if (!$foundSource || !isset($fragments[$path])) {
                // Earlier includes cannot mask this source; unresolved fragments cannot be compared.
                continue;
            }
            $fragment = $fragments[$path];
            // Only later fragments outside vendor/hyva-themes count as external overrides here.
            if (empty($fragment['hyva']) && isset($fragment['definitions'][$field])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $component
     * @return bool
     */
    private function existsInOtherSection(array $component, string $section, string $field)
    {
        foreach (['content', 'design', 'advanced'] as $other) {
            if ($other !== $section && isset($component[$other][$field])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string[] $includes
     * @return bool
     */
    private function includesSource(array $includes, string $source)
    {
        // Declarations use Module_Name::file.json references; patch entries use filesystem paths.
        $paths = $this->m2->getHyvaCmsIncludePaths();
        foreach ($includes as $include) {
            if (($paths[$include] ?? '') === $source) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $contributor
     * @return bool
     */
    private function isEligibleContributor(string $source, array $contributor)
    {
        $sourceRoot = substr($source, 0, (int)strpos($source, '/etc/hyva_cms/'));
        $contributorRoot = substr($contributor['file'], 0, (int)strpos($contributor['file'], '/etc/hyva_cms/'));
        // Exclude the source module's own components and changes contained within Hyva-owned packages.
        return $sourceRoot !== $contributorRoot
            && !(str_starts_with($sourceRoot, 'vendor/hyva-themes/') && !empty($contributor['hyva']));
    }
}
