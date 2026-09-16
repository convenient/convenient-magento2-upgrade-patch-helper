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
     * Add REST WebAPI notices when routes are added, removed or changed.
     *
     * @return void
     */
    public function check()
    {
        try {
            $vendorFile = $this->patchEntry->getPath();
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

            $routeDefinitions = $this->m2->getWebApiRestRouteDefinitions();
            foreach (Checks::$webApiRestTypes as $webApiRestType) {
                if (empty($this->infos[$webApiRestType])) {
                    continue;
                }
                foreach ($this->infos[$webApiRestType] as $identity) {
                    $contributingFiles = $routeDefinitions[$identity] ?? [];
                    $otherFiles = array_values(array_filter($contributingFiles, function ($file) use ($vendorFile) {
                        return $file !== $vendorFile;
                    }));

                    if (!str_starts_with($vendorFile, 'vendor/magento/') && !empty($otherFiles)) {
                        foreach ($otherFiles as $otherFile) {
                            $this->warnings[$webApiRestType][] = "$otherFile ($identity)";
                        }
                        unset($this->infos[$webApiRestType][$identity]);
                        continue;
                    }

                    if (!str_starts_with($vendorFile, 'vendor/magento/')) {
                        continue;
                    }
                    foreach ($otherFiles as $otherFile) {
                        if (str_starts_with($otherFile, 'vendor/magento/')) {
                            continue;
                        }
                        $this->warnings[Checks::TYPE_WEBAPI_REST_TARGET_CHANGED][] = "$otherFile ($identity)";
                    }
                }
            }
            foreach (Checks::$webApiRestTypes as $webApiRestType) {
                if (empty($this->infos[$webApiRestType])) {
                    unset($this->infos[$webApiRestType]);
                }
            }
            unset($webApiRestType, $identity, $contributingFiles, $otherFiles, $otherFile);
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException('etc/webapi.xml not parseable: ' . $throwable->getMessage());
        }
    }
}
