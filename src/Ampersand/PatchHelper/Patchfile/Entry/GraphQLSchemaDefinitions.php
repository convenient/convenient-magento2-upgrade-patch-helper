<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

use GraphQL\Language\Parser;

class GraphQLSchemaDefinitions
{
    private const PLACEHOLDER_FIELD = 'patch_helper_placeholder_graphql_field';

    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getDefinitions(string $file)
    {
        if (pathinfo($file, PATHINFO_BASENAME) !== 'schema.graphqls') {
            return [];
        }

        $filepath = realpath($this->directory . DIRECTORY_SEPARATOR . $file);
        return $this->getDefinitionsFromFilepath($filepath ?: '');
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getDefinitionsFromFilepath(string $filepath)
    {
        if (pathinfo($filepath, PATHINFO_BASENAME) !== 'schema.graphqls') {
            return [];
        }
        if (!is_file($filepath)) {
            return [];
        }

        if (!class_exists(Parser::class)) {
            throw new \InvalidArgumentException(
                'GraphQL parser is unavailable; GraphQL schema check skipped'
            );
        }

        try {
            $schema = $this->convertInterfacesToAnnotations((string)file_get_contents($filepath));
            $document = Parser::parse($this->addPlaceholdersToEmptyTypes($schema));
        } catch (\Throwable $throwable) {
            throw new \RuntimeException(
                sprintf('Unable to parse %s: %s', $filepath, $throwable->getMessage())
            );
        }

        $typeDeclarations = [];
        foreach ($document->definitions as $definition) {
            if (!$this->isTypeDeclaration($definition)) {
                continue;
            }

            // Magento's GraphQlReader::parseTypes() keys declarations by type
            // name, so the later declaration in a schema file replaces an
            // earlier declaration of the same type.
            $typeDeclarations[$this->getTypeDeclarationName($definition)] = $definition;
        }

        $typeContracts = [];
        foreach ($typeDeclarations as $typeName => $typeDeclaration) {
            $typeContracts[$typeName] = $this->getTypeContract($typeDeclaration);
        }

        $definitions = [];
        foreach (['Query', 'Mutation'] as $rootType) {
            if (!isset($typeDeclarations[$rootType])) {
                continue;
            }

            $rootDeclaration = $typeDeclarations[$rootType];
            if (
                !($rootDeclaration instanceof \GraphQL\Language\AST\ObjectTypeDefinitionNode)
                && !($rootDeclaration instanceof \GraphQL\Language\AST\ObjectTypeExtensionNode)
            ) {
                continue;
            }

            foreach ($rootDeclaration->fields as $field) {
                if ($field->name->value === self::PLACEHOLDER_FIELD) {
                    continue;
                }
                $label = $rootType . ' ' . $field->name->value;
                $definitions[$label] = [
                    'label' => $label,
                    'value' => md5(serialize([
                        'field' => $this->getFieldContract($field),
                        'reachableTypes' => $this->getReachableTypeContracts($field, $typeDeclarations, $typeContracts)
                    ]))
                ];
            }
        }

        ksort($definitions);
        return $definitions;
    }

    /**
     * @param object $definition
     * @return bool
     */
    private function isTypeDeclaration($definition): bool
    {
        return $definition instanceof \GraphQL\Language\AST\ObjectTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\ObjectTypeExtensionNode
            || $definition instanceof \GraphQL\Language\AST\InterfaceTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\InterfaceTypeExtensionNode
            || $definition instanceof \GraphQL\Language\AST\InputObjectTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\InputObjectTypeExtensionNode
            || $definition instanceof \GraphQL\Language\AST\EnumTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\EnumTypeExtensionNode
            || $definition instanceof \GraphQL\Language\AST\UnionTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\UnionTypeExtensionNode
            || $definition instanceof \GraphQL\Language\AST\ScalarTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\ScalarTypeExtensionNode;
    }

    /**
     * @param object $definition
     * @return string
     */
    private function getTypeDeclarationName($definition): string
    {
        return $definition->name->value;
    }

    /**
     * @param object $definition
     * @return array<string, mixed>
     */
    private function getTypeContract($definition): array
    {
        $contract = [
            'kind' => $this->getTypeDeclarationKind($definition),
            'directives' => $this->getDirectives($definition->directives)
        ];

        if (isset($definition->interfaces)) {
            $interfaces = [];
            foreach ($definition->interfaces as $interface) {
                $interfaces[] = $interface->name->value;
            }
            sort($interfaces);
            $contract['interfaces'] = $interfaces;
        }

        if (isset($definition->fields)) {
            $fields = [];
            foreach ($definition->fields as $field) {
                if ($field->name->value === self::PLACEHOLDER_FIELD) {
                    continue;
                }
                $fields[$field->name->value] = $field instanceof \GraphQL\Language\AST\FieldDefinitionNode
                    ? $this->getFieldContract($field) : $this->getInputValueContract($field);
            }
            ksort($fields);
            $contract['fields'] = $fields;
        }

        if (isset($definition->values)) {
            $values = [];
            foreach ($definition->values as $value) {
                if ($value->name->value === self::PLACEHOLDER_FIELD) {
                    continue;
                }
                $values[$value->name->value] = [
                    'directives' => $this->getDirectives($value->directives)
                ];
            }
            ksort($values);
            $contract['values'] = $values;
        }

        if (isset($definition->types)) {
            $types = [];
            foreach ($definition->types as $type) {
                $types[] = $type->name->value;
            }
            sort($types);
            $contract['types'] = $types;
        }

        return $contract;
    }

    /**
     * @param object $definition
     * @return string
     */
    private function getTypeDeclarationKind($definition): string
    {
        if (
            $definition instanceof \GraphQL\Language\AST\ObjectTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\ObjectTypeExtensionNode
        ) {
            return 'object';
        }
        if (
            $definition instanceof \GraphQL\Language\AST\InterfaceTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\InterfaceTypeExtensionNode
        ) {
            return 'interface';
        }
        if (
            $definition instanceof \GraphQL\Language\AST\InputObjectTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\InputObjectTypeExtensionNode
        ) {
            return 'input';
        }
        if (
            $definition instanceof \GraphQL\Language\AST\EnumTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\EnumTypeExtensionNode
        ) {
            return 'enum';
        }
        if (
            $definition instanceof \GraphQL\Language\AST\UnionTypeDefinitionNode
            || $definition instanceof \GraphQL\Language\AST\UnionTypeExtensionNode
        ) {
            return 'union';
        }
        return 'scalar';
    }

    /**
     * @param object $field
     * @param array<string, object> $typeDeclarations
     * @param array<string, array<string, mixed>> $typeContracts
     * @return array<string, array<string, mixed>>
     */
    private function getReachableTypeContracts($field, array $typeDeclarations, array $typeContracts): array
    {
        $pending = $this->getFieldReferences($field);
        $reachable = [];

        while (!empty($pending)) {
            $typeName = array_pop($pending);
            if (isset($reachable[$typeName]) || !isset($typeDeclarations[$typeName])) {
                continue;
            }

            $reachable[$typeName] = $typeContracts[$typeName];
            foreach ($this->getDefinitionReferences($typeDeclarations[$typeName]) as $reference) {
                $pending[] = $reference;
            }
        }

        ksort($reachable);
        return $reachable;
    }

    /**
     * @param object $definition
     * @return string[]
     */
    private function getDefinitionReferences($definition): array
    {
        $references = [];
        if (isset($definition->interfaces)) {
            foreach ($definition->interfaces as $interface) {
                $references[] = $interface->name->value;
            }
        }
        foreach ($this->getImplementsReferences($definition) as $interface) {
            $references[] = $interface;
        }
        if (isset($definition->fields)) {
            foreach ($definition->fields as $field) {
                if ($field->name->value === self::PLACEHOLDER_FIELD) {
                    continue;
                }
                foreach (
                    $field instanceof \GraphQL\Language\AST\FieldDefinitionNode
                    ? $this->getFieldReferences($field) : $this->getInputValueReferences($field) as $reference
                ) {
                    $references[] = $reference;
                }
            }
        }
        if (isset($definition->types)) {
            foreach ($definition->types as $type) {
                $references[] = $type->name->value;
            }
        }
        return array_values(array_unique($references));
    }

    /**
     * Magento converts source `implements` clauses into an @implements
     * directive before parsing. Mirror ImplementsReader so the local type graph
     * continues to reach interface declarations after that normalization.
     *
     * @param object $definition
     * @return string[]
     */
    private function getImplementsReferences($definition): array
    {
        if (!isset($definition->directives)) {
            return [];
        }

        $references = [];
        foreach ($definition->directives as $directive) {
            if ($directive->name->value !== 'implements') {
                continue;
            }
            foreach ($directive->arguments as $argument) {
                if ($argument->name->value !== 'interfaces') {
                    continue;
                }
                if ($argument->value instanceof \GraphQL\Language\AST\ListValueNode) {
                    foreach ($argument->value->values as $value) {
                        $references[] = $this->getReferenceValue($value);
                    }
                    continue;
                }
                $references[] = $this->getReferenceValue($argument->value);
            }
        }
        return array_values(array_unique($references));
    }

    /**
     * @param object $value
     * @return string
     */
    private function getReferenceValue($value): string
    {
        return $value->value;
    }

    /**
     * @param object $field
     * @return string[]
     */
    private function getFieldReferences($field): array
    {
        $references = $this->getTypeReferences($field->type);
        foreach ($field->arguments as $argument) {
            foreach ($this->getInputValueReferences($argument) as $reference) {
                $references[] = $reference;
            }
        }
        return array_values(array_unique($references));
    }

    /**
     * @param object $value
     * @return string[]
     */
    private function getInputValueReferences($value): array
    {
        return $this->getTypeReferences($value->type);
    }

    /**
     * @param object $type
     * @return string[]
     */
    private function getTypeReferences($type): array
    {
        if ($type instanceof \GraphQL\Language\AST\NamedTypeNode) {
            return [$type->name->value];
        }
        return $this->getTypeReferences($type->type);
    }

    /**
     * @param object $field
     * @return array<string, mixed>
     */
    private function getFieldContract($field): array
    {
        $arguments = [];
        foreach ($field->arguments as $argument) {
            $arguments[$argument->name->value] = $this->getInputValueContract($argument);
        }
        ksort($arguments);

        return [
            'arguments' => $arguments,
            'type' => $this->getType($field->type),
            'directives' => $this->getDirectives($field->directives)
        ];
    }

    /**
     * @param object $value
     * @return array<string, mixed>
     */
    private function getInputValueContract($value): array
    {
        return [
            'type' => $this->getType($value->type),
            'default' => isset($value->defaultValue) && $value->defaultValue !== null
                ? $this->getValue($value->defaultValue) : null,
            'directives' => $this->getDirectives($value->directives)
        ];
    }

    /**
     * @param iterable<object> $directives
     * @return array<string, array<string, mixed>>
     */
    private function getDirectives(iterable $directives): array
    {
        $result = [];
        foreach ($directives as $directive) {
            if ($directive->name->value === 'doc') {
                continue;
            }

            $arguments = [];
            foreach ($directive->arguments as $argument) {
                $arguments[$argument->name->value] = $this->getValue($argument->value);
            }
            ksort($arguments);
            $result[$directive->name->value] = $arguments;
        }
        ksort($result);
        return $result;
    }

    /**
     * @param object $type
     * @return string
     */
    private function getType($type): string
    {
        if ($type instanceof \GraphQL\Language\AST\NamedTypeNode) {
            return $type->name->value;
        }
        if ($type instanceof \GraphQL\Language\AST\ListTypeNode) {
            return '[' . $this->getType($type->type) . ']';
        }
        if ($type instanceof \GraphQL\Language\AST\NonNullTypeNode) {
            return $this->getType($type->type) . '!';
        }
        throw new \LogicException('Unsupported GraphQL type node');
    }

    /**
     * @param object $value
     * @return mixed
     */
    private function getValue($value)
    {
        $type = get_class($value);
        if ($value instanceof \GraphQL\Language\AST\NullValueNode) {
            return ['type' => $type, 'value' => null];
        }
        if ($value instanceof \GraphQL\Language\AST\ListValueNode) {
            $values = [];
            foreach ($value->values as $item) {
                $values[] = $this->getValue($item);
            }
            return ['type' => $type, 'value' => $values];
        }
        if ($value instanceof \GraphQL\Language\AST\ObjectValueNode) {
            $fields = [];
            foreach ($value->fields as $field) {
                $fields[$field->name->value] = $this->getValue($field->value);
            }
            ksort($fields);
            return ['type' => $type, 'value' => $fields];
        }
        return ['type' => $type, 'value' => $value->value];
    }

    /**
     * Magento accepts comma-separated interface lists and normalizes them before
     * passing SDL to the GraphQL parser.
     *
     * @param string $schema
     * @return string
     */
    private function convertInterfacesToAnnotations(string $schema): string
    {
        $typeName = '([_A-Za-z][_0-9A-Za-z]+)';
        $space = '([\\s\\t\\n\\r]+)';
        $optionalSpace = '[\\s\\t\\n\\r]*';
        $implementsPattern = "/{$space}implements{$space}{$typeName}"
            . "(,{$optionalSpace}|({$optionalSpace}&{$optionalSpace})?{$typeName})*/im";
        preg_match_all(
            $implementsPattern,
            $schema,
            $matches
        );

        foreach (array_unique($matches[0]) as $implements) {
            $declarations = preg_replace("/{$space}implements{$space}/m", '', $implements);
            preg_match_all("/{$typeName}+/im", (string)$declarations, $names);
            if (empty($names[0])) {
                continue;
            }
            $interfaces = array_map(function (string $name): string {
                return '"' . $name . '"';
            }, $names[0]);
            $schema = str_replace(
                $implements,
                ' @implements(interfaces: [' . implode(', ', $interfaces) . ']) ',
                $schema
            );
        }
        return $schema;
    }

    /**
     * Magento adds placeholders before parsing SDL because empty type and enum
     * declarations are valid module fragments even though graphql-php rejects
     * them in isolation.
     *
     * @param string $schema
     * @return string
     */
    private function addPlaceholdersToEmptyTypes(string $schema): string
    {
        $typeKinds = '(type|interface|input|union|scalar)';
        $enumKind = '(enum)';
        $typeName = '([_A-Za-z][_0-9A-Za-z]+)';
        $typeDefinition = '([^\\{\\}]*)(\\{[\\s\\t\\n\\r^\\}]*\\})';
        $space = '([\\s\\t\\n\\r]+)';

        $schema = preg_replace(
            "/{$typeKinds}{$space}{$typeName}{$space}{$typeDefinition}/im",
            '$1$2$3$4$5{' . "\n" . self::PLACEHOLDER_FIELD . ": String\n}",
            $schema
        );
        return (string)preg_replace(
            "/{$enumKind}{$space}{$typeName}{$space}{$typeDefinition}/im",
            '$1$2$3$4$5{' . "\n" . self::PLACEHOLDER_FIELD . "\n}",
            $schema
        );
    }
}
