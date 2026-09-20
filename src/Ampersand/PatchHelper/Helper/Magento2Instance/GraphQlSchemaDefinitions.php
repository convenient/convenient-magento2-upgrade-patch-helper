<?php

namespace Ampersand\PatchHelper\Helper\Magento2Instance;

use Ampersand\PatchHelper\Patchfile\Entry\GraphQLSchemaDefinitions as PatchGraphQlSchemaDefinitions;

class GraphQlSchemaDefinitions
{
    /** @var string */
    private $rootDir;

    /** @var string[] */
    private $schemaFiles;

    /** @var array<string, array<int, string>> */
    private $rootFieldDefinitions = [];

    /**
     * @param string $rootDir
     * @param string[] $schemaFiles
     */
    public function __construct(string $rootDir, array $schemaFiles)
    {
        $this->rootDir = $rootDir;
        $this->schemaFiles = $schemaFiles;
    }

    /**
     * Prepare a map of GraphQL root-field identities to every schema.graphqls that contributes to them.
     *
     * @return void
     */
    public function prepare()
    {
        $graphQlDefinitions = new PatchGraphQlSchemaDefinitions('');
        foreach ($this->schemaFiles as $schemaFile) {
            $relativeFile = sanitize_filepath($this->rootDir, $schemaFile);
            foreach ($graphQlDefinitions->getDefinitionsFromFilepath($schemaFile) as $identity => $definition) {
                $this->rootFieldDefinitions[$identity][] = $relativeFile;
            }
        }
        unset($graphQlDefinitions, $schemaFile, $relativeFile, $identity, $definition);

        ksort($this->rootFieldDefinitions);
        foreach ($this->rootFieldDefinitions as &$files) {
            $files = array_values(array_unique($files));
            sort($files);
        }
        unset($files);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getRootFieldDefinitions()
    {
        return $this->rootFieldDefinitions;
    }
}
