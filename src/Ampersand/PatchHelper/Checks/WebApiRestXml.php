<?php

namespace Ampersand\PatchHelper\Checks;

use Ampersand\PatchHelper\Checks;

class WebApiRestXml extends AbstractCheck
{
    /**
     * @return bool
     */
    public function canCheck()
    {
        return str_ends_with($this->patchEntry->getPath(), '/etc/webapi.xml')
            || str_ends_with($this->patchEntry->getOriginalPath(), '/etc/webapi.xml');
    }

    /**
     * Add INFO notices when REST WebAPI routes are added, removed or changed.
     *
     * @return void
     */
    public function check()
    {
        try {
            $originalDefinitions = $this->patchEntry->getWebApiRestDefinitionsFromOriginalFile();
            $newDefinitions = $this->patchEntry->getWebApiRestDefinitionsFromNewFile();

            foreach ($originalDefinitions as $identity => $definition) {
                $label = $definition['label'];
                if (!isset($newDefinitions[$identity])) {
                    $this->infos[Checks::TYPE_WEBAPI_REST_REMOVED][$label] = $label;
                    continue;
                }
                if ($newDefinitions[$identity]['value'] !== $definition['value']) {
                    $this->infos[Checks::TYPE_WEBAPI_REST_CHANGED][$label] = $label;
                }
            }

            foreach ($newDefinitions as $identity => $definition) {
                if (!isset($originalDefinitions[$identity])) {
                    $label = $definition['label'];
                    $this->infos[Checks::TYPE_WEBAPI_REST_ADDED][$label] = $label;
                }
            }
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException('etc/webapi.xml not parseable: ' . $throwable->getMessage());
        }
    }
}
