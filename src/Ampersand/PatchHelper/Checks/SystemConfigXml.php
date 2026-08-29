<?php

namespace Ampersand\PatchHelper\Checks;

use Ampersand\PatchHelper\Checks;

class SystemConfigXml extends AbstractCheck
{
    /**
     * @return bool
     */
    public function canCheck()
    {
        return str_ends_with($this->patchEntry->getPath(), '/etc/adminhtml/system.xml')
            || str_ends_with($this->patchEntry->getOriginalPath(), '/etc/adminhtml/system.xml');
    }

    /**
     * Add INFO notices when system configuration fields are added, removed or changed.
     *
     * @return void
     */
    public function check()
    {
        try {
            $originalDefinitions = $this->patchEntry->getSystemConfigDefinitionsFromOriginalFile();
            $newDefinitions = $this->patchEntry->getSystemConfigDefinitionsFromNewFile();

            foreach ($originalDefinitions as $path => $definition) {
                if (!isset($newDefinitions[$path])) {
                    $this->infos[Checks::TYPE_SYSTEM_CONFIG_REMOVED][$path] = $path;
                    continue;
                }
                if ($newDefinitions[$path] !== $definition) {
                    $this->infos[Checks::TYPE_SYSTEM_CONFIG_CHANGED][$path] = $path;
                }
            }

            foreach ($newDefinitions as $path => $definition) {
                if (!isset($originalDefinitions[$path])) {
                    $this->infos[Checks::TYPE_SYSTEM_CONFIG_ADDED][$path] = $path;
                }
            }
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException('adminhtml/system.xml not parseable: ' . $throwable->getMessage());
        }
    }
}
