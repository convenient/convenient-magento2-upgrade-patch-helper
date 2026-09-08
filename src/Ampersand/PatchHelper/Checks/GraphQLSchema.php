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
}
