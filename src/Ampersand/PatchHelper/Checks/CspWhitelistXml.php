<?php

namespace Ampersand\PatchHelper\Checks;

use Ampersand\PatchHelper\Checks;

class CspWhitelistXml extends AbstractCheck
{
    /**
     * @return bool
     */
    public function canCheck()
    {
        return str_ends_with($this->patchEntry->getPath(), '/etc/csp_whitelist.xml')
            || str_ends_with($this->patchEntry->getOriginalPath(), '/etc/csp_whitelist.xml');
    }

    /**
     * Add INFO notices when CSP whitelist declarations are added, removed or changed.
     *
     * @return void
     */
    public function check()
    {
        try {
            $originalDefinitions = $this->patchEntry->getContentSecurityDefinitionsFromOriginalFile();
            $newDefinitions = $this->patchEntry->getContentSecurityDefinitionsFromNewFile();

            foreach ($originalDefinitions as $identity => $definition) {
                if (!isset($newDefinitions[$identity])) {
                    $label = $definition['label'];
                    $this->infos[Checks::TYPE_CSP_REMOVED][$label] = $label;
                    continue;
                }

                if ($newDefinitions[$identity]['value'] !== $definition['value']) {
                    $label = $newDefinitions[$identity]['label'];
                    $this->infos[Checks::TYPE_CSP_CHANGED][$label] = $label;
                }
            }

            foreach ($newDefinitions as $identity => $definition) {
                if (!isset($originalDefinitions[$identity])) {
                    $label = $definition['label'];
                    $this->infos[Checks::TYPE_CSP_ADDED][$label] = $label;
                }
            }
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException('etc/csp_whitelist.xml not parseable: ' . $throwable->getMessage());
        }
    }
}
