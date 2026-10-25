<?php

namespace Ampersand\PatchHelper\Helper\Magento2Instance;

use Ampersand\PatchHelper\Patchfile\Entry\WebApiRestDefinitions as PatchWebApiRestDefinitions;

class WebApiRestDefinitions
{
    /** @var string */
    private $rootDir;

    /** @var string[] */
    private $xmlFiles;

    /** @var array<string, array<int, string>> */
    private $routeDefinitions = [];

    /**
     * @param string $rootDir
     * @param string[] $xmlFiles
     */
    public function __construct(string $rootDir, array $xmlFiles)
    {
        $this->rootDir = $rootDir;
        $this->xmlFiles = $xmlFiles;
    }

    /**
     * Prepare a map of REST route identities to every webapi.xml that contributes to them.
     *
     * @return \Throwable[] Failures for individual files; valid contributors are retained.
     */
    public function prepare()
    {
        $webApiFiles = array_unique(array_filter($this->xmlFiles, function ($potentialWebApi) {
            if (!str_ends_with($potentialWebApi, '/etc/webapi.xml')) {
                return false;
            }
            foreach (['/tests/', '/dev/tools/'] as $dir) {
                if (str_contains($potentialWebApi, $dir)) {
                    return false;
                }
            }
            return true;
        }));

        $errors = [];
        $webApiRestDefinitions = new PatchWebApiRestDefinitions('');
        foreach ($webApiFiles as $webApiFile) {
            try {
                $relativeFile = sanitize_filepath($this->rootDir, $webApiFile);
                foreach ($webApiRestDefinitions->getDefinitionsFromFilepath($webApiFile) as $identity => $definition) {
                    $this->routeDefinitions[$identity][] = $relativeFile;
                }
            } catch (\Throwable $throwable) {
                $errors[] = new \RuntimeException($webApiFile . ': ' . $throwable->getMessage(), 0, $throwable);
            }
        }
        unset($webApiRestDefinitions, $webApiFile, $relativeFile, $identity, $definition);

        ksort($this->routeDefinitions);
        foreach ($this->routeDefinitions as &$files) {
            $files = array_values(array_unique($files));
            sort($files);
        }
        unset($files);
        return $errors;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getRouteDefinitions()
    {
        return $this->routeDefinitions;
    }
}
