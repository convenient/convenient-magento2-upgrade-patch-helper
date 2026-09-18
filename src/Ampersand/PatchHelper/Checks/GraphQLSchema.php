<?php

namespace Ampersand\PatchHelper\Checks;

use Ampersand\PatchHelper\Checks;
use GraphQL\Language\Parser;

class GraphQLSchema extends AbstractCheck
{
    /**
     * @return bool
     */
    public function canCheck()
    {
        return str_ends_with($this->patchEntry->getPath(), '/etc/schema.graphqls')
            || str_ends_with($this->patchEntry->getOriginalPath(), '/etc/schema.graphqls');
    }

    /**
     * @return void
     */
    public function check()
    {
        if (!class_exists(Parser::class)) {
            throw new \InvalidArgumentException(
                'GraphQL parser is unavailable; GraphQL schema check skipped'
            );
        }

        try {
            $vendorFile = $this->patchEntry->getPath();
            $originalDefinitions = $this->patchEntry->getGraphQLSchemaDefinitionsFromOriginalFile();
            $newDefinitions = $this->patchEntry->getGraphQLSchemaDefinitionsFromNewFile();

            foreach ($originalDefinitions as $identity => $definition) {
                $label = $definition['label'];
                $rootType = str_starts_with($label, 'Query ') ? 'Query' : 'Mutation';
                if (!isset($newDefinitions[$identity])) {
                    $this->infos[$this->getInfoType($rootType, 'removed')][$label] = $label;
                    continue;
                }
                if ($newDefinitions[$identity]['value'] !== $definition['value']) {
                    $this->infos[$this->getInfoType($rootType, 'changed')][$label] = $label;
                }
            }

            foreach ($newDefinitions as $identity => $definition) {
                if (isset($originalDefinitions[$identity])) {
                    continue;
                }
                $label = $definition['label'];
                $rootType = str_starts_with($label, 'Query ') ? 'Query' : 'Mutation';
                $this->infos[$this->getInfoType($rootType, 'added')][$label] = $label;
            }

            $rootFieldDefinitions = $this->m2->getGraphQlRootFieldDefinitions();
            foreach (Checks::$graphQlTypes as $graphQlType) {
                if (empty($this->infos[$graphQlType])) {
                    continue;
                }
                foreach ($this->infos[$graphQlType] as $identity) {
                    $contributingFiles = $rootFieldDefinitions[$identity] ?? [];
                    $otherFiles = array_values(array_filter($contributingFiles, function ($file) use ($vendorFile) {
                        return $file !== $vendorFile;
                    }));

                    if (!str_starts_with($vendorFile, 'vendor/magento/') && !empty($otherFiles)) {
                        foreach ($otherFiles as $otherFile) {
                            $this->warnings[$graphQlType][] = "$otherFile ($identity)";
                        }
                        unset($this->infos[$graphQlType][$identity]);
                        continue;
                    }

                    if (!str_starts_with($vendorFile, 'vendor/magento/')) {
                        continue;
                    }
                    foreach ($otherFiles as $otherFile) {
                        if (str_starts_with($otherFile, 'vendor/magento/')) {
                            continue;
                        }
                        $rootType = str_starts_with($identity, 'Query ') ? 'Query' : 'Mutation';
                        $this->warnings[$this->getTargetWarningType($rootType)][] = "$otherFile ($identity)";
                    }
                }
            }
            foreach (Checks::$graphQlTypes as $graphQlType) {
                if (empty($this->infos[$graphQlType])) {
                    unset($this->infos[$graphQlType]);
                }
            }
            unset($graphQlType, $identity, $contributingFiles, $otherFiles, $otherFile);
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException('etc/schema.graphqls not parseable: ' . $throwable->getMessage());
        }
    }

    /**
     * @param string $rootType
     * @param string $change
     * @return string
     */
    private function getInfoType(string $rootType, string $change): string
    {
        $types = [
            'Query' => [
                'added' => Checks::TYPE_GRAPHQL_QUERY_ADDED,
                'removed' => Checks::TYPE_GRAPHQL_QUERY_REMOVED,
                'changed' => Checks::TYPE_GRAPHQL_QUERY_CHANGED
            ],
            'Mutation' => [
                'added' => Checks::TYPE_GRAPHQL_MUTATION_ADDED,
                'removed' => Checks::TYPE_GRAPHQL_MUTATION_REMOVED,
                'changed' => Checks::TYPE_GRAPHQL_MUTATION_CHANGED
            ]
        ];

        return $types[$rootType][$change];
    }

    /**
     * @param string $rootType
     * @return string
     */
    private function getTargetWarningType(string $rootType): string
    {
        return $rootType === 'Query'
            ? Checks::TYPE_GRAPHQL_QUERY_TARGET_CHANGED
            : Checks::TYPE_GRAPHQL_MUTATION_TARGET_CHANGED;
    }
}
